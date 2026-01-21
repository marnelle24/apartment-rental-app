<?php

use App\Models\Tenant;
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
    public int $apartment_id = 0;
    public string $status = '';
    public bool $drawer = false;

    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];

    // Check owner access on mount
    public function mount(): void
    {
        $this->authorizeRole('owner');
    }

    // Clear filters
    public function clear(): void
    {
        $this->reset(['search', 'apartment_id', 'status']);
        $this->resetPage(); 
        $this->success('Filters cleared.', position: 'toast-bottom');
    }

    // Delete action
    public function delete(Tenant $tenant): void
    {
        // Ensure owner can only delete their own tenants
        if ($tenant->owner_id !== auth()->id()) {
            $this->error('Unauthorized access.', position: 'toast-bottom');
            return;
        }

        $tenant->delete();
        $this->warning("$tenant->name deleted", 'Good bye!', position: 'toast-bottom');
    }

    // Table headers
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'name', 'label' => 'Name', 'class' => 'w-64'],
            ['key' => 'apartment_name', 'label' => 'Apartment', 'class' => 'hidden lg:table-cell'],
            ['key' => 'email', 'label' => 'Email', 'class' => 'hidden lg:table-cell'],
            ['key' => 'phone', 'label' => 'Phone', 'class' => 'hidden md:table-cell'],
            ['key' => 'monthly_rent', 'label' => 'Monthly Rent', 'class' => 'w-32'],
            ['key' => 'lease_end_date', 'label' => 'Lease End', 'class' => 'hidden lg:table-cell'],
            ['key' => 'status', 'label' => 'Status', 'class' => 'w-32'],
        ];
    }

    public function tenants(): LengthAwarePaginator
    {
        return Tenant::query()
            ->where('owner_id', auth()->id())
            ->with(['apartment'])
            ->withAggregate('apartment', 'name')
            ->when($this->search, fn(Builder $q) => $q->where('name', 'like', "%$this->search%")
                ->orWhere('email', 'like', "%$this->search%")
                ->orWhere('phone', 'like', "%$this->search%"))
            ->when($this->apartment_id, fn(Builder $q) => $q->where('apartment_id', $this->apartment_id))
            ->when($this->status, fn(Builder $q) => $q->where('status', $this->status))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(10);
    }

    public function with(): array
    {
        return [
            'tenants' => $this->tenants(),
            'headers' => $this->headers(),
            'apartments' => \App\Models\Apartment::where('owner_id', auth()->id())->get(),
            'statuses' => [
                ['id' => 'active', 'name' => 'Active'],
                ['id' => 'inactive', 'name' => 'Inactive'],
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
    <x-header title="Tenants" separator progress-indicator>
        <x-slot:middle class="justify-end!">
            <x-input placeholder="Search..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Create" link="/tenants/create" icon="o-plus" class="btn-primary" responsive />
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel" />
        </x-slot:actions>
    </x-header>

    <!-- TABLE  -->
    <x-card shadow>
        <x-table 
            :headers="$headers" 
            :rows="$tenants" 
            :sort-by="$sortBy" 
            with-pagination
            class="bg-base-100"
            link="tenants/{id}/edit"
        >
            @scope('cell_apartment_name', $tenant)
                <div class="font-semibold">
                    {{ $tenant['apartment_name'] ?? '—' }}
                </div>
            @endscope

            @scope('cell_email', $tenant)
                <div class="text-sm">
                    {{ $tenant['email'] ?? '—' }}
                </div>
            @endscope

            @scope('cell_phone', $tenant)
                <div class="text-sm">
                    {{ $tenant['phone'] ?? '—' }}
                </div>
            @endscope

            @scope('cell_monthly_rent', $tenant)
                <div class="font-semibold">
                    ₱{{ number_format($tenant['monthly_rent'], 2) }}
                </div>
            @endscope

            @scope('cell_lease_end_date', $tenant)
                @if($tenant['lease_end_date'])
                    @php
                        $endDate = \Carbon\Carbon::parse($tenant['lease_end_date']);
                        $daysUntil = now()->diffInDays($endDate, false);
                        $isExpiringSoon = $daysUntil <= 30 && $daysUntil >= 0;
                        $isExpired = $daysUntil < 0;
                    @endphp
                    <div class="text-sm {{ $isExpired ? 'text-error' : ($isExpiringSoon ? 'text-warning' : '') }}">
                        {{ $endDate->format('M d, Y') }}
                        @if($isExpired)
                            <span class="badge badge-error badge-xs">Expired</span>
                        @elseif($isExpiringSoon)
                            <span class="badge badge-warning badge-xs">{{ $daysUntil }}d left</span>
                        @endif
                    </div>
                @else
                    <div class="text-sm text-base-content/50">—</div>
                @endif
            @endscope

            @scope('cell_status', $tenant)
                @php
                    $statusColors = [
                        'active' => 'badge-success',
                        'inactive' => 'badge-ghost',
                    ];
                    $color = $statusColors[$tenant['status']] ?? 'badge-ghost';
                @endphp
                <div class="badge {{ $color }}">
                    {{ ucfirst($tenant['status']) }}
                </div>
            @endscope

            @scope('actions', $tenant)
                <x-button icon="o-trash" wire:click="delete({{ $tenant['id'] }})" wire:confirm="Are you sure?" spinner class="btn-ghost btn-sm text-error" />
            @endscope
        </x-table>
    </x-card>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <div class="grid gap-5"> 
            <x-input placeholder="Search..." wire:model.live.debounce="search" icon="o-magnifying-glass" /> 
            <x-select placeholder="Apartment" wire:model.live="apartment_id" :options="$apartments" placeholder-value="0" /> 
            <x-select placeholder="Status" wire:model.live="status" :options="$statuses" placeholder-value="" /> 
        </div>
        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>
</div>
