<?php

use Livewire\Component;
use App\Traits\AuthorizesRole;
use App\Models\Tenant;

new class extends Component
{
    use AuthorizesRole;

    public function render()
    {
        return view('pages.portal.⚡apartments')->layout('layouts.portal');
    }

    public function mount(): void
    {
        $this->authorizeRole('tenant');
    }

    public function getTenantRecordsProperty()
    {
        $tenants = Tenant::where('user_id', auth()->id())
            ->with('apartment')
            ->get();

        return $tenants->sortBy(function (Tenant $tenant) {
            $today = now()->startOfDay();
            $isPast = $tenant->lease_end_date && $tenant->lease_end_date->startOfDay()->lt($today);
            $isUpcoming = $tenant->lease_start_date && $tenant->lease_start_date->startOfDay()->gt($today);
            if ($isPast) return 2;      // Previous – last
            if ($isUpcoming) return 1;   // Upcoming – middle
            return 0;                   // Current – first
        })->values();
    }

}; ?>

<div>
    {{-- <x-header title="My Apartments" separator class="mb-4" /> --}}

    @if($this->tenantRecords->isEmpty())
        <x-card class="bg-base-100 border border-base-content/10">
            <p class="text-base-content/80">You're not linked to any lease yet. Ask your landlord to link your account to your tenant record.</p>
        </x-card>
    @else
        <h3 class="text-xl font-bold mb-8 mt-4 px-4">My Apartments</h3>
        <div class="space-y-4 px-4 mt-8">
            @foreach($this->tenantRecords as $tenant)
                @php
                    // create a variables for isPast, isFuture, isCurrent
                    $isPast = $tenant->lease_end_date && $tenant->lease_end_date->isPast();
                    $isFuture = $tenant->lease_start_date && $tenant->lease_start_date->isFuture();
                    $isCurrent = !$isPast && !$isFuture;
                @endphp
                <x-card class="bg-base-100 border border-base-content/10">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            @if($isPast)
                                <span class="badge badge-sm border dark:border-white border-base-content/40 mb-1">Previous</span>
                            @elseif($isFuture)
                                <span class="badge badge-sm badge-warning mb-1">Upcoming</span>
                            @else
                                <span class="badge badge-sm badge-success opacity-90 border-success mb-1">Current</span>
                            @endif
                            
                            <span class="ml-1 badge badge-sm {{ $tenant->status === 'active' ? 'border border-success text-success' : 'badge-ghost border border-base-content/30 text-base-content/50' }} mb-1 capitalize">{{ $tenant->status }}</span>
                            
                            <h3 class="font-semibold text-base">{{ $tenant->apartment->name ?? '—' }}</h3>
                            @if($tenant->apartment->unit_number)
                                <p class="text-sm text-base-content/70">Unit {{ $tenant->apartment->unit_number }}</p>
                            @endif
                            @if($tenant->apartment->address)
                                <p class="text-xs text-base-content/60 mt-0.5">{{ $tenant->apartment->address }}</p>
                            @endif
                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-base-content/70">
                                @if($tenant->lease_start_date)
                                    <span>Lease: {{ $tenant->lease_start_date->format('M j, Y') }}</span>
                                @endif
                                @if($tenant->lease_end_date)
                                    <span>– {{ $tenant->lease_end_date->format('M j, Y') }}</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm font-medium">{{ currency_symbol($tenant->apartment->currency ?? 'PHP') }}{{ number_format($tenant->monthly_rent, 2) }} <span class="text-base-content/60 font-normal">/ month</span></p>
                        </div>
                        <x-icon name="o-building-office" class="w-8 h-8 text-base-content/20 shrink-0" />
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</div>
