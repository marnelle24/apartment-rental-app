<?php

use Livewire\Component;
use Mary\Traits\Toast;
use App\Models\Plan;
use App\Traits\AuthorizesRole;

new class extends Component
{
    use Toast;
    use AuthorizesRole;

    public string $billingCycle = 'monthly';
    public bool $showCancelModal = false;

    public function mount(): void
    {
        $this->authorizeRole('owner');
    }

    public function cancelSubscription(): void
    {
        $user = auth()->user();

        if (! $user->subscribed('default')) {
            $this->error('You do not have an active subscription.', position: 'toast-bottom');
            return;
        }

        // Cancel at end of billing period (grace period)
        $user->subscription('default')->cancel();
        $this->showCancelModal = false;
        $this->success('Your subscription has been cancelled. You will retain access until the end of your current billing period.', position: 'toast-bottom');
    }

    public function resumeSubscription(): void
    {
        $user = auth()->user();

        if (! $user->subscription('default')?->onGracePeriod()) {
            $this->error('No subscription to resume.', position: 'toast-bottom');
            return;
        }

        $user->subscription('default')->resume();
        $this->success('Your subscription has been resumed!', position: 'toast-bottom');
    }

    public function with(): array
    {
        $user = auth()->user();
        $currentPlan = $user->getEffectivePlan();
        $plans = Plan::activePlans();
        $isSubscribed = $user->subscribed('default');
        $subscription = $isSubscribed ? $user->subscription('default') : null;

        // Subscription details
        $subscriptionStatus = null;
        $onGracePeriod = false;
        $endsAt = null;

        if ($subscription) {
            $onGracePeriod = $subscription->onGracePeriod();
            $endsAt = $subscription->ends_at;

            if ($onGracePeriod) {
                $subscriptionStatus = 'cancelling';
            } elseif ($subscription->active()) {
                $subscriptionStatus = 'active';
            }
        } elseif ($currentPlan?->isFree()) {
            $subscriptionStatus = 'free';
        }

        // Usage stats
        $apartmentCount = $user->apartments()->count();
        $tenantCount = $user->tenants()->count();

        return [
            'plans' => $plans,
            'currentPlan' => $currentPlan,
            'isSubscribed' => $isSubscribed,
            'subscriptionStatus' => $subscriptionStatus,
            'onGracePeriod' => $onGracePeriod,
            'endsAt' => $endsAt,
            'apartmentCount' => $apartmentCount,
            'tenantCount' => $tenantCount,
        ];
    }
};
?>

