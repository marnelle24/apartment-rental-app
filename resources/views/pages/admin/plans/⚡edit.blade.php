<?php

use App\Models\Plan;
use Livewire\Component;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;
use App\Traits\AuthorizesRole;

new class extends Component {
    use Toast;
    use AuthorizesRole;

    public Plan $plan;

    // Form fields
    #[Rule('required|min:2|max:255')]
    public string $name = '';

    #[Rule('nullable|string|max:255')]
    public ?string $short_description = null;

    #[Rule('required|numeric|min:0')]
    public string $price = '0';

    #[Rule('nullable|numeric|min:0')]
    public ?string $annual_price = null;

    #[Rule('required|integer|min:0')]
    public int $apartment_limit = 0;

    #[Rule('required|integer|min:0')]
    public int $tenant_limit = 0;

    #[Rule('nullable|string')]
    public ?string $stripe_price_id = null;

    #[Rule('nullable|string')]
    public ?string $stripe_annual_price_id = null;

    #[Rule('required|boolean')]
    public bool $is_active = true;

    #[Rule('required|integer|min:0')]
    public int $sort_order = 0;

    // Features management
    public array $features = [];
    public string $newFeature = '';

    public function mount(): void
    {
        $this->authorizeRole('admin');

        $this->name = $this->plan->name;
        $this->short_description = $this->plan->short_description;
        $this->price = (string) $this->plan->price;
        $this->annual_price = $this->plan->annual_price ? (string) $this->plan->annual_price : null;
        $this->apartment_limit = $this->plan->apartment_limit;
        $this->tenant_limit = $this->plan->tenant_limit;
        $this->stripe_price_id = $this->plan->stripe_price_id;
        $this->stripe_annual_price_id = $this->plan->stripe_annual_price_id;
        $this->is_active = $this->plan->is_active;
        $this->sort_order = $this->plan->sort_order;
        $this->features = $this->plan->features ?? [];
    }

    // Add a new feature
    public function addFeature(): void
    {
        $trimmed = trim($this->newFeature);
        if ($trimmed !== '' && !in_array($trimmed, $this->features)) {
            $this->features[] = $trimmed;
            $this->newFeature = '';
        }
    }

    // Remove a feature by index
    public function removeFeature(int $index): void
    {
        if (isset($this->features[$index])) {
            array_splice($this->features, $index, 1);
        }
    }

    // Reorder feature up
    public function moveFeatureUp(int $index): void
    {
        if ($index > 0 && isset($this->features[$index])) {
            $temp = $this->features[$index - 1];
            $this->features[$index - 1] = $this->features[$index];
            $this->features[$index] = $temp;
        }
    }

    // Reorder feature down
    public function moveFeatureDown(int $index): void
    {
        if ($index < count($this->features) - 1 && isset($this->features[$index])) {
            $temp = $this->features[$index + 1];
            $this->features[$index + 1] = $this->features[$index];
            $this->features[$index] = $temp;
        }
    }

    // Save the plan
    public function save(): void
    {
        $data = $this->validate();

        $this->plan->update([
            'name' => $this->name,
            'short_description' => $this->short_description,
            'price' => $this->price,
            'annual_price' => $this->annual_price ?: null,
            'apartment_limit' => $this->apartment_limit,
            'tenant_limit' => $this->tenant_limit,
            'stripe_price_id' => $this->stripe_price_id ?: null,
            'stripe_annual_price_id' => $this->stripe_annual_price_id ?: null,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'features' => $this->features,
        ]);

        $this->success("Plan '{$this->plan->name}' updated successfully.", redirectTo: '/admin/plans');
    }
}; ?>

