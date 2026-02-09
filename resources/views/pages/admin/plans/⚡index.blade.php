<?php

use App\Models\Plan;
use Livewire\Component;
use Mary\Traits\Toast;
use App\Traits\AuthorizesRole;

new class extends Component {
    use Toast;
    use AuthorizesRole;

    public string $search = '';
    public array $sortBy = ['column' => 'sort_order', 'direction' => 'asc'];

    public function mount(): void
    {
        $this->authorizeRole('admin');
    }

    // Toggle plan active status
    public function toggleActive(int $planId): void
    {
        $plan = Plan::findOrFail($planId);
        $plan->update(['is_active' => !$plan->is_active]);

        $status = $plan->is_active ? 'activated' : 'deactivated';
        $this->success("Plan '{$plan->name}' has been {$status}.", position: 'toast-bottom');
    }

    // Table headers
    public function headers(): array
    {
        return [
            ['key' => 'sort_order', 'label' => '#', 'class' => 'w-12'],
            ['key' => 'name', 'label' => 'Plan Name', 'class' => ''],
            ['key' => 'price', 'label' => 'Monthly Price', 'class' => 'text-right'],
            ['key' => 'annual_price', 'label' => 'Annual Price', 'class' => 'text-right'],
            ['key' => 'apartment_limit', 'label' => 'Apt Limit', 'class' => 'text-center'],
            ['key' => 'tenant_limit', 'label' => 'Tenant Limit', 'class' => 'text-center'],
            ['key' => 'subscribers', 'label' => 'Subscribers', 'class' => 'text-center', 'sortable' => false],
            ['key' => 'is_active', 'label' => 'Status', 'class' => 'text-center'],
        ];
    }

    public function with(): array
    {
        $plans = Plan::query()
            ->withCount('users')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy(...array_values($this->sortBy))
            ->get()
            ->map(fn($plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'price' => $plan->price,
                'annual_price' => $plan->annual_price,
                'apartment_limit' => $plan->apartment_limit,
                'tenant_limit' => $plan->tenant_limit,
                'features' => $plan->features ?? [],
                'is_active' => $plan->is_active,
                'sort_order' => $plan->sort_order,
                'subscribers' => $plan->users_count,
                'stripe_price_id' => $plan->stripe_price_id,
                'stripe_annual_price_id' => $plan->stripe_annual_price_id,
            ]);

        return [
            'plans' => $plans,
            'headers' => $this->headers(),
            'totalPlans' => Plan::count(),
            'activePlans' => Plan::where('is_active', true)->count(),
            'totalSubscribers' => \App\Models\User::whereNotNull('plan_id')->count(),
        ];
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Plan Management" separator progress-indicator>
        <x-slot:subtitle>
            Manage subscription plans, pricing, and features
        </x-slot:subtitle>
        <x-slot:middle class="justify-end!">
            <x-input placeholder="Search plans..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
    </x-header>

    <!-- SUMMARY CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <!-- Total Plans -->
        <x-card class="bg-base-100 border border-base-content/10 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Total Plans</div>
                    <div class="text-3xl font-bold text-primary">{{ $totalPlans }}</div>
                </div>
                <x-icon name="o-rectangle-stack" class="w-12 h-12 text-primary/80" />
            </div>
        </x-card>

        <!-- Active Plans -->
        <x-card class="bg-base-100 border border-base-content/10 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Active Plans</div>
                    <div class="text-3xl font-bold text-success">{{ $activePlans }}</div>
                </div>
                <x-icon name="o-check-circle" class="w-12 h-12 text-success/80" />
            </div>
        </x-card>

        <!-- Total Subscribers -->
        <x-card class="bg-base-100 border border-base-content/10 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Total Subscribers</div>
                    <div class="text-3xl font-bold text-info">{{ $totalSubscribers }}</div>
                </div>
                <x-icon name="o-user-group" class="w-12 h-12 text-info/80" />
            </div>
        </x-card>
    </div>

    <!-- PLANS TABLE -->
    <x-card class="border border-base-content/10" shadow>
        <x-table :headers="$headers" :rows="$plans" :sort-by="$sortBy" class="bg-base-100">
            @scope('cell_sort_order', $plan)
                <span class="text-base-content/60 font-mono">{{ $plan['sort_order'] }}</span>
            @endscope

            @scope('cell_name', $plan)
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold">{{ $plan['name'] }}</span>
                        @if($plan['slug'] === 'free')
                            <span class="badge badge-sm badge-ghost">Free Tier</span>
                        @endif
                    </div>
                    <span class="text-xs text-base-content/50">{{ $plan['slug'] }}</span>
                </div>
            @endscope

            @scope('cell_price', $plan)
                <div class="text-right font-semibold">
                    @if((float)$plan['price'] === 0.00)
                        <span class="text-base-content/60">Free</span>
                    @else
                        ${{ number_format((float)$plan['price'], 2) }}
                    @endif
                </div>
            @endscope

            @scope('cell_annual_price', $plan)
                <div class="text-right">
                    @if($plan['annual_price'])
                        ${{ number_format((float)$plan['annual_price'], 2) }}
                        <div class="text-xs text-base-content/50">${{ number_format((float)$plan['annual_price'] / 12, 2) }}/mo</div>
                    @else
                        <span class="text-base-content/40">—</span>
                    @endif
                </div>
            @endscope

            @scope('cell_apartment_limit', $plan)
                <div class="text-center">
                    @if($plan['apartment_limit'] === 0)
                        <span class="badge badge-sm bg-teal-500/10 text-teal-500 border-teal-500/20">Unlimited</span>
                    @else
                        <span class="badge badge-ghost badge-sm">{{ $plan['apartment_limit'] }}</span>
                    @endif
                </div>
            @endscope

            @scope('cell_tenant_limit', $plan)
                <div class="text-center">
                    @if($plan['tenant_limit'] === 0)
                        <span class="badge badge-sm bg-teal-500/10 text-teal-500 border-teal-500/20">Unlimited</span>
                    @else
                        <span class="badge badge-ghost badge-sm">{{ $plan['tenant_limit'] }}</span>
                    @endif
                </div>
            @endscope

            @scope('cell_subscribers', $plan)
                <div class="text-center">
                    <span class="badge badge-ghost badge-sm">{{ $plan['subscribers'] }}</span>
                </div>
            @endscope

            @scope('cell_is_active', $plan)
                <div class="text-center">
                    @if($plan['is_active'])
                        <span class="badge badge-sm badge-success gap-1">
                            <x-icon name="o-check" class="w-3 h-3" /> Active
                        </span>
                    @else
                        <span class="badge badge-sm badge-error gap-1">
                            <x-icon name="o-x-mark" class="w-3 h-3" /> Inactive
                        </span>
                    @endif
                </div>
            @endscope

            @scope('actions', $plan)
                <div class="flex items-center gap-1">
                    <x-button icon="o-pencil-square" link="/admin/plans/{{ $plan['id'] }}/edit" class="btn-ghost btn-sm" spinner tooltip="Edit" />
                    <x-button 
                        icon="{{ $plan['is_active'] ? 'o-eye-slash' : 'o-eye' }}" 
                        wire:click="toggleActive({{ $plan['id'] }})" 
                        class="btn-ghost btn-sm" 
                        spinner 
                        tooltip="{{ $plan['is_active'] ? 'Deactivate' : 'Activate' }}" 
                    />
                </div>
            @endscope
        </x-table>
    </x-card>
</div>
