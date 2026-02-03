<?php

use App\Models\RentPayment;
use Livewire\Component;
use Mary\Traits\Toast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;
use App\Traits\AuthorizesRole;

new class extends Component {
    use Toast;
    use WithPagination;
    use AuthorizesRole;

    public string $search = '';
    public int $tenant_id = 0;
    public int $apartment_id = 0;
    public string $status = '';
    public bool $drawer = false;

    public array $sortBy = ['column' => 'due_date', 'direction' => 'desc'];

    // Check owner access on mount
    public function mount(): void
    {
        $this->authorizeRole('owner');
    }

    // Clear filters
    public function clear(): void
    {
        $this->reset(['search', 'tenant_id', 'apartment_id', 'status']);
        $this->resetPage(); 
        $this->success('Filters cleared.', position: 'toast-bottom');
    }

    // Delete action
    public function delete(RentPayment $rentPayment): void
    {
        // Ensure owner can only delete payments for their own tenants
        if ($rentPayment->tenant->owner_id !== auth()->id()) {
            $this->error('Unauthorized access.', position: 'toast-bottom');
            return;
        }

        $rentPayment->delete();
        $this->warning("Payment #{$rentPayment->id} deleted", 'Good bye!', position: 'toast-bottom');
    }

    // Table headers
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'tenant_name', 'label' => 'Tenant', 'class' => 'w-48'],
            ['key' => 'apartment_name', 'label' => 'Apartment', 'class' => 'hidden lg:table-cell w-48'],
            ['key' => 'amount', 'label' => 'Amount', 'class' => 'w-32'],
            ['key' => 'due_date', 'label' => 'Due Date', 'class' => 'w-32'],
            ['key' => 'payment_date', 'label' => 'Payment Date', 'class' => 'hidden lg:table-cell w-32'],
            ['key' => 'status', 'label' => 'Status', 'class' => 'w-32'],
            ['key' => 'payment_method', 'label' => 'Method', 'class' => 'hidden md:table-cell w-32'],
        ];
    }

    public function rentPayments(): LengthAwarePaginator
    {
        return RentPayment::query()
            ->whereHas('tenant', fn(Builder $q) => $q->where('owner_id', auth()->id()))
            ->with(['tenant', 'apartment'])
            ->withAggregate('tenant', 'name')
            ->withAggregate('apartment', 'name')
            ->when($this->search, fn(Builder $q) => $q->where('reference_number', 'like', "%$this->search%")
                ->orWhereHas('tenant', fn(Builder $q) => $q->where('name', 'like', "%$this->search%"))
                ->orWhereHas('apartment', fn(Builder $q) => $q->where('name', 'like', "%$this->search%")))
            ->when($this->tenant_id, fn(Builder $q) => $q->where('tenant_id', $this->tenant_id))
            ->when($this->apartment_id, fn(Builder $q) => $q->where('apartment_id', $this->apartment_id))
            ->when($this->status, fn(Builder $q) => $q->where('status', $this->status))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(10);
    }

    public function with(): array
    {
        return [
            'rentPayments' => $this->rentPayments(),
            'headers' => $this->headers(),
            'tenants' => \App\Models\Tenant::where('owner_id', auth()->id())->get(),
            'apartments' => \App\Models\Apartment::where('owner_id', auth()->id())->get(),
            'statuses' => [
                ['id' => 'pending', 'name' => 'Pending'],
                ['id' => 'paid', 'name' => 'Paid'],
                ['id' => 'overdue', 'name' => 'Overdue'],
            ],
        ];
    }

    // Reset pagination when any component property changes
    public function updated($property): void
    {
        if (! is_array($property) && $property != "") {
            $this->resetPage();
        }
    }

}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Rent Payments" separator progress-indicator>
        <x-slot:middle class="justify-end!">
            <x-input placeholder="Search..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Create" link="/rent-payments/create" icon="o-plus" class="bg-teal-500 text-white" responsive />
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel" class="border border-gray-400 text-gray-500 dark:text-gray-400 dark:hover:bg-gray-200/30" />
        </x-slot:actions>
    </x-header>

    <!-- TABLE  -->
    <x-card class="border border-base-content/10" shadow>
        <x-table 
            :headers="$headers" 
            :rows="$rentPayments" 
            :sort-by="$sortBy" 
            with-pagination
            class="bg-base-100"
            link="rent-payments/{id}/edit"
        >
            @scope('cell_tenant_name', $payment)
                <div class="font-semibold">
                    {{ $payment['tenant_name'] ?? '—' }}
                </div>
            @endscope

            @scope('cell_apartment_name', $payment)
                <div class="text-sm">
                    {{ $payment['apartment_name'] ?? '—' }}
                </div>
            @endscope

            @scope('cell_amount', $payment)
                <div class="font-semibold">
                    ₱{{ number_format($payment['amount'], 2) }}
                </div>
            @endscope

            @scope('cell_due_date', $payment)
                @php
                    $dueDate = \Carbon\Carbon::parse($payment['due_date']);
                    $isOverdue = $dueDate->isPast() && $payment['status'] !== 'paid';
                @endphp
                <div class="text-sm {{ $isOverdue ? 'text-error font-semibold' : '' }}">
                    {{ $dueDate->format('M d, Y') }}
                    @if($isOverdue)
                        <span class="badge badge-error badge-xs ml-1">Overdue</span>
                    @endif
                </div>
            @endscope

            @scope('cell_payment_date', $payment)
                @if($payment['payment_date'])
                    <div class="text-sm">
                        {{ \Carbon\Carbon::parse($payment['payment_date'])->format('M d, Y') }}
                    </div>
                @else
                    <div class="text-sm text-base-content/50">—</div>
                @endif
            @endscope

            @scope('cell_status', $payment)
                @php
                    $statusColors = [
                        'pending' => 'badge-warning',
                        'paid' => 'badge-success',
                        'overdue' => 'badge-error',
                    ];
                    $color = $statusColors[$payment['status']] ?? 'badge-ghost';
                @endphp
                <div class="badge {{ $color }}">
                    {{ ucfirst($payment['status']) }}
                </div>
            @endscope

            @scope('cell_payment_method', $payment)
                <div class="text-sm">
                    {{ $payment['payment_method'] ?? '—' }}
                </div>
            @endscope

            @scope('actions', $payment)
                <x-button icon="o-trash" wire:click="delete({{ $payment['id'] }})" wire:confirm="Are you sure?" spinner class="btn-ghost btn-sm text-error" />
            @endscope
        </x-table>
    </x-card>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <div class="grid gap-5"> 
            <x-input placeholder="Search..." wire:model.live.debounce="search" icon="o-magnifying-glass" /> 
            <x-select placeholder="Tenant" wire:model.live="tenant_id" :options="$tenants" placeholder-value="0" /> 
            <x-select placeholder="Apartment" wire:model.live="apartment_id" :options="$apartments" placeholder-value="0" /> 
            <x-select placeholder="Status" wire:model.live="status" :options="$statuses" placeholder-value="" /> 
        </div>
        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>
</div>
