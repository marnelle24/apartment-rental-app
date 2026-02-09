<?php

use Livewire\Component;
use Mary\Traits\Toast;
use App\Models\Tenant;
use App\Models\Apartment;
use App\Models\User;
use Livewire\Attributes\Rule;
use App\Traits\AuthorizesRole;

new class extends Component
{
    use Toast;
    use AuthorizesRole;

    // Form fields
    #[Rule('required|exists:apartments,id')]
    public ?int $apartment_id = null;

    #[Rule('required|exists:users,id')]
    public ?int $user_id = null;

    #[Rule('required|min:2|max:255')]
    public string $name = '';

    #[Rule('required|email|max:255')]
    public string $email = '';

    #[Rule('nullable|max:50')]
    public ?string $phone = null;

    #[Rule('nullable|max:255')]
    public ?string $emergency_contact = null;

    #[Rule('nullable|max:50')]
    public ?string $emergency_phone = null;

    #[Rule('required|date')]
    public ?string $move_in_date = null;

    #[Rule('required|date')]
    public ?string $lease_start_date = null;

    #[Rule('required|date|after:lease_start_date')]
    public ?string $lease_end_date = null;

    #[Rule('required|numeric|min:0')]
    public float $monthly_rent = 0;

    #[Rule('nullable|numeric|min:0')]
    public ?float $deposit_amount = null;

    #[Rule('required|in:active,inactive')]
    public string $status = 'active';

    #[Rule('nullable|max:2000')]
    public ?string $notes = null;

    /** @var array<int, array{id: int, name: string}> Options for tenant user search (updated by search()) */
    public array $tenantUserOptions = [];

    /** @var array<int, array{id: int, name: string}> Options for apartment search (updated by searchApartments()) */
    public array $apartmentOptions = [];

    // Check owner access and plan limits on mount
    public function mount(): void
    {
        $this->authorizeRole('owner');

        // Check if owner can add more tenants based on their plan
        if (! auth()->user()->canAddTenant()) {
            $plan = auth()->user()->getEffectivePlan();
            $limit = $plan ? $plan->tenant_limit : 0;
            session()->flash('error', "You've reached your plan limit of {$limit} tenants. Please upgrade your plan to add more.");
            $this->redirect('/tenants');
        }

        $this->searchTenantUsers();
        $this->searchApartments();
    }

    // Load plan usage for near-limit banner
    public function with(): array 
    {
        $user = auth()->user();
        $plan = $user->getEffectivePlan();
        $remaining = $user->remainingTenantSlots();

        return [
            'plan' => $plan,
            'remainingSlots' => $remaining,
            'isNearLimit' => $remaining !== null && $remaining <= 2 && $remaining > 0,
        ];
    }

    // Search owner's apartments by name, address, or unit number (called on mount and when user types)
    public function searchApartments(string $value = ''): void
    {
        $query = Apartment::where('owner_id', auth()->id())->orderBy('name');
        if ($value !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$value}%")
                ->orWhere('address', 'like', "%{$value}%")
                ->orWhere('unit_number', 'like', "%{$value}%"));
        }
        $apartments = $query->take(15)->get();
        $options = $apartments->map(fn ($a) => ['id' => $a->id, 'name' => $a->name . ($a->unit_number ? ' – ' . $a->unit_number : '')])->toArray();
        // Ensure currently selected apartment is in the list when searching
        if ($this->apartment_id) {
            $selected = Apartment::where('id', $this->apartment_id)->where('owner_id', auth()->id())->first();
            if ($selected && !collect($options)->contains('id', $this->apartment_id)) {
                array_unshift($options, ['id' => $selected->id, 'name' => $selected->name . ($selected->unit_number ? ' – ' . $selected->unit_number : '')]);
            }
        }
        $this->apartmentOptions = array_values($options);
    }

    // Search tenant-role users by name/email (called on mount and when user types in the searchable choices)
    public function searchTenantUsers(string $value = ''): void
    {
        $query = User::where('role', 'tenant')->orderBy('name');
        if ($value !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$value}%")->orWhere('email', 'like', "%{$value}%"));
        }
        $users = $query->take(15)->get();
        $options = $users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name . ' (' . $u->email . ')'])->toArray();
        // Ensure currently selected user is in the list when searching
        if ($this->user_id) {
            $selected = User::where('id', $this->user_id)->where('role', 'tenant')->first();
            if ($selected && !collect($options)->contains('id', $this->user_id)) {
                array_unshift($options, ['id' => $selected->id, 'name' => $selected->name . ' (' . $selected->email . ')']);
            }
        }
        $this->tenantUserOptions = array_values($options);
    }

    // Auto-fill name and email when a tenant user is selected; clear when selection is cleared
    public function updatedUserId($value): void
    {
        if ($value) {
            $user = User::find($value);
            if ($user && $user->role === 'tenant') {
                $this->name = $user->name;
                $this->email = $user->email;
            }
        } else {
            $this->name = '';
            $this->email = null;
        }
    }

    // Populate name and email from selected user when tenant choices loses focus (focus-out)
    public function syncNameEmailFromSelectedUser(): void
    {
        if ($this->user_id) {
            $user = User::find($this->user_id);
            if ($user && $user->role === 'tenant') {
                $this->name = $user->name;
                $this->email = $user->email;
            }
        }
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
        // Re-check plan limit before saving
        if (! auth()->user()->canAddTenant()) {
            $this->error('You have reached your plan\'s tenant limit. Please upgrade your plan.', position: 'toast-bottom');
            return;
        }

        $data = $this->validate();
        
        // Ensure apartment belongs to current owner
        $apartment = Apartment::find($data['apartment_id']);
        if (!$apartment || $apartment->owner_id !== auth()->id()) {
            $this->error('Invalid apartment selected.', position: 'toast-bottom');
            return;
        }
        
        // Set owner_id to current user; name/email already set from selected user
        $data['owner_id'] = auth()->id();

        Tenant::create($data);

        $this->success('Tenant created successfully.', redirectTo: '/tenants');
    }
};
?>

