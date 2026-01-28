<?php

use Livewire\Component;
use Mary\Traits\Toast;
use App\Models\Location;
use Livewire\Attributes\Rule;
use App\Traits\AuthorizesRole;

new class extends Component
{
    use Toast;
    use AuthorizesRole;

    // Form fields
    #[Rule('required|min:2|max:255')] 
    public string $name = '';

    #[Rule('nullable|max:1000')]
    public ?string $description = null;

    // Check admin access on mount
    public function mount(): void
    {
        $this->authorizeRole('admin');
    }

    // Save the new location
    public function save(): void
    {
        $data = $this->validate();
        
        Location::create($data);

        $this->success('Location created successfully.', redirectTo: '/locations');
    }
};
?>

<div>
    <x-header title="Create Location" separator />

    <div class="max-w-2xl">
        <x-card shadow class="bg-base-100">
            <x-form wire:submit="save"> 
                <x-input label="Name" wire:model="name" hint="e.g., Manila City, Cebu City" />
                <x-textarea label="Description" wire:model="description" rows="4" hint="Optional description of the location" />

                <x-slot:actions>
                    <x-button label="Cancel" link="/locations" />
                    <x-button label="Create" icon="o-plus" spinner="save" type="submit" class="btn-primary" />
                </x-slot:actions>
            </x-form>
        </x-card>
    </div>
</div>
