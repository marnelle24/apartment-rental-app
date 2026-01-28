<?php

use Livewire\Component;
use Mary\Traits\Toast;
use App\Models\RentPayment;
use App\Models\Tenant;
use App\Models\Apartment;
use Livewire\Attributes\Rule;
use App\Traits\AuthorizesRole;

new class extends Component
{
    use Toast;
    use AuthorizesRole;

    // Form fields
    #[Rule('required|exists:tenants,id')]
    public ?int $tenant_id = null;

    #[Rule('required|exists:apartments,id')]
    public ?int $apartment_id = null;

    #[Rule('required|numeric|min:0.01')]
    public float $amount = 0;

    #[Rule('required|date')]
    public ?string $due_date = null;

    #[Rule('nullable|date|before_or_equal:today')]
    public ?string $payment_date = null;

    #[Rule('required|in:pending,paid,overdue')]
    public string $status = 'pending';

    #[Rule('nullable|max:50')]
    public ?string $payment_method = null;

    #[Rule('nullable|max:100')]
    public ?string $reference_number = null;

    // Check owner access on mount
    public function mount(): void
    {
        $this->authorizeRole('owner');
    }

    // Load tenants and apartments for the form
    public function with(): array 
    {
        return [
            'tenants' => Tenant::where('owner_id', auth()->id())->get(),
            'apartments' => Apartment::where('owner_id', auth()->id())->get(),
        ];
    }

    // Auto-fill apartment when tenant is selected
    public function updatedTenantId($value): void
    {
        if ($value) {
            $tenant = Tenant::find($value);
            if ($tenant && $tenant->owner_id === auth()->id()) {
                $this->apartment_id = $tenant->apartment_id;
                // Auto-fill amount from tenant's monthly rent
                $this->amount = $tenant->monthly_rent;
            }
        }
    }

    // Auto-update status when payment_date is set
    public function updatedPaymentDate($value): void
    {
        if ($value) {
            $this->status = 'paid';
        } else {
            // Check if overdue
            if ($this->due_date && \Carbon\Carbon::parse($this->due_date)->isPast()) {
                $this->status = 'overdue';
            } else {
                $this->status = 'pending';
            }
        }
    }

    // Auto-update status when due_date changes
    public function updatedDueDate($value): void
    {
        if ($value && !$this->payment_date) {
            if (\Carbon\Carbon::parse($value)->isPast()) {
                $this->status = 'overdue';
            } else {
                $this->status = 'pending';
            }
        }
    }

    // Save the new rent payment
    public function save(): void
    {
        $data = $this->validate();
        
        // Ensure tenant belongs to current owner
        $tenant = Tenant::find($data['tenant_id']);
        if (!$tenant || $tenant->owner_id !== auth()->id()) {
            $this->error('Invalid tenant selected.', position: 'toast-bottom');
            return;
        }

        // Ensure apartment belongs to current owner
        $apartment = Apartment::find($data['apartment_id']);
        if (!$apartment || $apartment->owner_id !== auth()->id()) {
            $this->error('Invalid apartment selected.', position: 'toast-bottom');
            return;
        }

        // Ensure tenant's apartment matches selected apartment
        if ($tenant->apartment_id !== $data['apartment_id']) {
            $this->error('Selected apartment does not match tenant\'s apartment.', position: 'toast-bottom');
            return;
        }

        // Auto-update status based on dates
        if ($data['payment_date']) {
            $data['status'] = 'paid';
        } elseif ($data['due_date'] && \Carbon\Carbon::parse($data['due_date'])->isPast()) {
            $data['status'] = 'overdue';
        }

        RentPayment::create($data);

        $this->success('Rent payment created successfully.', redirectTo: '/rent-payments');
    }
};
?>

<div>
    <x-header title="Create Rent Payment" separator />

    <div class="max-w-4xl">
        <x-card shadow class="bg-base-100">
            <x-form wire:submit="save"> 
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-select 
                label="Tenant" 
                wire:model.live="tenant_id" 
                :options="$tenants" 
                placeholder="Select tenant" 
                icon="o-user"
                hint="Apartment and amount will be auto-filled"
            />
            <x-select 
                label="Apartment" 
                wire:model="apartment_id" 
                :options="$apartments" 
                placeholder="Select apartment" 
                icon="o-building-office"
            />
        </div>

        <div class="divider">Payment Information</div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-input label="Amount" wire:model="amount" type="number" step="0.01" hint="Amount in PHP" />
            <x-input label="Due Date" wire:model.live="due_date" type="date" hint="When payment is due" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-input label="Payment Date" wire:model.live="payment_date" type="date" hint="When payment was received (leave empty if not paid yet)" />
            <x-select label="Status" wire:model="status" :options="[
                ['id' => 'pending', 'name' => 'Pending'],
                ['id' => 'paid', 'name' => 'Paid'],
                ['id' => 'overdue', 'name' => 'Overdue'],
            ]" hint="Auto-updates based on dates" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-input label="Payment Method" wire:model="payment_method" hint="e.g., Cash, Bank Transfer, GCash" />
            <x-input label="Reference Number" wire:model="reference_number" hint="Transaction or receipt number" />
        </div>

        <x-slot:actions>
            <x-button label="Cancel" link="/rent-payments" />
            <x-button label="Create" icon="o-plus" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
        </x-card>
    </div>
</div>
