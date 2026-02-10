<?php

use Livewire\Component;
use App\Traits\AuthorizesRole;
use App\Models\Tenant;
use App\Models\RentPayment;
use App\Models\Task;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    use AuthorizesRole;

    public function render()
    {
        return view('pages.portal.⚡dashboard')->layout('layouts.portal');
    }

    public function mount(): void
    {
        $this->authorizeRole('tenant');
    }

    public function getTenantRecordsProperty()
    {
        return Tenant::where('user_id', auth()->id())
            ->with(['apartment', 'rentPayments', 'tasks'])
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderByDesc('lease_end_date')
            ->get();
    }

    /** Current tenant (first active, or first by lease_end_date) */
    public function getCurrentTenantProperty(): ?Tenant
    {
        $tenants = $this->tenantRecords;
        $active = $tenants->where('status', 'active')->first();
        return $active ?? $tenants->first();
    }

    /**
     * Recent activities for the current month: notifications, task requests, payment dues/paid, lease expiry.
     * Sorted by date descending (most recent first).
     */
    public function getRecentActivitiesProperty(): Collection
    {
        $tenantIds = $this->tenantRecords->pluck('id');
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $activities = collect();

        // Notifications (current month)
        Notification::where('user_id', auth()->id())
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->orderByDesc('created_at')
            ->get()
            ->each(fn ($n) => $activities->push((object)[
                'type' => 'notification',
                'title' => $n->title,
                'subtitle' => $n->message,
                'date' => $n->created_at,
                'icon' => 'o-bell-alert',
                'color' => $n->read_at ? 'text-base-content/60' : 'text-primary',
                'link' => '/portal/notifications',
            ]));

        // Task requests (created or updated this month)
        if ($tenantIds->isNotEmpty()) {
            Task::whereIn('tenant_id', $tenantIds)
                ->with('apartment')
                ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                    $q->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                        ->orWhereBetween('updated_at', [$startOfMonth, $endOfMonth]);
                })
                ->orderByDesc(DB::raw('GREATEST(created_at, updated_at)'))
                ->get()
                ->each(function ($t) use (&$activities) {
                    $date = $t->updated_at->gt($t->created_at) ? $t->updated_at : $t->created_at;
                    $activities->push((object)[
                        'type' => 'task',
                        'title' => $t->title,
                        'subtitle' => ($t->apartment->name ?? '—') . ' · ' . ucfirst($t->status),
                        'date' => $date,
                        'icon' => 'o-clipboard-document-list',
                        'color' => in_array($t->status, ['done', 'cancelled']) ? 'text-base-content/60' : 'text-warning',
                        'link' => '/portal/notifications',
                    ]);
                });
        }

        // Payment dues (due_date in current month, pending/overdue)
        if ($tenantIds->isNotEmpty()) {
            RentPayment::whereIn('tenant_id', $tenantIds)
                ->with('apartment')
                ->whereBetween('due_date', [$startOfMonth, $endOfMonth])
                ->whereIn('status', ['pending', 'overdue'])
                ->orderBy('due_date')
                ->get()
                ->each(fn ($p) => $activities->push((object)[
                    'type' => 'payment_due',
                    'title' => $p->status === 'overdue' ? 'Overdue payment' : 'Payment due',
                    'subtitle' => currency_symbol($p->apartment->currency ?? 'PHP') . number_format($p->amount, 0) . ' · ' . ($p->apartment->name ?? '—'),
                    'date' => $p->due_date,
                    'icon' => $p->status === 'overdue' ? 'o-exclamation-triangle' : 'o-banknotes',
                    'color' => $p->status === 'overdue' ? 'text-error' : 'text-warning',
                    'link' => null,
                ]));
        }

        // Payments paid (payment_date in current month)
        if ($tenantIds->isNotEmpty()) {
            RentPayment::whereIn('tenant_id', $tenantIds)
                ->with('apartment')
                ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
                ->where('status', 'paid')
                ->orderByDesc('payment_date')
                ->get()
                ->each(fn ($p) => $activities->push((object)[
                    'type' => 'payment_paid',
                    'title' => 'Payment received',
                    'subtitle' => currency_symbol($p->apartment->currency ?? 'PHP') . number_format($p->amount, 0) . ' · ' . ($p->apartment->name ?? '—'),
                    'date' => $p->payment_date,
                    'icon' => 'o-check-circle',
                    'color' => 'text-success',
                    'link' => null,
                ]));
        }

        // Lease expiry (lease_end_date in current month)
        $this->tenantRecords
            ->filter(fn ($t) => $t->lease_end_date && $t->lease_end_date->between($startOfMonth, $endOfMonth))
            ->sortByDesc('lease_end_date')
            ->each(fn ($t) => $activities->push((object)[
                'type' => 'lease_expiry',
                'title' => 'Lease ends',
                'subtitle' => ($t->apartment->name ?? '—') . ' · ' . $t->lease_end_date->format('M j, Y'),
                'date' => $t->lease_end_date,
                'icon' => 'o-calendar',
                'color' => 'text-warning',
                'link' => '/portal/apartments',
            ]));

        return $activities->sortByDesc(fn ($a) => $a->date->format('Y-m-d H:i:s'))->take(20)->values();
    }

    public function getCurrentApartmentProperty(): ?Tenant
    {
        return $this->tenantRecords
            ->filter(fn ($t) => Carbon::today()->between($t->lease_start_date, $t->lease_end_date))
            ->first();
    }

    /** Next rent due date for current apartment (same day of month as lease_start_date, on or after today). */
    public function getCurrentApartmentMonthlyDueDateProperty(): ?Carbon
    {
        return Carbon::now()->startOfMonth()->day($this->currentApartment->lease_start_date->day);
    }

    public function getNextRentDueDateProperty(): ?Carbon
    {
        return Carbon::now()->startOfMonth()->day($this->currentApartment->lease_start_date->day)->addMonth();
    }

    public function getCurrentApartCurrentMonthPaymentStatus(): string
    {
        if ($this->currentApartment->rentPayments->where('due_date', $this->currentApartmentMonthlyDueDate)->where('status', 'paid')->first()) {
            return 'Paid';
        }
        if ($this->currentApartment->rentPayments->where('due_date', $this->currentApartmentMonthlyDueDate)->where('status', 'overdue')->first()) {
            return 'Overdue';
        }
        return 'Pending';
    }

}; ?>