<div>
    <x-header title="Edit Plan: {{ $plan->name }}" separator>
        <x-slot:actions>
            <x-button label="Back to Plans" icon="o-arrow-left" link="/admin/plans" class="btn-ghost" />
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Form --}}
        <div class="lg:col-span-2">
            <x-card shadow class="bg-base-100 border border-base-content/10">
                <x-form wire:submit="save">
                    {{-- Plan Details Section --}}
                    <div class="text-lg font-semibold text-base-content mb-2">Plan Details</div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Plan Name" wire:model="name" hint="Display name shown to users" />
                        <div>
                            <label class="label">
                                <span class="label-text">Slug</span>
                            </label>
                            <input type="text" value="{{ $plan->slug }}" class="input input-bordered w-full opacity-60" disabled />
                            <div class="label">
                                <span class="label-text-alt text-base-content/50">Slug cannot be changed</span>
                            </div>
                        </div>
                    </div>

                    <x-input label="Short Description" wire:model="short_description" hint="Brief tagline displayed below the plan name on the pricing page" placeholder="e.g., Perfect for small-scale landlords" />

                    <x-hr />

                    {{-- Pricing Section --}}
                    <div class="text-lg font-semibold text-base-content mb-2">Pricing</div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Monthly Price ($)" wire:model="price" type="number" step="0.01" min="0" prefix="$" hint="Set to 0 for free tier" />
                        <x-input label="Annual Price ($)" wire:model="annual_price" type="number" step="0.01" min="0" prefix="$" hint="Leave empty if no annual option" />
                    </div>

                    <x-hr />

                    {{-- Stripe Configuration --}}
                    <div class="text-lg font-semibold text-base-content mb-2">Stripe Configuration</div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Stripe Monthly Price ID" wire:model="stripe_price_id" hint="e.g., price_1ABC..." placeholder="price_..." />
                        <x-input label="Stripe Annual Price ID" wire:model="stripe_annual_price_id" hint="e.g., price_1DEF..." placeholder="price_..." />
                    </div>

                    <x-hr />

                    {{-- Limits Section --}}
                    <div class="text-lg font-semibold text-base-content mb-2">Resource Limits</div>
                    <p class="text-sm text-base-content/60 mb-3">Set to 0 for unlimited access.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Apartment Limit" wire:model="apartment_limit" type="number" min="0" hint="0 = unlimited" />
                        <x-input label="Tenant Limit" wire:model="tenant_limit" type="number" min="0" hint="0 = unlimited" />
                    </div>

                    <x-hr />

                    {{-- Settings Section --}}
                    <div class="text-lg font-semibold text-base-content mb-2">Settings</div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Sort Order" wire:model="sort_order" type="number" min="0" hint="Lower number appears first" />
                        <div>
                            <label class="label">
                                <span class="label-text">Status</span>
                            </label>
                            <x-toggle label="Active" wire:model="is_active" hint="Inactive plans are hidden from the pricing page" />
                        </div>
                    </div>

                    <x-slot:actions>
                        <x-button label="Cancel" link="/admin/plans" />
                        <x-button label="Save Changes" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
                    </x-slot:actions>
                </x-form>
            </x-card>
        </div>

        {{-- Features Sidebar --}}
        <div class="lg:col-span-1">
            <x-card shadow class="bg-base-100 border border-base-content/10">
                <div class="text-lg font-semibold text-base-content mb-4">Features</div>
                <p class="text-sm text-base-content/60 mb-4">Manage the feature list displayed on the pricing page.</p>

                {{-- Add Feature --}}
                <div class="flex gap-2 mb-4">
                    <x-input 
                        placeholder="Add a feature..." 
                        wire:model="newFeature" 
                        wire:keydown.enter.prevent="addFeature"
                        class="flex-1" 
                    />
                    <x-button icon="o-plus" wire:click="addFeature" class="btn-primary btn-sm mt-1" spinner />
                </div>

                {{-- Feature List --}}
                @if(count($features) > 0)
                    <div class="space-y-2">
                        @foreach($features as $index => $feature)
                            <div class="flex items-center gap-2 p-2 rounded-lg bg-base-200/50 border border-base-content/5 group">
                                <x-icon name="o-check" class="w-4 h-4 text-teal-500 shrink-0" />
                                <span class="text-sm flex-1">{{ $feature }}</span>
                                <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                    @if($index > 0)
                                        <button wire:click="moveFeatureUp({{ $index }})" class="btn btn-ghost btn-xs btn-circle" title="Move up">
                                            <x-icon name="o-chevron-up" class="w-3 h-3" />
                                        </button>
                                    @endif
                                    @if($index < count($features) - 1)
                                        <button wire:click="moveFeatureDown({{ $index }})" class="btn btn-ghost btn-xs btn-circle" title="Move down">
                                            <x-icon name="o-chevron-down" class="w-3 h-3" />
                                        </button>
                                    @endif
                                    <button wire:click="removeFeature({{ $index }})" class="btn btn-ghost btn-xs btn-circle text-error" title="Remove">
                                        <x-icon name="o-x-mark" class="w-3 h-3" />
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-base-content/40">
                        <x-icon name="o-list-bullet" class="w-8 h-8 mx-auto mb-2" />
                        <p class="text-sm">No features added yet</p>
                    </div>
                @endif

                <div class="mt-4 text-xs text-base-content/50">
                    {{ count($features) }} feature{{ count($features) !== 1 ? 's' : '' }} configured
                </div>
            </x-card>

            {{-- Plan Info Card --}}
            <x-card shadow class="bg-base-100 border border-base-content/10 mt-4">
                <div class="text-lg font-semibold text-base-content mb-3">Plan Info</div>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-base-content/60">Created</span>
                        <span>{{ $plan->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base-content/60">Last Updated</span>
                        <span>{{ $plan->updated_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base-content/60">Subscribers</span>
                        <span class="badge badge-ghost badge-sm">{{ $plan->users()->count() }}</span>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</div>
