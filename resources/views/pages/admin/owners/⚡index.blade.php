<?php

use App\Models\User;
use App\Models\Apartment;
use App\Models\Tenant;
use App\Models\RentPayment;
use App\Services\OwnerMetricsService;
use Livewire\Component;
use Mary\Traits\Toast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;
use App\Traits\AuthorizesRole;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use Toast;
    use WithPagination;
    use AuthorizesRole;

    public string $search = '';
    public string $activity_status = '';
    public bool $drawer = false;

    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];

    protected OwnerMetricsService $metricsService;

    public function boot(OwnerMetricsService $metricsService): void
    {
        $this->metricsService = $metricsService;
    }

    // Check admin access on mount
    public function mount(): void
    {
        $this->authorizeRole('admin');
    }

    // Clear filters
    public function clear(): void
    {
        $this->reset(['search', 'activity_status']);
        $this->resetPage(); 
        $this->success('Filters cleared.', position: 'toast-bottom');
    }

    // Get owners with metrics
    public function getOwnersWithMetrics(): LengthAwarePaginator
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        return User::where('role', 'owner')
            ->whereHas('apartments')
            ->withCount([
                'apartments',
                'apartments as occupied_apartments_count' => function($query) {
                    $query->where('status', 'occupied');
                },
                'apartments as available_apartments_count' => function($query) {
                    $query->where('status', 'available');
                },
                'tenants as active_tenants_count' => function($query) {
                    $query->where('status', 'active');
                },
            ])
            ->when($this->search, fn(Builder $q) => $q->where(function($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->when($this->activity_status === 'active', fn(Builder $q) => $q->whereHas('apartments', function($query) use ($thirtyDaysAgo) {
                $query->where('updated_at', '>=', $thirtyDaysAgo);
            })->orWhereHas('tenants', function($query) use ($thirtyDaysAgo) {
                $query->where('updated_at', '>=', $thirtyDaysAgo);
            }))
            ->when($this->activity_status === 'inactive', fn(Builder $q) => $q->whereDoesntHave('apartments', function($query) use ($thirtyDaysAgo) {
                $query->where('updated_at', '>=', $thirtyDaysAgo);
            })->whereDoesntHave('tenants', function($query) use ($thirtyDaysAgo) {
                $query->where('updated_at', '>=', $thirtyDaysAgo);
            }))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(10)
            ->through(function($owner) {
                // Use service to calculate metrics
                $metrics = $this->metricsService->getOwnerMetrics($owner);
                
                return [
                    'id' => $owner->id,
                    'name' => $owner->name,
                    'email' => $owner->email,
                    'apartments_count' => $metrics['total_apartments'],
                    'occupied_apartments' => $metrics['occupied_apartments'],
                    'available_apartments' => $metrics['available_apartments'],
                    'tenants_count' => $metrics['active_tenants'],
                    'monthly_revenue' => $metrics['monthly_revenue'],
                    'occupancy_rate' => $metrics['occupancy_rate'],
                    'collection_rate' => $metrics['collection_rate'],
                    'last_activity' => $metrics['last_activity'],
                ];
            });
    }

    // Summary statistics
    public function getTotalOwners(): int
    {
        return User::where('role', 'owner')->count();
    }

    public function getActiveOwners(): int
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        
        return User::where('role', 'owner')
            ->where(function($query) use ($thirtyDaysAgo) {
                $query->whereHas('apartments', function($q) use ($thirtyDaysAgo) {
                    $q->where('updated_at', '>=', $thirtyDaysAgo);
                })->orWhereHas('tenants', function($q) use ($thirtyDaysAgo) {
                    $q->where('updated_at', '>=', $thirtyDaysAgo);
                });
            })
            ->count();
    }

    public function getAverageMetrics(): array
    {
        $owners = User::where('role', 'owner')
            ->withCount([
                'apartments',
                'apartments as occupied_apartments_count' => function($query) {
                    $query->where('status', 'occupied');
                },
                'tenants as active_tenants_count' => function($query) {
                    $query->where('status', 'active');
                },
            ])
            ->get();

        $totalOwners = $owners->count();
        if ($totalOwners === 0) {
            return [
                'avg_apartments' => 0,
                'avg_tenants' => 0,
                'avg_occupancy_rate' => 0,
            ];
        }

        $totalApartments = $owners->sum('apartments_count');
        $totalOccupied = $owners->sum('occupied_apartments_count');
        $totalTenants = $owners->sum('active_tenants_count');

        $avgOccupancyRate = $totalApartments > 0 
            ? round(($totalOccupied / $totalApartments) * 100, 1) 
            : 0;

        return [
            'avg_apartments' => round($totalApartments / $totalOwners, 1),
            'avg_tenants' => round($totalTenants / $totalOwners, 1),
            'avg_occupancy_rate' => $avgOccupancyRate,
        ];
    }

    // Table headers
    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => 'Name', 'class' => ''],
            ['key' => 'apartments_count', 'label' => 'Apartments', 'class' => ''],
            ['key' => 'tenants_count', 'label' => 'Tenants', 'class' => 'text-center', 'sortable' => false],
            ['key' => 'monthly_revenue', 'label' => 'Revenue (MTD)', 'class' => 'text-right', 'sortable' => false],
            ['key' => 'occupancy_rate', 'label' => 'Occupancy Rate', 'class' => '', 'sortable' => false],
            ['key' => 'collection_rate', 'label' => 'Collection Rate', 'class' => '', 'sortable' => false],
            ['key' => 'last_activity', 'label' => 'Last Activity', 'class' => 'text-right', 'sortable' => false],
        ];
    }

    public function with(): array
    {
        $owners = $this->getOwnersWithMetrics();
        $avgMetrics = $this->getAverageMetrics();

        return [
            'owners' => $owners,
            'headers' => $this->headers(),
            'totalOwners' => $this->getTotalOwners(),
            'activeOwners' => $this->getActiveOwners(),
            'avgMetrics' => $avgMetrics,
        ];
    }

    // Reset pagination when any component property changes
    public function updated($property): void
    {
        if (! is_array($property) && $property != "") {
            $this->resetPage();
        }
    }

}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Owner Monitoring" separator progress-indicator>
        <x-slot:subtitle>
            Track owner progress, performance metrics, and activity
        </x-slot:subtitle>
        <x-slot:middle class="justify-end!">
            <x-input placeholder="Search by name or email..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel" />
        </x-slot:actions>
    </x-header>

    <!-- SUMMARY CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Owners -->
        <x-card class="bg-base-100 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Total Owners</div>
                    <div class="text-3xl font-bold text-primary">{{ $totalOwners }}</div>
                </div>
                <x-icon name="o-user-group" class="w-12 h-12 text-primary/20" />
            </div>
        </x-card>

        <!-- Active Owners -->
        <x-card class="bg-base-100 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Active Owners</div>
                    <div class="text-3xl font-bold text-success">{{ $activeOwners }}</div>
                    <div class="text-xs text-base-content/60 mt-1">Last 30 days</div>
                </div>
                <x-icon name="o-check-circle" class="w-12 h-12 text-success/20" />
            </div>
        </x-card>

        <!-- Average Apartments -->
        <x-card class="bg-base-100 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Avg Apartments</div>
                    <div class="text-3xl font-bold text-info">{{ $avgMetrics['avg_apartments'] }}</div>
                    <div class="text-xs text-base-content/60 mt-1">Per owner</div>
                </div>
                <x-icon name="o-building-office" class="w-12 h-12 text-info/20" />
            </div>
        </x-card>

        <!-- Average Occupancy Rate -->
        <x-card class="bg-base-100 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Avg Occupancy</div>
                    <div class="text-3xl font-bold text-warning">{{ $avgMetrics['avg_occupancy_rate'] }}%</div>
                    <div class="text-xs text-base-content/60 mt-1">System average</div>
                </div>
                <x-icon name="o-chart-bar" class="w-12 h-12 text-warning/20" />
            </div>
        </x-card>
    </div>

    <!-- TABLE  -->
    <x-card shadow>
        <x-table 
            :headers="$headers" 
            :rows="$owners" 
            :sort-by="$sortBy" 
            with-pagination
            class="bg-base-100"
            link="/admin/owners/{id}"
        >
            @scope('cell_name', $owner)
            <div class="flex flex-col gap-1">
                <span class="text-sm font-medium whitespace-nowrap">
                    {{ $owner['name'] }}
                </span>
                <span class="text-xs text-base-content/60">
                    ({{ $owner['email'] }})
                </span>
            </div>
            @endscope

            @scope('cell_apartments_count', $owner)
                <div class="flex items-center gap-2 justify-center whitespace-nowrap">
                    <div class="badge badge-ghost">
                        {{ $owner['apartments_count'] }}
                    </div>
                    @if($owner['apartments_count'] > 0)
                        <div class="text-xs text-base-content/60">
                            ({{ $owner['occupied_apartments'] }}/{{ $owner['apartments_count'] }} occupied)
                        </div>
                    @endif
                </div>
            @endscope

            @scope('cell_tenants_count', $owner)
                <div class="badge badge-ghost text-center">
                    {{ $owner['tenants_count'] }}
                </div>
            @endscope

            @scope('cell_monthly_revenue', $owner)
                <div class="font-semibold text-right">
                    ₱{{ number_format($owner['monthly_revenue'], 2) }}
                </div>
            @endscope

            @scope('cell_occupancy_rate', $owner)
                <div class="flex items-center gap-2">
                    <div class="flex-1">
                        <div class="w-full bg-base-200 rounded-full h-2">
                            <div 
                                class="h-2 rounded-full transition-all {{ $owner['occupancy_rate'] >= 80 ? 'bg-success' : ($owner['occupancy_rate'] >= 50 ? 'bg-warning' : 'bg-error') }}"
                                style="width: {{ min($owner['occupancy_rate'], 100) }}%"
                            ></div>
                        </div>
                    </div>
                    <span class="text-sm font-medium min-w-12 text-right">
                        {{ $owner['occupancy_rate'] }}%
                    </span>
                </div>
            @endscope

            @scope('cell_collection_rate', $owner)
                <div class="flex items-center gap-2">
                    <div class="flex-1">
                        <div class="w-full bg-base-200 rounded-full h-2">
                            <div 
                                class="h-2 rounded-full transition-all {{ $owner['collection_rate'] >= 90 ? 'bg-success' : ($owner['collection_rate'] >= 70 ? 'bg-warning' : 'bg-error') }}"
                                style="width: {{ min($owner['collection_rate'], 100) }}%"
                            ></div>
                        </div>
                    </div>
                    <span class="text-sm font-medium min-w-12 text-right">
                        {{ $owner['collection_rate'] }}%
                    </span>
                </div>
            @endscope

            @scope('cell_last_activity', $owner)
                @if($owner['last_activity'])
                    <div class="text-sm text-base-content/70">
                        {{ \Carbon\Carbon::parse($owner['last_activity'])->diffForHumans() }}
                    </div>
                @else
                    <div class="text-sm text-base-content/50">
                        No activity
                    </div>
                @endif
            @endscope
        </x-table>
    </x-card>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <div class="grid gap-5"> 
            <x-input placeholder="Search by name or email..." wire:model.live.debounce="search" icon="o-magnifying-glass" /> 
            <div class="form-control">
                <label class="label">
                    <span class="label-text">Activity Status</span>
                </label>
                <select wire:model.live="activity_status" class="select select-bordered w-full">
                    <option value="">All Owners</option>
                    <option value="active">Active (Last 30 days)</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>
</div>