<div>
    {{-- <x-header title="Dashboard" separator class="mb-4" /> --}}

    <h3 class="text-xl font-bold mb-8 mt-4">Hello! <span class="text-teal-500">{{ auth()->user()->name }}</span></h3>

    <!-- QUICK STATS CARDS -->

    @if($this->tenantRecords->isEmpty())
        <x-card class="bg-base-100 border border-base-content/10">
            <p class="text-base-content/80">You're not linked to a lease yet. Ask your landlord to link your account to your tenant record, or check back later.</p>
            <p class="mt-2 text-sm text-base-content/60">You can still update your profile and view notifications from the menu.</p>
        </x-card>
    @else
        <div class="space-y-4 px-4">

            @if($this->currentApartment && $this->currentApartment->apartment)
                <x-card class="border border-teal-600 bg-teal-100/10 shadow">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm text-base-content">Currently staying at...</p>
                        @if($this->getCurrentApartCurrentMonthPaymentStatus() === 'Paid')
                            <span class="badge badge-sm badge-success bg-opacity-10 text-opacity-100">
                                {{ $this->getCurrentApartCurrentMonthPaymentStatus() }}
                            </span>
                        @elseif($this->getCurrentApartCurrentMonthPaymentStatus() === 'Overdue')
                            <span class="badge badge-sm badge-error bg-opacity-10 text-opacity-100">
                                {{ $this->getCurrentApartCurrentMonthPaymentStatus() }}
                            </span>
                        @else
                            <span class="badge badge-sm badge-warning bg-opacity-10 text-opacity-100">
                                {{ $this->getCurrentApartCurrentMonthPaymentStatus() }}
                            </span>
                        @endif
                    </div>
                    <p class="font-semibold text-2xl">{{ $this->currentApartment->apartment->name ?? '—' }}</p>
                    @if($this->currentApartment->apartment->address)
                        <p class="text-xs text-base-content/60">{{ $this->currentApartment->apartment->address }}</p>
                    @endif
                    @if($this->currentApartment->apartment->unit_number)
                        <p class="mb-4 text-xs text-base-content/60">{{ $this->currentApartment->apartment->unit_number }}</p>
                    @endif

                    {{-- add the Due date of the lease --}}
                    <div>
                        <p class="text-sm text-base-content/70 text-[10px]">Monthly Rent:</p>
                        <p class="text-md text-base-content/60 font-semibold ml-2">{{ currency_symbol($this->currentApartment->apartment->currency ?? 'PHP') }}{{ number_format($this->currentApartment->monthly_rent, 2) }}</p>
                    </div>
                    @if($this->CurrentApartmentMonthlyDueDate)
                        <div class="mt-2">
                            <p class="text-sm text-base-content/70 text-[10px]">Due Date:</p>
                            <p class="text-md text-base-content/60 font-semibold ml-2">Every {{ $this->currentApartmentMonthlyDueDate->format('jS') }} of the month</p>
                        </div>
                    @endif
                    @if($this->nextRentDueDate)
                        <div class="mt-2">
                            <p class="text-sm text-base-content/70 text-[10px]">Next Due Date:</p>
                            <p class="text-md text-base-content/60 font-semibold ml-2">{{ $this->nextRentDueDate->format('M d, Y') }}</p>
                        </div>
                    @endif
                </x-card>
            @endif

            {{-- Recent Activities (current month) --}}
            <div class="flex items-center justify-between mt-8 mb-4">
                <h4 class="font-semibold text-base-content">Recent activities</h4>
                <span class="text-xs text-base-content/60">{{ now()->format('F Y') }}</span>
            </div>
            <x-card class="bg-base-100 border border-base-content/10">
                @if($this->recentActivities->isEmpty())
                    <p class="text-sm text-base-content/60 py-4">No activities this month yet.</p>
                @else
                    <ul class="space-y-3">
                        @foreach($this->recentActivities as $activity)
                            <li class="flex gap-3 items-start py-2 border-b border-base-content/30 last:border-0 last:pb-0">
                                <span class="{{ $activity->color }} mt-0.5 shrink-0">
                                    <x-icon :name="$activity->icon" class="w-5 h-5" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium capitalize text-base-content">{{ $activity->title }}</p>
                                    <p class="text-sm text-base-content/60 truncate">{{ $activity->subtitle }}</p>
                                    <p class="text-[10px] text-base-content/50 mt-0.5">{{ $activity->date->format('M j, g:i A') }}</p>
                                </div>
                                @if($activity->link)
                                    <a href="{{ $activity->link }}" wire:navigate class="btn btn-ghost btn-xs shrink-0">View</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <a href="/portal/notifications" wire:navigate class="btn btn-sm btn-ghost border border-base-content/30 mt-4 w-full">View all notifications</a>
                @endif
            </x-card>
        </div>
    @endif
</div>
