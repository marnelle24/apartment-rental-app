<?php

use App\Models\Location;
use App\Models\Apartment;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Plan;
use Livewire\Component;
use Mary\Traits\Toast;
use App\Traits\AuthorizesRole;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use Toast;
    use AuthorizesRole;

    // Check admin access on mount
    public function mount(): void
    {
        $this->authorizeRole('admin');
    }

    // System Statistics
    public function getTotalLocations(): int
    {
        return Location::count();
    }

    public function getTotalApartments(): int
    {
        return Apartment::count();
    }

    public function getTotalOwners(): int
    {
        return User::where('role', 'owner')->count();
    }

    public function getTotalTenants(): int
    {
        return Tenant::where('status', 'active')->count();
    }

    public function getOccupancyRate(): float
    {
        $totalApartments = $this->getTotalApartments();
        if ($totalApartments === 0) {
            return 0;
        }
        
        $occupiedApartments = Apartment::where('status', 'occupied')->count();
        return round(($occupiedApartments / $totalApartments) * 100, 2);
    }

    // Location Performance
    public function getLocationStats(): array
    {
        return Location::with('apartments')
            ->get()
            ->map(function($location) {
                $apartments = $location->apartments;
                $total = $apartments->count();
                $occupied = $apartments->where('status', 'occupied')->count();
                $avgRent = $apartments->avg('monthly_rent') ?? 0;
                $occupancyRate = $total > 0 ? round(($occupied / $total) * 100, 2) : 0;

                return [
                    'name' => $location->name,
                    'total_apartments' => $total,
                    'occupied_apartments' => $occupied,
                    'avg_rent' => round($avgRent, 2),
                    'occupancy_rate' => $occupancyRate,
                ];
            })
            ->sortByDesc('total_apartments')
            ->values()
            ->toArray();
    }

    // Activity Feed
    public function getRecentApartments(): array
    {
        return Apartment::with(['owner', 'location'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($apartment) {
                return [
                    'id' => $apartment->id,
                    'name' => $apartment->name,
                    'owner' => $apartment->owner->name ?? 'N/A',
                    'location' => $apartment->location->name ?? 'N/A',
                    'created_at' => $apartment->created_at,
                ];
            })
            ->toArray();
    }

    public function getRecentOwners(): array
    {
        return User::where('role', 'owner')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'apartments_count' => $user->apartments()->count(),
                    'created_at' => $user->created_at,
                ];
            })
            ->toArray();
    }

    // Subscription Analytics
    public function getSubscriptionStats(): array
    {
        $totalPaidSubscribers = User::where('role', 'owner')
            ->whereNotNull('plan_id')
            ->whereHas('plan', fn($q) => $q->where('slug', '!=', 'free'))
            ->count();

        $freeUsers = User::where('role', 'owner')
            ->where(function ($q) {
                $q->whereNull('plan_id')
                  ->orWhereHas('plan', fn($p) => $p->where('slug', 'free'));
            })
            ->count();

        $planDistribution = Plan::withCount(['users' => fn($q) => $q->where('role', 'owner')])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($plan) => [
                'name' => $plan->name,
                'slug' => $plan->slug,
                'count' => $plan->users_count,
                'price' => $plan->price,
            ])
            ->toArray();

        // Estimate MRR from plan assignments
        $estimatedMrr = 0;
        foreach ($planDistribution as $plan) {
            if ($plan['slug'] !== 'free') {
                $estimatedMrr += $plan['count'] * (float) $plan['price'];
            }
        }

        return [
            'total_paid' => $totalPaidSubscribers,
            'free_users' => $freeUsers,
            'plan_distribution' => $planDistribution,
            'estimated_mrr' => $estimatedMrr,
        ];
    }

    public function with(): array
    {
        return [
            'totalLocations' => $this->getTotalLocations(),
            'totalApartments' => $this->getTotalApartments(),
            'totalOwners' => $this->getTotalOwners(),
            'totalTenants' => $this->getTotalTenants(),
            'occupancyRate' => $this->getOccupancyRate(),
            'locationStats' => $this->getLocationStats(),
            'recentApartments' => $this->getRecentApartments(),
            'recentOwners' => $this->getRecentOwners(),
            'subscriptionStats' => $this->getSubscriptionStats(),
        ];
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Admin Dashboard" separator progress-indicator>
        <x-slot:subtitle>
            System-wide statistics and monitoring
        </x-slot:subtitle>
    </x-header>

    <!-- SYSTEM STATISTICS CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <!-- Total Locations -->
        <x-card class="bg-base-100 border border-base-content/10 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Total Locations</div>
                    <div class="text-3xl font-bold text-primary">{{ $totalLocations }}</div>
                </div>
                <x-icon name="o-map-pin" class="w-12 h-12 text-primary/80" />
            </div>
        </x-card>

        <!-- Total Apartments -->
        <x-card class="bg-base-100 border border-base-content/10 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Total Apartments</div>
                    <div class="text-3xl font-bold text-info">{{ $totalApartments }}</div>
                </div>
                <x-icon name="o-building-office" class="w-12 h-12 text-info/80" />
            </div>
        </x-card>

        <!-- Total Owners -->
        <x-card class="bg-base-100 border border-base-content/10 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Total Owners</div>
                    <div class="text-3xl font-bold text-success">{{ $totalOwners }}</div>
                </div>
                <x-icon name="o-user-group" class="w-12 h-12 text-success/80" />
            </div>
        </x-card>

        <!-- Total Tenants -->
        <x-card class="bg-base-100 border border-base-content/10 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Active Tenants</div>
                    <div class="text-3xl font-bold text-warning">{{ $totalTenants }}</div>
                </div>
                <x-icon name="o-users" class="w-12 h-12 text-warning/80" />
            </div>
        </x-card>

        <!-- Occupancy Rate -->
        <x-card class="bg-base-100 border border-base-content/10 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Occupancy Rate</div>
                    <div class="text-3xl font-bold text-accent">{{ $occupancyRate }}%</div>
                </div>
                <x-icon name="o-chart-bar" class="w-12 h-12 text-accent/80" />
            </div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- LOCATION PERFORMANCE -->
        <x-card title="Location Performance" class="border border-base-content/10" shadow separator>
            <div class="overflow-x-auto">
                <x-table :headers="[
                    ['key' => 'name', 'label' => 'Location'],
                    ['key' => 'total_apartments', 'label' => 'Apartments'],
                    ['key' => 'avg_rent', 'label' => 'Avg Rent'],
                    ['key' => 'occupancy_rate', 'label' => 'Occupancy'],
                ]" :rows="$locationStats" no-pagination>
                    @scope('cell_avg_rent', $location)
                        <div class="font-semibold">
                            ₱{{ number_format($location['avg_rent'], 2) }}
                        </div>
                    @endscope

                    @scope('cell_occupancy_rate', $location)
                        <div class="flex items-center gap-2">
                            <div class="flex-1">
                                <div class="w-full bg-base-200 rounded-full h-2">
                                    <div 
                                        class="h-2 rounded-full transition-all {{ $location['occupancy_rate'] >= 80 ? 'bg-success' : ($location['occupancy_rate'] >= 50 ? 'bg-warning' : 'bg-error') }}"
                                        style="width: {{ $location['occupancy_rate'] }}%"
                                    ></div>
                                </div>
                            </div>
                            <span class="text-sm font-medium min-w-12 text-right">
                                {{ $location['occupancy_rate'] }}%
                            </span>
                        </div>
                    @endscope

                    @scope('cell_total_apartments', $location)
                        <div class="badge badge-ghost">
                            {{ $location['total_apartments'] }}
                        </div>
                    @endscope
                </x-table>
            </div>
        </x-card>

        <!-- ACTIVITY FEED -->
        <x-card title="Recent Activity" class="border border-base-content/10" shadow separator>
            <div class="space-y-4">
                <!-- Recent Apartments -->
                <div>
                    <h3 class="font-semibold text-sm text-base-content/70 mb-2 flex items-center gap-2">
                        <x-icon name="o-building-office" class="w-4 h-4" />
                        Recent Apartments
                    </h3>
                    <div class="space-y-2">
                        @forelse($recentApartments as $apartment)
                            <div class="flex items-center justify-between p-2 rounded-lg bg-base-200/50 hover:bg-base-200 transition-colors">
                                <div class="flex-1">
                                    <div class="font-medium text-sm">{{ $apartment['name'] }}</div>
                                    <div class="text-xs text-base-content/60">
                                        {{ $apartment['location'] }} • {{ $apartment['owner'] }}
                                    </div>
                                </div>
                                <div class="text-xs text-base-content/50">
                                    {{ $apartment['created_at']->diffForHumans() }}
                                </div>
                            </div>
                        @empty
                            <div class="text-sm text-base-content/50 text-center py-4">
                                No apartments yet
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Recent Owners -->
                <div class="pt-4 border-t border-base-300">
                    <h3 class="font-semibold text-sm text-base-content/70 mb-2 flex items-center gap-2">
                        <x-icon name="o-user-group" class="w-4 h-4" />
                        New Owners
                    </h3>
                    <div class="space-y-2">
                        @forelse($recentOwners as $owner)
                            <div class="flex items-center justify-between p-2 rounded-lg bg-base-200/50 hover:bg-base-200 transition-colors">
                                <div class="flex-1">
                                    <div class="font-medium text-sm">{{ $owner['name'] }}</div>
                                    <div class="text-xs text-base-content/60">
                                        {{ $owner['email'] }} • {{ $owner['apartments_count'] }} apartment(s)
                                    </div>
                                </div>
                                <div class="text-xs text-base-content/50">
                                    {{ $owner['created_at']->diffForHumans() }}
                                </div>
                            </div>
                        @empty
                            <div class="text-sm text-base-content/50 text-center py-4">
                                No owners yet
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </x-card>
    </div>

    <!-- SUBSCRIPTION ANALYTICS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Subscription Summary --}}
        <x-card title="Subscription Overview" class="border border-base-content/10" shadow separator>
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="p-4 rounded-lg bg-teal-500/10 border border-teal-500/20">
                    <div class="text-sm text-base-content/70 mb-1">Paid Subscribers</div>
                    <div class="text-3xl font-bold text-teal-500">{{ $subscriptionStats['total_paid'] }}</div>
                </div>
                <div class="p-4 rounded-lg bg-base-200 border border-base-content/10">
                    <div class="text-sm text-base-content/70 mb-1">Free Users</div>
                    <div class="text-3xl font-bold text-base-content/70">{{ $subscriptionStats['free_users'] }}</div>
                </div>
            </div>
            <div class="p-4 rounded-lg bg-success/10 border border-success/20">
                <div class="text-sm text-base-content/70 mb-1">Estimated MRR</div>
                <div class="text-3xl font-bold text-success">${{ number_format($subscriptionStats['estimated_mrr'], 2) }}</div>
                <div class="text-xs text-base-content/50 mt-1">Based on current active plan assignments</div>
            </div>
        </x-card>

        {{-- Plan Distribution --}}
        <x-card title="Plan Distribution" class="border border-base-content/10" shadow separator>
            <div class="space-y-4">
                @php
                    $totalUsers = collect($subscriptionStats['plan_distribution'])->sum('count');
                @endphp
                @foreach($subscriptionStats['plan_distribution'] as $plan)
                    @php
                        $percent = $totalUsers > 0 ? round(($plan['count'] / $totalUsers) * 100, 1) : 0;
                    @endphp
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium">
                                {{ $plan['name'] }}
                                @if($plan['slug'] !== 'free')
                                    <span class="text-base-content/50">${{ number_format((float) $plan['price'], 0) }}/mo</span>
                                @endif
                            </span>
                            <span class="text-base-content/70">{{ $plan['count'] }} users ({{ $percent }}%)</span>
                        </div>
                        <div class="w-full bg-base-200 rounded-full h-3">
                            <div 
                                class="h-3 rounded-full transition-all {{ $plan['slug'] === 'free' ? 'bg-base-content/30' : ($plan['slug'] === 'business' ? 'bg-teal-500' : ($plan['slug'] === 'professional' ? 'bg-teal-400' : 'bg-teal-300')) }}"
                                style="width: {{ max($percent, 2) }}%"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 pt-4 border-t border-base-content/10">
                <x-button label="Manage Plans" icon="o-rectangle-stack" link="/admin/plans" class="btn-ghost btn-sm" />
            </div>
        </x-card>
    </div>

    <!-- QUICK ACTIONS -->
    <x-card title="Quick Actions" class="border border-base-content/10" shadow separator>
        <div class="flex flex-wrap gap-3">
            <x-button label="Manage Locations" link="/locations" icon="o-map-pin" class="btn-primary" />
            <x-button label="Manage Plans" link="/admin/plans" icon="o-rectangle-stack" class="btn-ghost" />
            <x-button label="View All Owners" link="/users?role=owner" icon="o-user-group" class="btn-ghost" />
        </div>
    </x-card>
</div>