<div>
    <x-header title="Subscription Plans" subtitle="Choose the plan that fits your property management needs" separator />

    @if(session('success'))
        <div class="alert alert-success mb-6">
            <x-icon name="o-check-circle" class="w-5 h-5" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error mb-6">
            <x-icon name="o-x-circle" class="w-5 h-5 text-white" />
            <span class="text-white">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Current Subscription Status Card --}}
    @if($currentPlan)
        <div class="max-w-3xl mx-auto mb-8">
            <x-card class="bg-base-100 border border-base-content/10" shadow>
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="p-3 rounded-xl bg-teal-500/10">
                            <x-icon name="o-credit-card" class="w-7 h-7 text-teal-500" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-base-content">{{ $currentPlan->name }} Plan</span>
                                @if($subscriptionStatus === 'active')
                                    <span class="badge badge-sm badge-success gap-1"><x-icon name="o-check" class="w-3 h-3" /> Active</span>
                                @elseif($subscriptionStatus === 'cancelling')
                                    <span class="badge badge-sm badge-warning gap-1"><x-icon name="o-clock" class="w-3 h-3" /> Cancelling</span>
                                @elseif($subscriptionStatus === 'free')
                                    <span class="badge badge-sm badge-ghost">Free Tier</span>
                                @endif
                            </div>
                            <div class="text-sm text-base-content/60 mt-0.5">
                                @if($subscriptionStatus === 'cancelling' && $endsAt)
                                    Access until {{ \Carbon\Carbon::parse($endsAt)->format('M d, Y') }}
                                @elseif(!$currentPlan->isFree())
                                    Using {{ $apartmentCount }} of {{ $currentPlan->apartment_limit === 0 ? '∞' : $currentPlan->apartment_limit }} apartments,
                                    {{ $tenantCount }} of {{ $currentPlan->tenant_limit === 0 ? '∞' : $currentPlan->tenant_limit }} tenants
                                @else
                                    Using {{ $apartmentCount }} of {{ $currentPlan->apartment_limit }} apartments, {{ $tenantCount }} of {{ $currentPlan->tenant_limit }} tenants
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($isSubscribed && !$onGracePeriod)
                            <a href="/subscription/portal" class="p-4 text-md btn border border-gray-400 text-gray-600 hover:bg-gray-200 hover:text-gray-800 rounded-full btn-sm gap-1">
                                <x-icon name="o-credit-card" class="w-4 h-4" />
                                Billing
                            </a>
                            <x-button 
                                label="Cancel" 
                                icon="o-x-mark" 
                                @click="$wire.showCancelModal = true"
                                class="border border-error btn-sm rounded-full text-error hover:bg-error/10" 
                            />
                        @elseif($onGracePeriod)
                            <x-button 
                                label="Resume Subscription" 
                                icon="o-arrow-path" 
                                wire:click="resumeSubscription"
                                spinner="resumeSubscription"
                                class="rounded-full btn-md bg-teal-500 hover:bg-teal-600 text-white" 
                            />
                        @endif
                    </div>
                </div>
            </x-card>
        </div>
    @endif

    {{-- Billing Cycle Toggle --}}
    <div class="flex justify-center mb-8">
        <div class="bg-base-100 border border-base-content/10 rounded-full p-1 inline-flex shadow-sm">
            <button 
                wire:click="$set('billingCycle', 'monthly')"
                class="px-6 py-2 rounded-full text-sm font-medium transition-all duration-200 {{ $billingCycle === 'monthly' ? 'bg-teal-500 text-white shadow' : 'text-base-content/70 hover:text-base-content' }}"
            >
                Monthly
            </button>
            <button 
                wire:click="$set('billingCycle', 'annual')"
                class="px-6 py-2 rounded-full text-sm font-medium transition-all duration-200 {{ $billingCycle === 'annual' ? 'bg-teal-500 text-white shadow' : 'text-base-content/70 hover:text-base-content' }}"
            >
                Annual <span class="ml-1 text-xs {{ $billingCycle === 'annual' ? 'text-teal-100' : 'text-teal-500' }}">Save ~17%</span>
            </button>
        </div>
    </div>

    {{-- Plan Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 max-w-7xl mx-auto">
        @foreach($plans as $plan)
            @php
                $isCurrentPlan = $currentPlan && $currentPlan->id === $plan->id;
                $isFree = $plan->isFree();
                $isPopular = $plan->slug === 'professional';
                $price = $billingCycle === 'annual' && $plan->annual_price 
                    ? number_format((float) $plan->annual_price / 12, 2) 
                    : number_format((float) $plan->price, 2);
                $totalAnnual = $plan->annual_price ? number_format((float) $plan->annual_price, 2) : null;
            @endphp
            <div class="card bg-base-100 border {{ $isPopular ? 'border-teal-500 border-2 shadow-lg shadow-teal-500/10' : 'border-base-content/10' }} {{ $isCurrentPlan ? 'ring-2 ring-teal-400/50' : '' }} relative overflow-hidden">
                {{-- Popular Badge --}}
                @if($isPopular)
                    <div class="absolute top-0 right-0">
                        <div class="bg-teal-500 text-white text-xs font-bold px-3 py-1 rounded-bl-lg">
                            POPULAR
                        </div>
                    </div>
                @endif

                {{-- Current Badge --}}
                @if($isCurrentPlan)
                    <div class="absolute top-0 left-0">
                        <div class="bg-teal-600 text-white text-xs font-bold px-3 py-1 rounded-br-lg">
                            CURRENT
                        </div>
                    </div>
                @endif

                <div class="card-body p-6">
                    {{-- Plan Name --}}
                    <h3 class="text-xl font-bold text-base-content">{{ $plan->name }}</h3>
                    @if($plan->short_description)
                        <p class="text-sm text-base-content/60 mt-1">{{ $plan->short_description }}</p>
                    @endif

                    {{-- Price --}}
                    <div class="my-4">
                        @if($isFree)
                            <div class="flex items-baseline gap-1">
                                <span class="text-4xl font-bold text-base-content">$0</span>
                                <span class="text-base-content/60">/forever</span>
                            </div>
                        @else
                            <div class="flex items-baseline gap-1">
                                <span class="text-4xl font-bold text-base-content">${{ $price }}</span>
                                <span class="text-base-content/60">/mo</span>
                            </div>
                            @if($billingCycle === 'annual' && $totalAnnual)
                                <p class="text-sm text-base-content/50 mt-1">${{ $totalAnnual }} billed annually</p>
                            @endif
                        @endif
                    </div>

                    {{-- Limits --}}
                    <div class="flex gap-3 mb-4">
                        <div class="badge badge-ghost border border-base-content/10 p-3 text-xs">
                            <x-icon name="o-building-office" class="w-4 h-4 mr-1" />
                            {{ $plan->apartment_limit === 0 ? 'Unlimited' : $plan->apartment_limit }} {{ $plan->name !== 'Business' ? Str::plural('unit', $plan->apartment_limit === 0 ? 2 : $plan->apartment_limit) : '' }}
                        </div>
                        <div class="badge badge-ghost border border-base-content/10 p-3 text-xs">
                            <x-icon name="o-users" class="w-4 h-4 mr-1" />
                            {{ $plan->tenant_limit === 0 ? 'Unlimited' : $plan->tenant_limit }} {{ $plan->name !== 'Business' ? Str::plural('tenant', $plan->tenant_limit === 0 ? 2 : $plan->tenant_limit) : '' }}
                        </div>
                    </div>

                    {{-- Features --}}
                    <ul class="space-y-2 mb-6 grow">
                        @foreach($plan->features ?? [] as $feature)
                            <li class="flex items-start gap-2 text-sm">
                                <x-icon name="o-check" class="w-4 h-4 text-teal-500 mt-0.5 shrink-0" />
                                <span class="text-base-content/80">{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>

                    {{-- Action Button --}}
                    <div class="card-actions mt-auto">
                        @if($isCurrentPlan)
                            <button class="btn rounded-full btn-disabled w-full" disabled>
                                Current Plan
                            </button>
                        @elseif($isFree)
                            <form action="/subscription/checkout" method="POST" class="w-full">
                                @csrf
                                <input type="hidden" name="plan" value="{{ $plan->slug }}">
                                <button type="submit" class="btn rounded-full btn-outline w-full">
                                    Switch to Free
                                </button>
                            </form>
                        @elseif($isSubscribed)
                            <form action="/subscription/swap" method="POST" class="w-full">
                                @csrf
                                <input type="hidden" name="plan" value="{{ $plan->slug }}">
                                <input type="hidden" name="billing_cycle" value="{{ $billingCycle }}">
                                <button type="submit" class="btn rounded-full {{ $isPopular ? 'bg-teal-500 hover:bg-teal-600 text-white' : 'btn-outline border-teal-500 text-teal-500 hover:bg-teal-500 hover:text-white' }} w-full">
                                    Switch to {{ $plan->name }}
                                </button>
                            </form>
                        @else
                            <form action="/subscription/checkout" method="POST" class="w-full">
                                @csrf
                                <input type="hidden" name="plan" value="{{ $plan->slug }}">
                                <input type="hidden" name="billing_cycle" value="{{ $billingCycle }}">
                                <button type="submit" class="btn rounded-full {{ $isPopular ? 'bg-teal-500 hover:bg-teal-600 text-white' : 'btn-outline border-teal-500 text-teal-500 hover:bg-teal-500 hover:text-white' }} w-full">
                                    {{ $isFree ? 'Get Started' : 'Subscribe' }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Billing Portal Link --}}
    @if($isSubscribed)
        <div class="mt-14 text-center">
            <p class="text-base-content/60 mb-3">Need to update your payment method or view invoices?</p>
            <div class="flex justify-center gap-3">
                <a href="/subscription/invoices" class="btn btn-outline btn-sm gap-2">
                    <x-icon name="o-document-text" class="w-4 h-4" />
                    Invoice History
                </a>
                <a href="/subscription/portal" class="btn btn-outline btn-sm gap-2">
                    <x-icon name="o-credit-card" class="w-4 h-4" />
                    Manage Billing
                </a>
            </div>
        </div>
    @endif

    {{-- Cancel Subscription Modal --}}
    <x-modal wire:model="showCancelModal" title="Cancel Subscription" separator>
        <div class="space-y-4">
            <div class="flex items-center gap-3 p-4 rounded-lg bg-warning/10 border border-warning/20">
                <x-icon name="o-exclamation-triangle" class="w-8 h-8 text-warning shrink-0" />
                <div>
                    <p class="font-semibold text-base-content">Are you sure you want to cancel?</p>
                    <p class="text-sm text-base-content/70 mt-1">Your subscription will remain active until the end of your current billing period. After that, you'll be moved to the Free plan.</p>
                </div>
            </div>
            <div class="text-sm text-base-content/60 space-y-2">
                <p>When your subscription ends:</p>
                <ul class="list-disc list-inside space-y-1 ml-2">
                    <li class="text-xs">Your plan will revert to Free (1 apartment, 1 tenant)</li>
                    <li class="text-xs">If you exceed the Free plan limits, you won't be able to add new records</li>
                    <li class="text-xs">Your existing data will remain intact</li>
                    <li class="text-xs">You can re-subscribe at any time</li>
                </ul>
            </div>
        </div>
        <x-slot:actions>
            <x-button label="Keep Subscription" @click="$wire.showCancelModal = false" />
            <x-button 
                label="Yes, Cancel" 
                icon="o-x-mark"
                wire:click="cancelSubscription" 
                spinner="cancelSubscription"
                class="btn-error text-white" 
            />
        </x-slot:actions>
    </x-modal>

    {{-- FAQ Section --}}
    <div class="max-w-3xl mx-auto mt-16">
        <h2 class="text-2xl font-bold text-center text-base-content mb-8">Frequently Asked Questions</h2>
        <div class="space-y-4">
            <div class="collapse collapse-arrow bg-base-100 border border-base-content/10">
                <input type="radio" name="faq-accordion" checked="checked" />
                <div class="collapse-title text-base font-medium">Can I change my plan later?</div>
                <div class="collapse-content">
                    <p class="text-base-content/70">Yes! You can upgrade or downgrade your plan at any time. When upgrading, you'll be charged the prorated difference. When downgrading, the credit will be applied to your next billing cycle.</p>
                </div>
            </div>
            <div class="collapse collapse-arrow bg-base-100 border border-base-content/10">
                <input type="radio" name="faq-accordion" />
                <div class="collapse-title text-base font-medium">What happens when I reach my plan limit?</div>
                <div class="collapse-content">
                    <p class="text-base-content/70">You won't be able to add new apartments or tenants beyond your plan's limit. You can upgrade to a higher plan at any time to increase your limits.</p>
                </div>
            </div>
            <div class="collapse collapse-arrow bg-base-100 border border-base-content/10">
                <input type="radio" name="faq-accordion" />
                <div class="collapse-title text-base font-medium">Can I cancel my subscription?</div>
                <div class="collapse-content">
                    <p class="text-base-content/70">Yes, you can cancel at any time. Your subscription will remain active until the end of the current billing period, after which you'll be moved to the Free plan.</p>
                </div>
            </div>
            <div class="collapse collapse-arrow bg-base-100 border border-base-content/10">
                <input type="radio" name="faq-accordion" />
                <div class="collapse-title text-base font-medium">Is there a free trial?</div>
                <div class="collapse-content">
                    <p class="text-base-content/70">The Free plan lets you manage up to 3 apartments and 3 tenants with no time limit. This way you can try Rentory before committing to a paid plan.</p>
                </div>
            </div>
        </div>
    </div>
</div>
