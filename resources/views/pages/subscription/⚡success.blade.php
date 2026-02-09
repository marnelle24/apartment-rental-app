<?php

use Livewire\Component;
use Mary\Traits\Toast;
use App\Models\Plan;
use App\Traits\AuthorizesRole;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

new class extends Component
{
    use Toast;
    use AuthorizesRole;

    public function mount(): void
    {
        $this->authorizeRole('owner');

        $user = auth()->user();

        // Priority 1: If we have session_id from Stripe Checkout redirect, set plan_id from session metadata.
        // This fixes production where the success page can load before the webhook runs.
        $sessionId = request()->query('session_id');
        if ($sessionId) {
            $this->syncPlanFromCheckoutSession($user, $sessionId);
        }

        // Priority 2: If the user has a Stripe subscription in DB, sync plan_id from the Stripe price
        // (covers revisits or when webhook has already run)
        if ($user->subscribed('default')) {
            $stripePrice = $user->subscription('default')->stripe_price;
            $plan = Plan::where('stripe_price_id', $stripePrice)
                ->orWhere('stripe_annual_price_id', $stripePrice)
                ->first();

            if ($plan && $user->plan_id !== $plan->id) {
                $user->update(['plan_id' => $plan->id]);
            }
        }
    }

    /**
     * Set the user's plan_id from the Stripe Checkout Session metadata.
     * Used when returning from Checkout so we don't rely on webhook timing.
     */
    protected function syncPlanFromCheckoutSession($user, string $sessionId): void
    {
        try {
            \Stripe\Stripe::setApiKey(config('cashier.secret'));
            $session = \Stripe\Checkout\Session::retrieve($sessionId);

            $planId = $session->metadata->plan_id ?? null;
            if ($planId !== null && $planId !== '') {
                $planId = (int) $planId;
                $plan = Plan::find($planId);
                if ($plan && (int) $user->plan_id !== $planId) {
                    $user->update(['plan_id' => $plan->id]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Subscription success: could not sync plan from checkout session', [
                'session_id' => $sessionId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function with(): array
    {
        $user = auth()->user()->fresh();
        $plan = $user->getEffectivePlan();
        $subscription = $user->subscribed('default') ? $user->subscription('default') : null;

        // Determine billing cycle and actual price
        $billingCycle = 'monthly';
        $displayPrice = $plan?->price ?? 0;
        $nextBillingDate = null;

        if ($subscription && $plan) {
            $stripePrice = $subscription->stripe_price;

            if ($plan->stripe_annual_price_id && $stripePrice === $plan->stripe_annual_price_id) {
                $billingCycle = 'annual';
                $displayPrice = $plan->annual_price;
            }

            // Try to get the next billing date from Stripe
            try {
                $stripeSubscription = $user->subscription('default')->asStripeSubscription();
                if ($stripeSubscription->current_period_end) {
                    $nextBillingDate = Carbon::createFromTimestamp($stripeSubscription->current_period_end);
                }
            } catch (\Exception $e) {
                // Stripe API unavailable — skip
            }
        }

        return [
            'plan' => $plan,
            'billingCycle' => $billingCycle,
            'displayPrice' => $displayPrice,
            'nextBillingDate' => $nextBillingDate,
            'isSubscribed' => $user->subscribed('default'),
        ];
    }
};
?>

<div>
    <div class="max-w-2xl mx-auto text-center py-12">
        {{-- Success Icon --}}
        <div class="mb-6">
            <div class="w-20 h-20 bg-teal-500/20 rounded-full flex items-center justify-center mx-auto">
                <x-icon name="o-check-circle" class="w-12 h-12 text-teal-500" />
            </div>
        </div>

        {{-- Success Message --}}
        <h1 class="text-3xl font-bold text-base-content mb-3">Subscription Activated!</h1>
        <p class="text-lg text-base-content/70 mb-8">
            Thank you for subscribing to Rentory. Your <strong class="text-teal-500">{{ $plan?->name ?? 'selected' }}</strong> plan is now active.
        </p>

        {{-- Plan Details Card --}}
        @if($plan)
            <div class="card bg-base-100 border border-base-content/10 shadow mb-8 text-left">
                <div class="card-body">
                    <h3 class="text-lg font-semibold text-base-content mb-4">Your Plan Details</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-base-content/60">Plan</p>
                            <p class="font-semibold text-base-content">{{ $plan->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-base-content/60">Billing Cycle</p>
                            <p class="font-semibold text-base-content">
                                @if($billingCycle === 'annual')
                                    Annual
                                    <span class="badge badge-sm bg-teal-500/10 text-teal-500 border-teal-500/20 ml-1">Save ~17%</span>
                                @else
                                    Monthly
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-base-content/60">Amount</p>
                            <p class="font-semibold text-base-content">
                                @if($billingCycle === 'annual')
                                    ${{ number_format((float) $displayPrice, 2) }}/year
                                    <span class="text-sm text-base-content/50">(${{ number_format((float) $displayPrice / 12, 2) }}/mo)</span>
                                @else
                                    ${{ number_format((float) $displayPrice, 2) }}/mo
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-base-content/60">Next Billing Date</p>
                            <p class="font-semibold text-base-content">
                                @if($nextBillingDate)
                                    {{ $nextBillingDate->format('M d, Y') }}
                                @else
                                    —
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-base-content/60">Apartment Limit</p>
                            <p class="font-semibold text-base-content">{{ $plan->apartment_limit === 0 ? 'Unlimited' : $plan->apartment_limit }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-base-content/60">Tenant Limit</p>
                            <p class="font-semibold text-base-content">{{ $plan->tenant_limit === 0 ? 'Unlimited' : $plan->tenant_limit }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Action Buttons --}}
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="/dashboard" class="rounded-full btn bg-teal-500 hover:bg-teal-600 text-white gap-2">
                <x-icon name="o-home" class="w-4 h-4" />
                Go to Dashboard
            </a>
            <a href="/subscription/invoices" class="btn rounded-full btn-outline gap-2">
                <x-icon name="o-document-text" class="w-4 h-4" />
                View Invoices
            </a>
            <a href="/subscription/pricing" class="btn rounded-full border border-teal-500 text-teal-500 hover:bg-teal-500 hover:text-white gap-2">
                <x-icon name="o-credit-card" class="w-4 h-4" />
                View Plans
            </a>
        </div>
    </div>
</div>
