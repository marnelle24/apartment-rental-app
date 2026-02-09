<?php

use Livewire\Component;
use App\Models\Plan;

new class extends Component
{
    public function with(): array
    {
        return [
            'plans' => Plan::activePlans(),
            'isLoggedIn' => auth()->check(),
            'isOwner' => auth()->check() && auth()->user()->isOwner(),
        ];
    }
};
?>

<div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 max-w-6xl mx-auto">
    @foreach($plans as $plan)
        @php
            $isFree = $plan->isFree();
            $isPopular = $plan->slug === 'professional';
            $isBusiness = $plan->slug === 'business';

            // Determine the CTA link based on auth status (register as owner when coming from pricing)
            if ($isOwner) {
                $ctaLink = '/subscription/pricing';
            } elseif ($isLoggedIn) {
                $ctaLink = '/subscription/pricing';
            } else {
                $ctaLink = route('register', ['usertype' => 'owner']);
            }
        @endphp

        {{-- Plan Card --}}
        <div class="{{ $isPopular ? 'relative bg-teal-700 rounded-2xl shadow-xl p-7 flex flex-col ring-2 ring-teal-400' : 'bg-white dark:bg-base-200 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-7 flex flex-col' }}">
            @if($isPopular)
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 bg-cyan-500 text-white text-xs font-semibold rounded-full shadow-md">Most Popular</div>
            @endif

            <h3 class="text-xl font-bold {{ $isPopular ? 'text-white mb-1' : ($plan->slug === 'starter' ? 'text-teal-600 mb-1' : 'text-slate-800 dark:text-slate-200 mb-1') }}">{{ $plan->name }}</h3>
            <p class="text-sm {{ $isPopular ? 'text-teal-200 mb-4' : 'text-slate-500 dark:text-slate-400 mb-4' }}">{{ $plan->short_description ?? 'Plan features' }}</p>

            @if($isFree)
                <p class="text-4xl font-bold {{ $isPopular ? 'text-white' : 'text-slate-900 dark:text-slate-100' }} mb-6">$0<span class="text-base font-normal {{ $isPopular ? 'text-teal-200' : 'text-slate-400' }}">/mo</span></p>
            @else
                <p class="text-4xl font-bold {{ $isPopular ? 'text-white' : 'text-slate-900 dark:text-slate-100' }} mb-1">
                    <span x-show="!annual">${{ number_format((float) $plan->price, 0) }}</span>
                    <span x-show="annual" x-cloak>${{ $plan->annual_price ? number_format((float) $plan->annual_price / 12, 2) : number_format((float) $plan->price, 0) }}</span>
                    <span class="text-base font-normal {{ $isPopular ? 'text-teal-200' : 'text-slate-400' }}">/mo</span>
                </p>
                <p class="text-xs {{ $isPopular ? 'text-teal-300 mb-6' : 'text-slate-400 mb-6' }}" x-show="annual" x-cloak>${{ $plan->annual_price ? number_format((float) $plan->annual_price, 0) : '' }} billed annually</p>
                <p class="text-xs {{ $isPopular ? 'text-teal-300 mb-6' : 'text-slate-400 mb-6' }}" x-show="!annual">&nbsp;</p>
            @endif

            <ul class="space-y-3 mb-8 flex-1 text-sm">
                @foreach($plan->features ?? [] as $feature)
                    <li class="flex items-start gap-2 {{ $isPopular ? 'text-teal-100' : 'text-slate-600 dark:text-slate-400' }}">
                        <x-icon name="o-check-circle" class="w-5 h-5 {{ $isPopular ? 'text-white shrink-0' : 'text-emerald-500 shrink-0' }}" />
                        {{ $feature }}
                    </li>
                @endforeach
            </ul>

            @if($isFree)
                <a href="{{ $ctaLink }}" class="block text-center py-3 rounded-full font-semibold border-2 border-slate-300 text-slate-700 hover:border-teal-500 hover:text-teal-600 transition-colors duration-200">
                    {{ $isLoggedIn ? 'View Plans' : 'Get Started Free' }}
                </a>
            @elseif($isBusiness)
                @if($isLoggedIn)
                    <a href="{{ $ctaLink }}" class="block text-center py-3 rounded-full font-semibold bg-slate-800 hover:bg-slate-900 text-white transition-colors duration-200">
                        View Plans
                    </a>
                @else
                    <a href="#contact" class="block text-center py-3 rounded-full font-semibold bg-slate-800 hover:bg-slate-900 text-white transition-colors duration-200">
                        Contact Sales
                    </a>
                @endif
            @elseif($isPopular)
                <a href="{{ $ctaLink }}" class="block text-center py-3 rounded-full font-semibold bg-white text-teal-700 hover:bg-teal-50 transition-colors duration-200">
                    {{ $isLoggedIn ? 'View Plans' : 'Get Started' }}
                </a>
            @else
                <a href="{{ $ctaLink }}" class="block text-center py-3 rounded-full font-semibold bg-teal-500 hover:bg-teal-600 text-white transition-colors duration-200">
                    {{ $isLoggedIn ? 'View Plans' : 'Get Started' }}
                </a>
            @endif
        </div>
    @endforeach
</div>