<div>
    <x-header title="Create Tenant" separator />

    {{-- Near-limit upgrade banner --}}
    @if($isNearLimit)
        <div class="mb-4 p-4 rounded-lg bg-warning/10 border border-warning/20 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <x-icon name="o-exclamation-triangle" class="w-5 h-5 text-warning shrink-0" />
                <span class="text-sm">
                    You have <strong>{{ $remainingSlots }}</strong> tenant {{ Str::plural('slot', $remainingSlots) }} remaining on your <strong>{{ $plan->name }}</strong> plan.
                </span>
            </div>
            <x-button label="Upgrade" icon="o-arrow-up-circle" link="/subscription/pricing" class="btn-sm bg-teal-500 hover:bg-teal-600 text-white shrink-0" />
        </div>
    @endif

    <div class="max-w-4xl">
        <x-card class="bg-base-100 border border-base-content/10" shadow>
            <x-form wire:submit="save"> 
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-choices
                        label="Apartment"
                        wire:model.live="apartment_id"
                        :options="$apartmentOptions"
                        option-value="id"
                        option-label="name"
                        placeholder="Search by name or unit..."
                        searchable
                        search-function="searchApartments"
                        single
                        hint="Monthly rent will be auto-filled from apartment"
                    />
                    <div x-data @focusout="$wire.syncNameEmailFromSelectedUser()">
                        <x-choices
                            label="Select tenant"
                            wire:model="user_id"
                            :options="$tenantUserOptions"
                            option-value="id"
                            option-label="name"
                            placeholder="Search by name or email..."
                            searchable
                            search-function="searchTenantUsers"
                            single
                            hint="Search and select a user with tenant role to assign to this apartment"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                    <x-input label="Name" wire:model="name" readonly hint="Filled from selected tenant user" />
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input label="Email" wire:model="email" type="email" readonly hint="Filled from selected tenant user" />
                    <x-input label="Phone" wire:model="phone" hint="Contact phone number (optional)" />
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
                    <x-button label="Cancel" link="/tenants" class="border border-gray-400 text-gray-500 dark:text-gray-400 dark:hover:bg-gray-200/30" />
                    <x-button label="Create" icon="o-plus" spinner="save" type="submit" class="bg-teal-500 text-white" />
                </x-slot:actions>
            </x-form>
        </x-card>
    </div>
</div>
