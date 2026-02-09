<?php

use App\Models\Apartment;
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
    public int $location_id = 0;
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
        $this->reset(['search', 'location_id', 'status']);
        $this->resetPage(); 
        $this->success('Filters cleared.', position: 'toast-bottom');
    }

    // Delete action
    public function delete(Apartment $apartment): void
    {
        // Ensure owner can only delete their own apartments
        if ($apartment->owner_id !== auth()->id()) {
            $this->error('Unauthorized access.', position: 'toast-bottom');
            return;
        }

        // Check if apartment has tenants
        if ($apartment->tenants()->count() > 0) {
            $this->error('Cannot delete apartment with existing tenants.', position: 'toast-bottom');
            return;
        }

        $apartment->delete();
        $this->warning("$apartment->name deleted", 'Good bye!', position: 'toast-bottom');
    }

    // Table headers
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'name', 'label' => 'Name', 'class' => 'w-64'],
            ['key' => 'location_name', 'label' => 'Location', 'class' => 'hidden lg:table-cell'],
            ['key' => 'unit_number', 'label' => 'Unit', 'class' => ''],
            ['key' => 'monthly_rent', 'label' => 'Monthly Rent', 'class' => ''],
            ['key' => 'status', 'label' => 'Status', 'class' => 'w-32'],
            ['key' => 'tenants_count', 'label' => 'Tenants', 'class' => 'w-24 text-center'],
        ];
    }

    public function apartments(): LengthAwarePaginator
    {
        return Apartment::query()
            ->where('owner_id', auth()->id())
            ->with(['location'])
            ->withCount('tenants')
            ->withCount(['tenants as active_tenants_count' => fn ($q) => $q->where('status', 'active')])
            ->withAggregate('location', 'name')
            ->when($this->search, fn(Builder $q) => $q->where('name', 'like', "%$this->search%")
                ->orWhere('address', 'like', "%$this->search%")
                ->orWhere('unit_number', 'like', "%$this->search%"))
            ->when($this->location_id, fn(Builder $q) => $q->where('location_id', $this->location_id))
            ->when($this->status, fn(Builder $q) => $q->where('status', $this->status))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(10);
    }

    public function with(): array
    {
        return [
            'apartments' => $this->apartments(),
            'headers' => $this->headers(),
            'locations' => \App\Models\Location::all(),
            'statuses' => [
                ['id' => 'available', 'name' => 'Available'],
                ['id' => 'occupied', 'name' => 'Occupied'],
                ['id' => 'maintenance', 'name' => 'Maintenance'],
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
    <x-header title="My Apartments" separator progress-indicator>
        <x-slot:middle class="justify-end!">
            <x-input placeholder="Search..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Create" link="/apartments/create" icon="o-plus" class="bg-teal-500 text-white" responsive />
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel" class="border border-gray-400 text-gray-500 dark:text-gray-400 dark:hover:bg-gray-200/30" />
        </x-slot:actions>
    </x-header>

    <!-- TABLE  -->
    <x-card class="border border-base-content/10" shadow>
        @if($apartments->total() === 0)
            <div class="flex flex-col items-center justify-center py-16 px-4 text-center">
                <x-icon name="o-building-office-2" class="w-16 h-16 text-base-content/30 mb-4" />
                <h3 class="text-lg font-semibold text-base-content/80 mb-2">No apartments yet</h3>
                <p class="text-sm text-base-content/60 max-w-sm mb-6">
                    You haven't added any apartments. Create your first one to start managing units and tenants.
                </p>
                <x-button label="Create apartment" link="/apartments/create" icon="o-plus" class="bg-teal-500 text-white" />
            </div>
        @else
            <x-table 
                :headers="$headers" 
                :rows="$apartments" 
                :sort-by="$sortBy" 
                with-pagination
                class="bg-base-100"
                link="apartments/{id}"
            >
                @scope('cell_location_name', $apartment)
                    <div class="font-semibold">
                        {{ $apartment['location_name'] ?? '—' }}
                    </div>
                @endscope

                @scope('cell_unit_number', $apartment)
                    <div class="text-sm">
                        {{ $apartment['unit_number'] ?? '—' }}
                    </div>
                @endscope

                @scope('cell_monthly_rent', $apartment)
                    <div class="font-semibold">
                        ₱{{ number_format($apartment['monthly_rent'], 2) }}
                    </div>
                @endscope

                @scope('cell_status', $apartment)
                    @php
                        $displayStatus = ($apartment['active_tenants_count'] ?? 0) > 0 ? 'occupied' : 'available';
                        $statusColors = [
                            'available' => 'badge-success',
                            'occupied' => 'badge-info',
                        ];
                        $color = $statusColors[$displayStatus] ?? 'badge-ghost';
                    @endphp
                    <div class="badge {{ $color }}">
                        {{ ucfirst($displayStatus) }}
                    </div>
                @endscope

                @scope('cell_tenants_count', $apartment)
                    <div class="badge badge-ghost">
                        {{ $apartment['tenants_count'] }}
                    </div>
                @endscope

                @scope('actions', $apartment)
                    <x-button icon="o-trash" wire:click="delete({{ $apartment['id'] }})" wire:confirm="Are you sure? This will only work if the apartment has no tenants." spinner class="btn-ghost btn-sm text-error" />
                @endscope
            </x-table>
        @endif
    </x-card>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <div class="grid gap-5"> 
            <x-input placeholder="Search..." wire:model.live.debounce="search" icon="o-magnifying-glass" /> 
            <x-select placeholder="Location" wire:model.live="location_id" :options="$locations" placeholder-value="0" /> 
            <x-select placeholder="Status" wire:model.live="status" :options="$statuses" placeholder-value="" /> 
        </div>
        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>
</div>
