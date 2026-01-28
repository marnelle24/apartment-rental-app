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
    
    public Tenant $tenant;

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

    // Check owner access and populate form with tenant data
    public function mount(): void
    {
        $this->authorizeRole('owner');
        
        // Ensure owner can only edit their own tenants
        if ($this->tenant->owner_id !== auth()->id()) {
            abort(403, 'Unauthorized access');
        }

        $this->apartment_id = $this->tenant->apartment_id;
        $this->name = $this->tenant->name;
        $this->email = $this->tenant->email;
        $this->phone = $this->tenant->phone;
        $this->emergency_contact = $this->tenant->emergency_contact;
        $this->emergency_phone = $this->tenant->emergency_phone;
        $this->move_in_date = $this->tenant->move_in_date?->format('Y-m-d');
        $this->lease_start_date = $this->tenant->lease_start_date?->format('Y-m-d');
        $this->lease_end_date = $this->tenant->lease_end_date?->format('Y-m-d');
        $this->monthly_rent = $this->tenant->monthly_rent;
        $this->deposit_amount = $this->tenant->deposit_amount;
        $this->status = $this->tenant->status;
        $this->notes = $this->tenant->notes;
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

    // Save the updated tenant
    public function save(): void
    {
        $data = $this->validate();

        // Ensure apartment belongs to current owner
        $apartment = Apartment::find($data['apartment_id']);
        if (!$apartment || $apartment->owner_id !== auth()->id()) {
            $this->error('Invalid apartment selected.', position: 'toast-bottom');
            return;
        }

        $this->tenant->update($data);

        $this->success('Tenant updated successfully.', redirectTo: '/tenants');
    }
};
?>

<div>
    <x-header title="Update {{ $tenant->name }}" separator />

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
                    <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
                </x-slot:actions>
            </x-form>
        </x-card>
    </div>
</div>
