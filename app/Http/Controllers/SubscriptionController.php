<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    /**
     * Create a Stripe Checkout session for the selected plan and redirect.
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'plan' => 'required|exists:plans,slug',
            'billing_cycle' => 'nullable|in:monthly,annual',
        ]);

        $user = $request->user();
        $plan = Plan::where('slug', $request->plan)->where('is_active', true)->firstOrFail();

        // Free plan — just assign directly, no Stripe checkout needed
        if ($plan->isFree()) {
            // If currently subscribed, cancel the Stripe subscription first
            if ($user->subscribed('default')) {
                try {
                    $user->subscription('default')->cancelNow();
                } catch (\Exception $e) {
                    Log::error('Failed to cancel subscription when switching to free plan.', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $user->update(['plan_id' => $plan->id]);

            return redirect('/dashboard')->with('success', 'You are now on the Free plan.');
        }

        // Prevent duplicate subscription creation
        if ($user->subscribed('default') && ! $user->subscription('default')->onGracePeriod()) {
            return redirect('/subscription/pricing')->with('error', 'You already have an active subscription. Use the plan switcher to change plans.');
        }

        $billingCycle = $request->input('billing_cycle', 'monthly');
        $priceId = $billingCycle === 'annual' && $plan->stripe_annual_price_id
            ? $plan->stripe_annual_price_id
            : $plan->stripe_price_id;

        if (! $priceId) {
            return back()->with('error', 'This plan is not yet configured for online payments. Please contact support.');
        }

        try {
            // Create Stripe Checkout session via Cashier
            return $user->newSubscription('default', $priceId)
                ->checkout([
                    'success_url' => route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('subscription.pricing'),
                    'metadata' => [
                        'plan_slug' => $plan->slug,
                        'plan_id' => $plan->id,
                        'billing_cycle' => $billingCycle,
                    ],
                ]);
        } catch (\Laravel\Cashier\Exceptions\IncompletePayment $e) {
            return redirect()->route('cashier.payment', $e->payment->id);
        } catch (\Stripe\Exception\CardException $e) {
            Log::warning('Card error during checkout.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Your card was declined: ' . $e->getMessage());
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe API error during checkout.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'type' => get_class($e),
            ]);

            return back()->with('error', 'A payment processing error occurred. Please try again or contact support.');
        } catch (\Exception $e) {
            Log::error('Unexpected error during checkout.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    /**
     * Redirect the owner to the Stripe Customer Billing Portal.
     */
    public function billingPortal(Request $request)
    {
        $user = $request->user();

        if (! $user->hasStripeId()) {
            return redirect('/subscription/pricing')->with('error', 'No billing account found. Please subscribe to a plan first.');
        }

        try {
            return $user->redirectToBillingPortal(route('subscription.pricing'));
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe API error opening billing portal.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect('/subscription/pricing')->with('error', 'Unable to open the billing portal. Please try again.');
        } catch (\Exception $e) {
            Log::error('Unexpected error opening billing portal.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect('/subscription/pricing')->with('error', 'Something went wrong. Please try again.');
        }
    }

    /**
     * Swap (upgrade/downgrade) subscription to a new plan.
     */
    public function swap(Request $request)
    {
        $request->validate([
            'plan' => 'required|exists:plans,slug',
            'billing_cycle' => 'nullable|in:monthly,annual',
        ]);

        $user = $request->user();
        $plan = Plan::where('slug', $request->plan)->where('is_active', true)->firstOrFail();

        // If user wants to go to free, cancel existing subscription
        if ($plan->isFree()) {
            if ($user->subscribed('default')) {
                try {
                    $user->subscription('default')->cancel();
                } catch (\Exception $e) {
                    Log::error('Failed to cancel subscription during swap to free.', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);

                    return back()->with('error', 'Failed to cancel your current subscription. Please try again.');
                }
            }
            $user->update(['plan_id' => $plan->id]);

            return redirect('/subscription/pricing')->with('success', 'Switched to Free plan. Your subscription will end at the current billing period.');
        }

        $billingCycle = $request->input('billing_cycle', 'monthly');
        $priceId = $billingCycle === 'annual' && $plan->stripe_annual_price_id
            ? $plan->stripe_annual_price_id
            : $plan->stripe_price_id;

        if (! $priceId) {
            return back()->with('error', 'This plan is not yet configured for online payments.');
        }

        // If already subscribed, swap the plan; otherwise create a new checkout
        if ($user->subscribed('default')) {
            try {
                $user->subscription('default')->swap($priceId);
                $user->update(['plan_id' => $plan->id]);

                return redirect('/subscription/pricing')->with('success', 'Your subscription has been updated to ' . $plan->name . '.');
            } catch (\Laravel\Cashier\Exceptions\IncompletePayment $e) {
                return redirect()->route('cashier.payment', $e->payment->id);
            } catch (\Stripe\Exception\CardException $e) {
                Log::warning('Card error during plan swap.', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);

                return back()->with('error', 'Your card was declined: ' . $e->getMessage());
            } catch (\Stripe\Exception\ApiErrorException $e) {
                Log::error('Stripe API error during plan swap.', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);

                return back()->with('error', 'A payment processing error occurred. Please try again.');
            } catch (\Exception $e) {
                Log::error('Unexpected error during plan swap.', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);

                return back()->with('error', 'Something went wrong. Please try again.');
            }
        }

        // Not subscribed yet, redirect to checkout
        return $this->checkout($request);
    }

    /**
     * Download a single invoice PDF for the authenticated owner.
     */
    public function downloadInvoice(Request $request, string $id)
    {
        $user = $request->user();

        if (! $user->hasStripeId()) {
            return redirect('/subscription/invoices')->with('error', 'No billing account found.');
        }

        try {
            $invoice = $user->findInvoiceOrFail($id);
            $filename = 'Rentory-' . $invoice->date()->format('n') . '-' . $invoice->date()->format('Y');

            return $invoice->downloadAs($filename, []);
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return redirect('/subscription/invoices')->with('error', 'Invoice not found.');
        } catch (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e) {
            return redirect('/subscription/invoices')->with('error', 'You do not have access to this invoice.');
        } catch (\Throwable $e) {
            Log::error('Invoice PDF download failed.', [
                'user_id' => $user->id,
                'invoice_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect('/subscription/invoices')->with('error', 'Unable to generate the invoice PDF. Please try again or contact support.');
        }
    }
}
