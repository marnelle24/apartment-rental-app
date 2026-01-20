<?php

use Livewire\Component;
use Mary\Traits\Toast;
use App\Models\User;
use App\Models\Country;
use App\Models\Language;
use Livewire\Attributes\Rule;
use Illuminate\Support\Facades\Hash;

new class extends Component
{
    use Toast;

    // Form fields
    #[Rule('required|min:3')] 
    public string $name = '';

    #[Rule('required|email|unique:users,email')]
    public string $email = '';

    #[Rule('required|min:8')]
    public string $password = '';

    #[Rule('sometimes')]
    public ?int $country_id = null;

    #[Rule('sometimes')]
    public ?string $bio = null;

    #[Rule('sometimes')]
    public array $language_ids = [];

    // Load countries and languages for the form
    public function with(): array 
    {
        return [
            'countries' => Country::all(),
            'languages' => Language::all()
        ];
    }

    // Save the new user
    public function save(): void
    {
        $data = $this->validate();
        $data['password'] = Hash::make($data['password']);
        
        $user = User::create($data);
        
        // Sync languages if any selected
        if (!empty($this->language_ids)) {
            $user->languages()->sync($this->language_ids);
        }

        $this->success('User created successfully.', redirectTo: '/users');
    }
};
?>

<div>
    <x-header title="Create User" separator />

    <x-form wire:submit="save"> 
        <x-input label="Name" wire:model="name" />
        <x-input label="Email" wire:model="email" type="email" />
        <x-input label="Password" wire:model="password" type="password" />
        <x-select label="Country" wire:model="country_id" :options="$countries" placeholder="---" />
        <x-textarea label="Bio" wire:model="bio" rows="3" />
        <x-choices label="Languages" wire:model="language_ids" :options="$languages" searchable />

        <x-slot:actions>
            <x-button label="Cancel" link="/users" />
            <x-button label="Create" icon="o-plus" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>