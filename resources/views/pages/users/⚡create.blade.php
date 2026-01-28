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
            'countries' => Country::all()->map(fn($country) => ['id' => $country->id, 'name' => $country->name])->toArray(),
            'languages' => Language::all()->map(fn($language) => ['id' => $language->id, 'name' => $language->name])->toArray()
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
    <div class="max-w-2xl">
        <x-card class="bg-base-100 border border-base-content/10" shadow>
            <x-form wire:submit="save"> 
                <x-input label="Name" wire:model="name" />
                <x-input label="Email" wire:model="email" type="email" />
                <x-input label="Password" wire:model="password" type="password" />
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Country</span>
                    </label>
                    <select wire:model="country_id" class="select select-bordered w-full">
                        <option value="">---</option>
                        @foreach($countries as $country)
                            <option value="{{ $country['id'] }}">{{ $country['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <x-textarea label="Bio" wire:model="bio" rows="3" />
                <x-choices label="Languages" wire:model="language_ids" :options="$languages" searchable />

                <x-slot:actions>
                    <x-button label="Cancel" link="/users" />
                    <x-button label="Create" icon="o-plus" spinner="save" type="submit" class="btn-primary" />
                </x-slot:actions>
            </x-form>
        </x-card>
    </div>
</div>