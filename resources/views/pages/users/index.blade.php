<?php

use App\Models\User;
use Livewire\Component;
use Mary\Traits\Toast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;
use App\Models\Country;

new class extends Component {
    use Toast;
    use WithPagination;

    public string $search = '';

    public int $country_id = 0;

    public bool $drawer = false;

    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];

    // Clear filters
    public function clear(): void
    {
        $this->reset();
        $this->resetPage(); 
        $this->success('Filters cleared.', position: 'toast-bottom');
    }

    // Delete action
    public function delete(User $user): void
    {
        $user->delete();
        $this->warning("$user->name deleted", 'Good bye!', position: 'toast-bottom');
    }

    // Table headers
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'name', 'label' => 'Name', 'class' => 'w-64'],
            ['key' => 'country_name', 'label' => 'Country', 'class' => 'hidden lg:table-cell'],
            ['key' => 'email', 'label' => 'E-mail', 'sortable' => false],
        ];
    }

    /**
     * For demo purpose, this is a static collection.
     *
     * On real projects you do it with Eloquent collections.
     * Please, refer to maryUI docs to see the eloquent examples.
     */
    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->with(['country'])
            ->withAggregate('country', 'name') 
            ->when($this->search, fn(Builder $q) => $q->where('name', 'like', "%$this->search%"))
            ->when($this->country_id, fn(Builder $q) => $q->where('country_id', $this->country_id))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(8);
    }

    public function with(): array
    {
        $countries = Country::all()->map(fn($country) => ['id' => $country->id, 'name' => $country->name])->toArray();
        
        // Add "All Countries" option at the beginning
        array_unshift($countries, ['id' => 0, 'name' => 'All Countries']);
        
        return [
            'users' => $this->users(),
            'headers' => $this->headers(),
            'countries' => $countries
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
    <x-header title="Users" separator progress-indicator>
        <x-slot:middle class="justify-end!">
            <x-input placeholder="Search..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Create" link="/users/create" icon="o-plus" class="btn-primary" responsive />
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel" />
        </x-slot:actions>
    </x-header>

    <!-- TABLE  -->
    <x-card class="border border-base-content/10" shadow>
        <x-table 
            :headers="$headers" 
            :rows="$users" 
            :sort-by="$sortBy" 
            with-pagination
            class="bg-base-100"
            link="users/{id}/edit"
        >
            @scope('cell_country_name', $user)
                <div class="hidden lg:table-cell">
                    {{ $user['country_name'] ?? 'N/A' }}
                </div>
            @endscope
            @scope('actions', $user)
                <x-button icon="o-trash" wire:click="delete({{ $user['id'] }})" wire:confirm="Are you sure?" spinner class="btn-ghost btn-sm text-error" />
            @endscope
        </x-table>
    </x-card>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <div class="grid gap-5"> 
            <x-input placeholder="Search..." wire:model.live.debounce="search" icon="o-magnifying-glass" /> 
            <div class="form-control">
                <label class="label">
                    <span class="label-text">Country</span>
                </label>
                <select wire:model.live="country_id" class="select select-bordered w-full">
                    @foreach($countries as $country)
                        <option value="{{ $country['id'] }}">{{ $country['name'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>
</div>
