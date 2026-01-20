<?php

use Livewire\Component;
use Mary\Traits\Toast;
use App\Traits\AuthorizesRole;
use App\Models\Tenant;
use App\Models\Apartment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use Toast;
    use AuthorizesRole;

    public int $year = 0;
    public int $location_id = 0;
    public bool $drawer = false;

    // Check owner access on mount
    public function mount(): void
    {
        $this->authorizeRole('owner');
        $this->year = now()->year;
    }

    // Clear filters
    public function clear(): void
    {
        $this->year = now()->year;
        $this->location_id = 0;
        $this->success('Filters cleared.', position: 'toast-bottom');
    }

    // Get turnover statistics
    public function getTurnoverStats(): array
    {
        $query = Tenant::query()->where('owner_id', auth()->id());

        if ($this->year) {
            $query->whereYear('move_in_date', '<=', $this->year)
                  ->where(function($q) {
                      $q->whereYear('move_in_date', $this->year)
                        ->orWhereNull('lease_end_date')
                        ->orWhereYear('lease_end_date', '>=', $this->year);
                  });
        }

        if ($this->location_id) {
            $query->whereHas('apartment', fn($q) => $q->where('location_id', $this->location_id));
        }

        $totalTenants = $query->count();
        $activeTenants = $query->where('status', 'active')->count();
        $inactiveTenants = $query->where('status', 'inactive')->count();

        // New tenants this year
        $newTenantsThisYear = Tenant::query()
            ->where('owner_id', auth()->id())
            ->whereYear('move_in_date', $this->year);

        if ($this->location_id) {
            $newTenantsThisYear->whereHas('apartment', fn($q) => $q->where('location_id', $this->location_id));
        }

        $newTenantsCount = $newTenantsThisYear->count();

        // Tenants who moved out this year
        $movedOutThisYear = Tenant::query()
            ->where('owner_id', auth()->id())
            ->whereYear('lease_end_date', $this->year)
            ->where('status', 'inactive');

        if ($this->location_id) {
            $movedOutThisYear->whereHas('apartment', fn($q) => $q->where('location_id', $this->location_id));
        }

        $movedOutCount = $movedOutThisYear->count();

        // Retention rate (active tenants / total tenants)
        $retentionRate = $totalTenants > 0 ? ($activeTenants / $totalTenants) * 100 : 0;

        return [
            'total' => $totalTenants,
            'active' => $activeTenants,
            'inactive' => $inactiveTenants,
            'new_this_year' => $newTenantsCount,
            'moved_out_this_year' => $movedOutCount,
            'retention_rate' => $retentionRate,
        ];
    }

    // Get monthly move-ins and move-outs
    public function getMonthlyTurnover(): array
    {
        $months = [];
        $moveIns = [];
        $moveOuts = [];

        for ($i = 1; $i <= 12; $i++) {
            $monthStart = Carbon::create($this->year, $i, 1)->startOfMonth();
            $monthEnd = Carbon::create($this->year, $i, 1)->endOfMonth();

            $moveInQuery = Tenant::query()
                ->where('owner_id', auth()->id())
                ->whereBetween('move_in_date', [$monthStart, $monthEnd]);

            $moveOutQuery = Tenant::query()
                ->where('owner_id', auth()->id())
                ->whereBetween('lease_end_date', [$monthStart, $monthEnd])
                ->where('status', 'inactive');

            if ($this->location_id) {
                $moveInQuery->whereHas('apartment', fn($q) => $q->where('location_id', $this->location_id));
                $moveOutQuery->whereHas('apartment', fn($q) => $q->where('location_id', $this->location_id));
            }

            $months[] = $monthStart->format('M');
            $moveIns[] = $moveInQuery->count();
            $moveOuts[] = $moveOutQuery->count();
        }

        return [
            'months' => $months,
            'move_ins' => $moveIns,
            'move_outs' => $moveOuts,
        ];
    }

    // Get tenants expiring soon
    public function getExpiringSoon(): array
    {
        $query = Tenant::query()
            ->where('owner_id', auth()->id())
            ->where('status', 'active')
            ->whereNotNull('lease_end_date')
            ->whereBetween('lease_end_date', [now(), now()->addDays(90)])
            ->with(['apartment.location'])
            ->orderBy('lease_end_date');

        if ($this->location_id) {
            $query->whereHas('apartment', fn($q) => $q->where('location_id', $this->location_id));
        }

        return $query->get()
            ->map(function ($tenant) {
                $daysUntilExpiry = now()->diffInDays($tenant->lease_end_date, false);
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'apartment_name' => $tenant->apartment->name ?? '—',
                    'location_name' => $tenant->apartment->location->name ?? '—',
                    'lease_end_date' => $tenant->lease_end_date->format('Y-m-d'),
                    'days_until_expiry' => $daysUntilExpiry,
                ];
            })
            ->toArray();
    }

    // Get average tenancy duration
    public function getAverageTenancyDuration(): array
    {
        $query = Tenant::query()
            ->where('owner_id', auth()->id())
            ->whereNotNull('lease_end_date')
            ->where('status', 'inactive');

        if ($this->location_id) {
            $query->whereHas('apartment', fn($q) => $q->where('location_id', $this->location_id));
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            return [
                'average_months' => 0,
                'average_days' => 0,
            ];
        }

        $totalDays = $tenants->sum(function ($tenant) {
            return $tenant->move_in_date->diffInDays($tenant->lease_end_date);
        });

        $averageDays = $totalDays / $tenants->count();
        $averageMonths = $averageDays / 30;

        return [
            'average_months' => round($averageMonths, 1),
            'average_days' => round($averageDays, 0),
        ];
    }

    // Get turnover by location
    public function getTurnoverByLocation(): array
    {
        $query = Tenant::query()
            ->select(
                'locations.id',
                'locations.name',
                DB::raw('COUNT(tenants.id) as total'),
                DB::raw('SUM(CASE WHEN tenants.status = "active" THEN 1 ELSE 0 END) as active'),
                DB::raw('SUM(CASE WHEN tenants.status = "inactive" THEN 1 ELSE 0 END) as inactive'),
                DB::raw('SUM(CASE WHEN YEAR(tenants.move_in_date) = ' . $this->year . ' THEN 1 ELSE 0 END) as new_this_year'),
                DB::raw('SUM(CASE WHEN YEAR(tenants.lease_end_date) = ' . $this->year . ' AND tenants.status = "inactive" THEN 1 ELSE 0 END) as moved_out_this_year')
            )
            ->join('apartments', 'tenants.apartment_id', '=', 'apartments.id')
            ->join('locations', 'apartments.location_id', '=', 'locations.id')
            ->where('tenants.owner_id', auth()->id())
            ->groupBy('locations.id', 'locations.name')
            ->orderBy('locations.name');

        if ($this->location_id) {
            $query->where('locations.id', $this->location_id);
        }

        return $query->get()
            ->map(function ($item) {
                $retentionRate = $item->total > 0 ? ($item->active / $item->total) * 100 : 0;
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'total' => $item->total,
                    'active' => $item->active,
                    'inactive' => $item->inactive,
                    'new_this_year' => $item->new_this_year,
                    'moved_out_this_year' => $item->moved_out_this_year,
                    'retention_rate' => $retentionRate,
                ];
            })
            ->toArray();
    }

    public function with(): array
    {
        $stats = $this->getTurnoverStats();
        $monthly = $this->getMonthlyTurnover();
        $expiring = $this->getExpiringSoon();
        $avgDuration = $this->getAverageTenancyDuration();
        $byLocation = $this->getTurnoverByLocation();

        return [
            'stats' => $stats,
            'monthly' => $monthly,
            'expiring' => $expiring,
            'avgDuration' => $avgDuration,
            'byLocation' => $byLocation,
            'years' => range(now()->year - 5, now()->year),
            'locations' => \App\Models\Location::all(),
        ];
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Tenant Turnover Report" separator progress-indicator>
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
                    <div class="text-sm opacity-80">Total Tenants</div>
                    <div class="text-3xl font-bold">{{ $stats['total'] }}</div>
                    <div class="text-sm opacity-70 mt-1">All time</div>
                </div>
                <x-icon name="o-user-group" class="w-12 h-12 opacity-50" />
            </div>
        </x-card>

        <x-card class="bg-success text-success-content">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm opacity-80">Active Tenants</div>
                    <div class="text-3xl font-bold">{{ $stats['active'] }}</div>
                    <div class="text-sm opacity-70 mt-1">{{ number_format($stats['retention_rate'], 1) }}% retention</div>
                </div>
                <x-icon name="o-check-circle" class="w-12 h-12 opacity-50" />
            </div>
        </x-card>

        <x-card class="bg-info text-info-content">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm opacity-80">New in {{ $year }}</div>
                    <div class="text-3xl font-bold">{{ $stats['new_this_year'] }}</div>
                    <div class="text-sm opacity-70 mt-1">Move-ins</div>
                </div>
                <x-icon name="o-arrow-down-circle" class="w-12 h-12 opacity-50" />
            </div>
        </x-card>

        <x-card class="bg-warning text-warning-content">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm opacity-80">Moved Out {{ $year }}</div>
                    <div class="text-3xl font-bold">{{ $stats['moved_out_this_year'] }}</div>
                    <div class="text-sm opacity-70 mt-1">Move-outs</div>
                </div>
                <x-icon name="o-arrow-up-circle" class="w-12 h-12 opacity-50" />
            </div>
        </x-card>
    </div>

    <!-- ADDITIONAL STATS -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <x-card title="Average Tenancy Duration" shadow>
            <div class="flex items-center gap-6">
                <div class="flex-1">
                    <div class="text-4xl font-bold text-primary">{{ $avgDuration['average_months'] }} months</div>
                    <div class="text-sm text-base-content/70 mt-2">
                        Average of {{ number_format($avgDuration['average_days']) }} days per tenancy
                    </div>
                </div>
                <x-icon name="o-clock" class="w-16 h-16 text-primary opacity-30" />
            </div>
        </x-card>

        <x-card title="Retention Rate" shadow>
            <div class="flex items-center gap-6">
                <div class="flex-1">
                    <div class="text-4xl font-bold text-success">{{ number_format($stats['retention_rate'], 1) }}%</div>
                    <div class="text-sm text-base-content/70 mt-2">
                        {{ $stats['active'] }} active out of {{ $stats['total'] }} total tenants
                    </div>
                </div>
                <div class="w-24 h-24 relative">
                    <svg class="transform -rotate-90 w-24 h-24">
                        <circle cx="48" cy="48" r="42" stroke="currentColor" stroke-width="6" fill="none" class="text-base-300" />
                        <circle 
                            cx="48" 
                            cy="48" 
                            r="42" 
                            stroke="currentColor" 
                            stroke-width="6" 
                            fill="none" 
                            class="text-success"
                            stroke-dasharray="{{ 2 * pi() * 42 }}"
                            stroke-dashoffset="{{ 2 * pi() * 42 * (1 - $stats['retention_rate'] / 100) }}"
                        />
                    </svg>
                </div>
            </div>
        </x-card>
    </div>

    <!-- MONTHLY TURNOVER CHART -->
    <x-card title="Monthly Turnover for {{ $year }}" shadow class="mb-6">
        <div class="h-64 flex items-end justify-between gap-2">
            @foreach($monthly['months'] as $index => $month)
                @php
                    $maxValue = max(max($monthly['move_ins']), max($monthly['move_outs'])) ?: 1;
                    $moveInHeight = ($monthly['move_ins'][$index] / $maxValue) * 100;
                    $moveOutHeight = ($monthly['move_outs'][$index] / $maxValue) * 100;
                @endphp
                <div class="flex-1 flex flex-col items-center gap-1">
                    <div class="w-full flex gap-1">
                        <div class="flex-1 bg-success rounded-t" style="height: {{ $moveInHeight }}%"></div>
                        <div class="flex-1 bg-warning rounded-t" style="height: {{ $moveOutHeight }}%"></div>
                    </div>
                    <div class="text-xs text-center transform -rotate-45 origin-top-left whitespace-nowrap">
                        {{ $month }}
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4 flex justify-center gap-6">
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-success rounded"></div>
                <span class="text-sm">Move-ins</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-warning rounded"></div>
                <span class="text-sm">Move-outs</span>
            </div>
        </div>
    </x-card>

    <!-- LEASES EXPIRING SOON -->
    <x-card title="Leases Expiring Soon (Next 90 Days)" shadow class="mb-6">
        @if(count($expiring) > 0)
            <x-table 
                :headers="[
                    ['key' => 'name', 'label' => 'Tenant'],
                    ['key' => 'apartment_name', 'label' => 'Apartment'],
                    ['key' => 'location_name', 'label' => 'Location'],
                    ['key' => 'lease_end_date', 'label' => 'Expiry Date'],
                    ['key' => 'days_until_expiry', 'label' => 'Days Remaining'],
                ]"
                :rows="$expiring"
            >
                @scope('cell_lease_end_date', $row)
                    <div class="font-semibold">{{ \Carbon\Carbon::parse($row['lease_end_date'])->format('M d, Y') }}</div>
                @endscope

                @scope('cell_days_until_expiry', $row)
                    @php
                        $days = $row['days_until_expiry'];
                        $badgeClass = $days <= 30 ? 'badge-error' : ($days <= 60 ? 'badge-warning' : 'badge-info');
                    @endphp
                    <div class="badge {{ $badgeClass }}">{{ $days }} days</div>
                @endscope
            </x-table>
        @else
            <div class="text-center text-base-content/50 py-8">No leases expiring in the next 90 days</div>
        @endif
    </x-card>

    <!-- TURNOVER BY LOCATION -->
    <x-card title="Turnover by Location" shadow>
        @if(count($byLocation) > 0)
            <x-table 
                :headers="[
                    ['key' => 'name', 'label' => 'Location'],
                    ['key' => 'total', 'label' => 'Total'],
                    ['key' => 'active', 'label' => 'Active'],
                    ['key' => 'new_this_year', 'label' => 'New {{ $year }}'],
                    ['key' => 'moved_out_this_year', 'label' => 'Moved Out {{ $year }}'],
                    ['key' => 'retention_rate', 'label' => 'Retention Rate'],
                ]"
                :rows="$byLocation"
            >
                @scope('cell_active', $row)
                    <div class="badge badge-success">{{ $row['active'] }}</div>
                @endscope

                @scope('cell_new_this_year', $row)
                    <div class="badge badge-info">{{ $row['new_this_year'] }}</div>
                @endscope

                @scope('cell_moved_out_this_year', $row)
                    <div class="badge badge-warning">{{ $row['moved_out_this_year'] }}</div>
                @endscope

                @scope('cell_retention_rate', $row)
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-base-300 rounded-full h-2 w-24">
                            <div class="bg-success h-2 rounded-full" style="width: {{ $row['retention_rate'] }}%"></div>
                        </div>
                        <span class="text-sm font-semibold">{{ number_format($row['retention_rate'], 1) }}%</span>
                    </div>
                @endscope
            </x-table>
        @else
            <div class="text-center text-base-content/50 py-8">No location data available</div>
        @endif
    </x-card>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <div class="grid gap-5">
            <x-select 
                label="Year" 
                wire:model.live="year" 
                :options="collect($years)->map(fn($y) => ['id' => $y, 'name' => $y])->toArray()" 
                icon="o-calendar" 
            />
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
</div>
