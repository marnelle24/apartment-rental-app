<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use Mary\Traits\Toast;
use App\Traits\AuthorizesRole;
use App\Models\Apartment;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use Toast;
    use AuthorizesRole;
    use WithPagination;
    use WithoutUrlPagination;


    public int $location_id = 0;
    public bool $drawer = false;

    // Check owner access on mount
    public function mount(): void
    {
        $this->authorizeRole('owner');
    }

    // Reset pagination when location filter changes
    public function updatedLocationId(): void
    {
        $this->resetPage();
    }

    // Clear filters
    public function clear(): void
    {
        $this->reset(['location_id']);
        $this->resetPage(); // Reset pagination when clearing filters
        $this->success('Filters cleared.', position: 'toast-bottom');
    }

    // Get occupancy statistics
    public function getOccupancyStats(): array
    {
        $query = Apartment::query()->where('owner_id', auth()->id());

        if ($this->location_id) {
            $query->where('location_id', $this->location_id);
        }

        $totalApartments = $query->count();
        $occupied = $query->where('status', 'occupied')->count();
        $available = $query->where('status', 'available')->count();
        $maintenance = $query->where('status', 'maintenance')->count();

        $occupancyRate = $totalApartments > 0 ? ($occupied / $totalApartments) * 100 : 0;

        return [
            'total' => $totalApartments,
            'occupied' => $occupied,
            'available' => $available,
            'maintenance' => $maintenance,
            'occupancy_rate' => $occupancyRate,
        ];
    }

    // Get occupancy by location
    public function getOccupancyByLocation(): array
    {
        $query = Apartment::query()
            ->select(
                'locations.id',
                'locations.name',
                DB::raw('COUNT(apartments.id) as total'),
                DB::raw('SUM(CASE WHEN apartments.status = "occupied" THEN 1 ELSE 0 END) as occupied'),
                DB::raw('SUM(CASE WHEN apartments.status = "available" THEN 1 ELSE 0 END) as available'),
                DB::raw('SUM(CASE WHEN apartments.status = "maintenance" THEN 1 ELSE 0 END) as maintenance')
            )
            ->join('locations', 'apartments.location_id', '=', 'locations.id')
            ->where('apartments.owner_id', auth()->id())
            ->groupBy('locations.id', 'locations.name')
            ->orderBy('locations.name');

        if ($this->location_id) {
            $query->where('locations.id', $this->location_id);
        }

        return $query->get()
            ->map(function ($item) {
                $occupancyRate = $item->total > 0 ? ($item->occupied / $item->total) * 100 : 0;
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'total' => $item->total,
                    'occupied' => $item->occupied,
                    'available' => $item->available,
                    'maintenance' => $item->maintenance,
                    'occupancy_rate' => $occupancyRate,
                ];
            })
            ->toArray();
    }

    // Get apartments with tenant count (paginated)
    public function getApartmentsWithTenants()
    {
        $query = Apartment::query()
            ->where('owner_id', auth()->id())
            ->withCount('tenants')
            ->withCount(['tenants as active_tenants_count' => fn ($q) => $q->where('status', 'active')])
            ->with(['location']);

        if ($this->location_id) {
            $query->where('location_id', $this->location_id);
        }

        return $query->orderBy('name')
            ->paginate(10)
            ->through(function ($apartment) {
                return [
                    'id' => $apartment->id,
                    'name' => $apartment->name,
                    'location_name' => $apartment->location->name ?? '—',
                    'status' => $apartment->status,
                    'tenants_count' => $apartment->tenants_count,
                    'active_tenants_count' => $apartment->active_tenants_count,
                    'monthly_rent' => $apartment->monthly_rent,
                ];
            });
    }

    public function with(): array
    {
        $stats = $this->getOccupancyStats();
        $byLocation = $this->getOccupancyByLocation();
        $apartments = $this->getApartmentsWithTenants();

        return [
            'stats' => $stats,
            'byLocation' => $byLocation,
            'apartments' => $apartments,
            'locations' => Location::all(),
        ];
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Occupancy Report" separator progress-indicator>
        <x-slot:middle class="justify-end!">
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Back" link="/reports" icon="o-arrow-left" class="btn-ghost" responsive />
        </x-slot:actions>
    </x-header>

    <!-- STATISTICS CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-card class="bg-primary text-primary-content">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm opacity-80">Total Apartments</div>
                    <div class="text-3xl font-bold">{{ $stats['total'] }}</div>
                    <div class="text-sm opacity-70 mt-1">All properties</div>
                </div>
                <x-icon name="o-building-office" class="w-12 h-12 opacity-50" />
            </div>
        </x-card>

        <x-card class="bg-success text-success-content">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm opacity-80">Occupied</div>
                    <div class="text-3xl font-bold">{{ $stats['occupied'] }}</div>
                    <div class="text-sm opacity-70 mt-1">{{ number_format($stats['occupancy_rate'], 1) }}% rate</div>
                </div>
                <x-icon name="o-check-circle" class="w-12 h-12 opacity-50" />
            </div>
        </x-card>

        <x-card class="bg-info text-info-content">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm opacity-80">Available</div>
                    <div class="text-3xl font-bold">{{ $stats['available'] }}</div>
                    <div class="text-sm opacity-70 mt-1">Ready to rent</div>
                </div>
                <x-icon name="o-home" class="w-12 h-12 opacity-50" />
            </div>
        </x-card>

        <x-card class="bg-warning text-warning-content">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm opacity-80">Maintenance</div>
                    <div class="text-3xl font-bold">{{ $stats['maintenance'] }}</div>
                    <div class="text-sm opacity-70 mt-1">Under repair</div>
                </div>
                <x-icon name="o-wrench-screwdriver" class="w-12 h-12 opacity-50" />
            </div>
        </x-card>
    </div>

    <!-- OCCUPANCY RATE CARD -->
    <x-card title="Overall Occupancy Rate" shadow class="mb-6">
        <div class="flex items-center gap-6">
            <div class="flex-1">
                <div class="text-5xl font-bold text-primary">{{ number_format($stats['occupancy_rate'], 1) }}%</div>
                <div class="text-sm text-base-content/70 mt-2">
                    {{ $stats['occupied'] }} of {{ $stats['total'] }} apartments are currently occupied
                </div>
            </div>
            <div class="w-32 h-32 relative">
                <svg class="transform -rotate-90 w-32 h-32">
                    <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="8" fill="none" class="text-base-300" />
                    <circle 
                        cx="64" 
                        cy="64" 
                        r="56" 
                        stroke="currentColor" 
                        stroke-width="8" 
                        fill="none" 
                        class="text-primary"
                        stroke-dasharray="{{ 2 * pi() * 56 }}"
                        stroke-dashoffset="{{ 2 * pi() * 56 * (1 - $stats['occupancy_rate'] / 100) }}"
                    />
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-2xl font-bold">{{ number_format($stats['occupancy_rate'], 0) }}%</span>
                </div>
            </div>
        </div>
    </x-card>

    <!-- OCCUPANCY BY LOCATION -->
    <x-card title="Occupancy by Location" shadow class="mb-6">
        @if(count($byLocation) > 0)
            <x-table 
                :headers="[
                    ['key' => 'name', 'label' => 'Location'],
                    ['key' => 'total', 'label' => 'Total'],
                    ['key' => 'occupied', 'label' => 'Occupied'],
                    ['key' => 'available', 'label' => 'Available'],
                    ['key' => 'maintenance', 'label' => 'Maintenance'],
                    ['key' => 'occupancy_rate', 'label' => 'Occupancy Rate'],
                ]"
                :rows="$byLocation"
            >
                @scope('cell_occupied', $row)
                    <div class="badge badge-success">{{ $row['occupied'] }}</div>
                @endscope

                @scope('cell_available', $row)
                    <div class="badge badge-info">{{ $row['available'] }}</div>
                @endscope

                @scope('cell_maintenance', $row)
                    <div class="badge badge-warning">{{ $row['maintenance'] }}</div>
                @endscope

                @scope('cell_occupancy_rate', $row)
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-base-300 rounded-full h-2 w-24">
                            <div class="bg-primary h-2 rounded-full" style="width: {{ $row['occupancy_rate'] }}%"></div>
                        </div>
                        <span class="text-sm font-semibold">{{ number_format($row['occupancy_rate'], 1) }}%</span>
                    </div>
                @endscope
            </x-table>
        @else
            <div class="text-center text-base-content/50 py-8">No location data available</div>
        @endif
    </x-card>

    <!-- APARTMENTS TABLE -->
    <x-card id="apartments-table" title="Apartment Details" shadow>
        @if($apartments->count() > 0)
            <x-table 
                :headers="[
                    ['key' => 'name', 'label' => 'Apartment'],
                    ['key' => 'location_name', 'label' => 'Location'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'tenants_count', 'label' => 'Tenants'],
                    ['key' => 'monthly_rent', 'label' => 'Monthly Rent'],
                ]"
                :rows="$apartments"
                link="/apartments/{id}"
            >
                @scope('cell_status', $apartment)
                    @php
                        // Show "available" only when there are no active tenants, otherwise "occupied"
                        $displayStatus = ($apartment['active_tenants_count'] ?? 0) > 0 ? 'occupied' : 'available';
                        $statusColors = [
                            'available' => 'badge-success',
                            'occupied' => 'badge-info',
                            'maintenance' => 'badge-warning',
                        ];
                        $color = $statusColors[$displayStatus] ?? 'badge-ghost';
                    @endphp
                    <div class="badge {{ $color }}">
                        {{ ucfirst($displayStatus) }}
                    </div>
                @endscope

                @scope('cell_tenants_count', $apartment)
                    <div class="badge badge-ghost">{{ $apartment['tenants_count'] }}</div>
                @endscope

                @scope('cell_monthly_rent', $apartment)
                    <div class="font-semibold">₱{{ number_format($apartment['monthly_rent'], 2) }}</div>
                @endscope
            </x-table>
            
            <!-- Pagination -->
            <div class="mt-4" wire:ignore>
                {{ $apartments->links(data: ['scrollTo' => '#apartments-table']) }}
            </div>
        @else
            <div class="text-center text-base-content/50 py-8">No apartments available</div>
        @endif
    </x-card>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <div class="grid gap-5">
            <x-select 
                placeholder="Filter by Location" 
                wire:model.live="location_id" 
                :options="$locations" 
                icon="o-map-pin" 
                placeholder-value="0" 
            />
        </div>
        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>

    <!-- Script to prevent scroll to top when clicking pagination -->
    <script>
        document.addEventListener('livewire:init', () => {
            let savedScrollPosition = 0;

            // Save scroll position before Livewire updates
            Livewire.hook('morph.updating', () => {
                savedScrollPosition = window.pageYOffset || document.documentElement.scrollTop;
            });

            // Restore scroll position after Livewire updates
            Livewire.hook('morph.updated', () => {
                setTimeout(() => {
                    window.scrollTo(0, savedScrollPosition);
                }, 100);
            });
        });
    </script>
</div>
