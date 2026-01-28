<?php

use App\Models\Location;
use App\Models\Apartment;
use App\Models\User;
use App\Models\Tenant;
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

    <!-- QUICK ACTIONS -->
    <x-card title="Quick Actions" class="border border-base-content/10" shadow separator>
        <div class="flex flex-wrap gap-3">
            <x-button label="Manage Locations" link="/locations" icon="o-map-pin" class="btn-primary" />
            <x-button label="View All Owners" link="/users?role=owner" icon="o-user-group" class="btn-ghost" />
            <x-button label="System Analytics" icon="o-chart-bar" class="btn-ghost" disabled />
        </div>
    </x-card>
</div>
