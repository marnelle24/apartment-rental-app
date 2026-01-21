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
    public User $user;

    // Form fields
    #[Rule('required|min:3')] 
    public string $name = '';

    public string $email = '';

    #[Rule('sometimes|nullable|min:8')]
    public ?string $password = null;

    #[Rule('sometimes')]
    public ?int $country_id = null;

    #[Rule('sometimes')]
    public ?string $bio = null;

    #[Rule('sometimes')]
    public array $language_ids = [];

    // Mount and populate form with user data
    public function mount(): void
    {
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->country_id = $this->user->country_id;
        $this->bio = $this->user->bio;
        $this->language_ids = $this->user->languages->pluck('id')->toArray();
    }
    
    // Load countries and languages for the form
    public function with(): array 
    {
        return [
            'countries' => Country::all()->map(fn($country) => ['id' => $country->id, 'name' => $country->name])->toArray(),
            'languages' => Language::all()->map(fn($language) => ['id' => $language->id, 'name' => $language->name])->toArray()
        ];
    }

    // Save the updated user
    public function save(): void
    {
        // Validate with unique email rule that ignores current user
        $this->validate([
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users,email,' . $this->user->id,
            'password' => 'sometimes|nullable|min:8',
            'country_id' => 'sometimes',
            'bio' => 'sometimes',
            'language_ids' => 'sometimes',
        ]);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'country_id' => $this->country_id,
            'bio' => $this->bio,
        ];
        
        // Only hash password if it's provided
        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        $this->user->update($data);
        
        // Sync languages
        $this->user->languages()->sync($this->language_ids ?? []);

        $this->success('User updated successfully.', redirectTo: '/users');
    }
};
?>

<div>
    <x-header title="Update {{ $user->name }}" separator />

    <x-form wire:submit="save"> 
        <x-input label="Name" wire:model="name" />
        <x-input label="Email" wire:model="email" type="email" />
        <x-input label="Password" wire:model="password" type="password" hint="Leave empty to keep current password" />
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
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>