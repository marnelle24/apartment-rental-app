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
    
    public RentPayment $rentPayment;

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

    // Check owner access and populate form with payment data
    public function mount(): void
    {
        $this->authorizeRole('owner');
        
        // Ensure owner can only edit payments for their own tenants
        if ($this->rentPayment->tenant->owner_id !== auth()->id()) {
            abort(403, 'Unauthorized access');
        }

        $this->tenant_id = $this->rentPayment->tenant_id;
        $this->apartment_id = $this->rentPayment->apartment_id;
        $this->amount = $this->rentPayment->amount;
        $this->due_date = $this->rentPayment->due_date->format('Y-m-d');
        $this->payment_date = $this->rentPayment->payment_date?->format('Y-m-d');
        $this->status = $this->rentPayment->status;
        $this->payment_method = $this->rentPayment->payment_method;
        $this->reference_number = $this->rentPayment->reference_number;
    }

    // Load tenants and apartments for the form
    public function with(): array 
    {
        $apartments = Apartment::where('owner_id', auth()->id())->get();
        $apartmentOptions = $apartments->map(fn (Apartment $apt) => [
            'id' => $apt->id,
            'name' => $apt->name . ' - ₱' . number_format((float) $apt->monthly_rent, 0),
        ])->values()->all();

        $tenants = Tenant::where('owner_id', auth()->id())->get();
        $tenantOptions = $tenants->map(fn (Tenant $t) => [
            'id' => $t->id,
            'name' => $t->name,
        ])->values()->all();

        return [
            'tenantOptions' => $tenantOptions,
            'apartments' => $apartmentOptions,
        ];
    }

    // Auto-fill apartment when tenant is selected
    public function updatedTenantId($value): void
    {
        if ($value) {
            $tenant = Tenant::find($value);
            if ($tenant && $tenant->owner_id === auth()->id()) {
                $this->apartment_id = $tenant->apartment_id;
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

    // Save the updated rent payment
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

        $this->rentPayment->update($data);

        $this->success('Rent payment updated successfully.', redirectTo: '/rent-payments');
    }
};
?>

<div>
    <x-header title="Update Payment #{{ $rentPayment->id }}" separator />

    <div class="max-w-4xl">
        <x-card class="bg-base-100 border border-base-content/10" shadow>
            <x-form wire:submit="save"> 
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-choices
                label="Tenant"
                wire:model.live="tenant_id"
                :options="$tenantOptions"
                option-value="id"
                option-label="name"
                placeholder="Select tenant"
                searchable
                single
                hint="Apartment will be auto-filled"
            />
            <x-choices
                label="Apartment"
                wire:model.live="apartment_id"
                :options="$apartments"
                option-value="id"
                option-label="name"
                placeholder="Select apartment"
                searchable
                single
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
            <x-button label="Cancel" link="/rent-payments" class="border border-gray-400 text-gray-500 dark:text-gray-400 dark:hover:bg-gray-200/30" />
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="bg-teal-500 text-white" />
        </x-slot:actions>
    </x-form>
        </x-card>
    </div>
</div>
