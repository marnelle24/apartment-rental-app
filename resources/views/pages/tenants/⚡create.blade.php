<?php

use Livewire\Component;
use Mary\Traits\Toast;
use App\Models\Tenant;
use App\Models\Apartment;
use Livewire\Attributes\Rule;
use App\Traits\AuthorizesRole;

new class extends Component
{
    use Toast;
    use AuthorizesRole;

    // Form fields
    #[Rule('required|exists:apartments,id')]
    public ?int $apartment_id = null;

    #[Rule('required|min:2|max:255')] 
    public string $name = '';

    #[Rule('nullable|email|max:255')]
    public ?string $email = null;

    #[Rule('nullable|max:50')]
    public ?string $phone = null;

    #[Rule('nullable|max:255')]
    public ?string $emergency_contact = null;

    #[Rule('nullable|max:50')]
    public ?string $emergency_phone = null;

    #[Rule('nullable|date')]
    public ?string $move_in_date = null;

    #[Rule('nullable|date')]
    public ?string $lease_start_date = null;

    #[Rule('nullable|date|after:lease_start_date')]
    public ?string $lease_end_date = null;

    #[Rule('required|numeric|min:0')]
    public float $monthly_rent = 0;

    #[Rule('nullable|numeric|min:0')]
    public ?float $deposit_amount = null;

    #[Rule('required|in:active,inactive')]
    public string $status = 'active';

    #[Rule('nullable|max:2000')]
    public ?string $notes = null;

    // Check owner access on mount
    public function mount(): void
    {
        $this->authorizeRole('owner');
    }

    // Load apartments for the form
    public function with(): array 
    {
        return [
            'apartments' => Apartment::where('owner_id', auth()->id())->get(),
        ];
    }

    // Auto-fill monthly rent when apartment is selected
    public function updatedApartmentId($value): void
    {
        if ($value) {
            $apartment = Apartment::find($value);
            if ($apartment && $apartment->owner_id === auth()->id()) {
                $this->monthly_rent = $apartment->monthly_rent;
            }
        }
    }

    // Save the new tenant
    public function save(): void
    {
        $data = $this->validate();
        
        // Ensure apartment belongs to current owner
        $apartment = Apartment::find($data['apartment_id']);
        if (!$apartment || $apartment->owner_id !== auth()->id()) {
            $this->error('Invalid apartment selected.', position: 'toast-bottom');
            return;
        }
        
        // Set owner_id to current user
        $data['owner_id'] = auth()->id();

        Tenant::create($data);

        $this->success('Tenant created successfully.', redirectTo: '/tenants');
    }
};
?>

<div>
    <x-header title="Create Tenant" separator />

    <div class="max-w-4xl">
        <x-card shadow class="bg-base-100 border border-base-content/10">
            <x-form wire:submit="save"> 
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-select 
                        label="Apartment" 
                        wire:model.live="apartment_id" 
                        :options="$apartments" 
                        placeholder="Select apartment" 
                        icon="o-building-office"
                        hint="Monthly rent will be auto-filled from apartment"
                    />
                    <x-input label="Name" wire:model="name" hint="Full name of the tenant" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input label="Email" wire:model="email" type="email" hint="Tenant email address" />
                    <x-input label="Phone" wire:model="phone" hint="Contact phone number" />
                </div>

                <div class="divider">Emergency Contact</div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input label="Emergency Contact Name" wire:model="emergency_contact" hint="Name of emergency contact" />
                    <x-input label="Emergency Contact Phone" wire:model="emergency_phone" hint="Emergency contact phone number" />
                </div>

                <div class="divider">Lease Information</div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-input label="Move-in Date" wire:model="move_in_date" type="date" />
                    <x-input label="Lease Start Date" wire:model="lease_start_date" type="date" />
                    <x-input label="Lease End Date" wire:model="lease_end_date" type="date" hint="Must be after lease start date" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-input label="Monthly Rent" wire:model="monthly_rent" type="number" step="0.01" hint="Amount in PHP" />
                    <x-input label="Deposit Amount" wire:model="deposit_amount" type="number" step="0.01" hint="Security deposit in PHP" />
                    <x-select label="Status" wire:model="status" :options="[
                        ['id' => 'active', 'name' => 'Active'],
                        ['id' => 'inactive', 'name' => 'Inactive'],
                    ]" />
                </div>

                <x-textarea label="Notes" wire:model="notes" rows="4" hint="Additional notes about the tenant" />

                <x-slot:actions>
                    <x-button label="Cancel" link="/tenants" />
                    <x-button label="Create" icon="o-plus" spinner="save" type="submit" class="btn-primary" />
                </x-slot:actions>
            </x-form>
        </x-card>
    </div>
</div>
