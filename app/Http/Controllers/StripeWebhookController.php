<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Plan;
use App\Models\User;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Custom Stripe webhook controller.
 * Extends Cashier's built-in controller to sync plan_id on subscription changes.
 */
class StripeWebhookController extends WebhookController
{
    /**
     * Handle customer subscription updated.
     * Syncs the user's plan_id based on the Stripe price.
     */
    public function handleCustomerSubscriptionUpdated(array $payload): Response
    {
        // Let Cashier handle the core subscription update first
        $response = parent::handleCustomerSubscriptionUpdated($payload);

        $this->syncUserPlan($payload);

        return $response;
    }

    /**
     * Handle customer subscription created.
     */
    public function handleCustomerSubscriptionCreated(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionCreated($payload);

        $this->syncUserPlan($payload);

        // Notify user of new subscription
        $stripeCustomerId = $payload['data']['object']['customer'] ?? null;
        if ($stripeCustomerId) {
            $user = User::where('stripe_id', $stripeCustomerId)->first();
            if ($user) {
                $plan = $user->plan;
                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'subscription_created',
                    'title' => 'Subscription Activated',
                    'message' => 'Welcome to the ' . ($plan?->name ?? 'new') . ' plan! Your subscription is now active.',
                ]);
            }
        }

        return $response;
    }

    /**
     * Handle customer subscription deleted (cancelled at period end, or immediately).
     * Move user back to free plan.
     */
    public function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);

        $stripeCustomerId = $payload['data']['object']['customer'] ?? null;
        if ($stripeCustomerId) {
            $user = User::where('stripe_id', $stripeCustomerId)->first();
            if ($user) {
                $freePlan = Plan::where('slug', 'free')->first();
                $user->update(['plan_id' => $freePlan?->id]);
                $this->notifySubscriptionCancelled($user, $freePlan);
            }
        }

        return $response;
    }

    /**
     * Handle invoice payment succeeded.
     * Ensures the user's plan stays in sync after successful payment.
     */
    public function handleInvoicePaymentSucceeded(array $payload): Response
    {
        // Stripe sends the subscription ID in the invoice
        $subscriptionId = $payload['data']['object']['subscription'] ?? null;
        $stripeCustomerId = $payload['data']['object']['customer'] ?? null;

        if ($subscriptionId && $stripeCustomerId) {
            $user = User::where('stripe_id', $stripeCustomerId)->first();

            if ($user && $user->subscribed('default')) {
                $stripePrice = $user->subscription('default')->stripe_price;
                $plan = Plan::where('stripe_price_id', $stripePrice)
                    ->orWhere('stripe_annual_price_id', $stripePrice)
                    ->first();

                if ($plan) {
                    $user->update(['plan_id' => $plan->id]);
                }
            }
        }

        return $this->successMethod();
    }

    /**
     * Handle invoice payment failed.
     * Create an in-app notification for the owner.
     */
    public function handleInvoicePaymentFailed(array $payload): Response
    {
        $stripeCustomerId = $payload['data']['object']['customer'] ?? null;
        $amountDue = $payload['data']['object']['amount_due'] ?? 0;
        $currency = strtoupper($payload['data']['object']['currency'] ?? 'USD');
        $attemptCount = $payload['data']['object']['attempt_count'] ?? 1;
        $nextAttempt = $payload['data']['object']['next_payment_attempt'] ?? null;

        if ($stripeCustomerId) {
            $user = User::where('stripe_id', $stripeCustomerId)->first();

            if ($user) {
                $formattedAmount = '$' . number_format($amountDue / 100, 2);
                $nextAttemptDate = $nextAttempt
                    ? \Carbon\Carbon::createFromTimestamp($nextAttempt)->format('M d, Y')
                    : null;

                $message = "Your payment of {$formattedAmount} {$currency} failed.";
                if ($nextAttemptDate) {
                    $message .= " We'll retry on {$nextAttemptDate}.";
                }
                $message .= ' Please update your payment method to avoid service interruption.';

                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'payment_failed',
                    'title' => 'Payment Failed',
                    'message' => $message,
                ]);
            }
        }

        return $this->successMethod();
    }

    /**
     * Handle customer subscription deleted.
     * Notify the owner that their subscription has ended.
     */
    protected function notifySubscriptionCancelled(User $user, ?Plan $plan): void
    {
        Notification::create([
            'user_id' => $user->id,
            'type' => 'subscription_cancelled',
            'title' => 'Subscription Ended',
            'message' => 'Your subscription has ended and you have been moved to the Free plan. You can re-subscribe at any time from the Subscription page.',
        ]);
    }

    /**
     * Sync the user's plan_id based on the Stripe subscription price.
     */
    protected function syncUserPlan(array $payload): void
    {
        $stripeCustomerId = $payload['data']['object']['customer'] ?? null;
        $items = $payload['data']['object']['items']['data'] ?? [];

        if (! $stripeCustomerId || empty($items)) {
            return;
        }

        $user = User::where('stripe_id', $stripeCustomerId)->first();
        if (! $user) {
            return;
        }

        // Get the price ID from the first subscription item
        $stripePriceId = $items[0]['price']['id'] ?? null;
        if (! $stripePriceId) {
            return;
        }

        $plan = Plan::where('stripe_price_id', $stripePriceId)
            ->orWhere('stripe_annual_price_id', $stripePriceId)
            ->first();

        if ($plan) {
            $user->update(['plan_id' => $plan->id]);
        }
    }
}
