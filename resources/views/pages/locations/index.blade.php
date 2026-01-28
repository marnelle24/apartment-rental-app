<?php

use App\Models\Location;
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

    public bool $drawer = false;

    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];

    // Check admin access on mount
    public function mount(): void
    {
        $this->authorizeRole('admin');
    }

    // Clear filters
    public function clear(): void
    {
        $this->reset();
        $this->resetPage(); 
        $this->success('Filters cleared.', position: 'toast-bottom');
    }

    // Delete action
    public function delete(Location $location): void
    {
        // Check if location has apartments
        if ($location->apartments()->count() > 0) {
            $this->error('Cannot delete location with existing apartments.', position: 'toast-bottom');
            return;
        }

        $location->delete();
        $this->warning("$location->name deleted", 'Good bye!', position: 'toast-bottom');
    }

    // Table headers
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'name', 'label' => 'Name', 'class' => 'w-64'],
            ['key' => 'description', 'label' => 'Description', 'sortable' => false, 'class' => 'hidden lg:table-cell'],
            ['key' => 'apartments_count', 'label' => 'Apartments', 'class' => 'w-24 text-center'],
            ['key' => 'created_at', 'label' => 'Created', 'class' => 'hidden lg:table-cell'],
        ];
    }

    public function locations(): LengthAwarePaginator
    {
        return Location::query()
            ->withCount('apartments')
            ->when($this->search, fn(Builder $q) => $q->where('name', 'like', "%$this->search%")
                ->orWhere('description', 'like', "%$this->search%"))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(10);
    }

    public function with(): array
    {
        return [
            'locations' => $this->locations(),
            'headers' => $this->headers(),
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
    <x-header title="Locations" separator progress-indicator>
        <x-slot:middle class="justify-end!">
            <x-input placeholder="Search..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Create" link="/locations/create" icon="o-plus" class="btn-primary" responsive />
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel" />
        </x-slot:actions>
    </x-header>

    <!-- TABLE  -->
    <x-card class="border border-base-content/10" shadow>
        <x-table 
            :headers="$headers" 
            :rows="$locations" 
            :sort-by="$sortBy" 
            with-pagination
            class="bg-base-100"
            link="locations/{id}/edit"
        >
            @scope('cell_apartments_count', $location)
                <div class="badge badge-ghost">
                    {{ $location['apartments_count'] }}
                </div>
            @endscope

            @scope('cell_description', $location)
                <div class="line-clamp-2 max-w-md">
                    {{ $location['description'] ?? '—' }}
                </div>
            @endscope

            @scope('cell_created_at', $location)
                <div class="text-sm text-base-content/70">
                    {{ $location['created_at']->format('M d, Y') }}
                </div>
            @endscope

            @scope('actions', $location)
                <x-button icon="o-trash" wire:click="delete({{ $location['id'] }})" wire:confirm="Are you sure? This will only work if the location has no apartments." spinner class="btn-ghost btn-sm text-error" />
            @endscope
        </x-table>
    </x-card>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <div class="grid gap-5"> 
            <x-input placeholder="Search..." wire:model.live.debounce="search" icon="o-magnifying-glass" /> 
        </div>
        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>
</div>
