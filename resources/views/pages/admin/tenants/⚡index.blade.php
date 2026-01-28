<?php

use App\Models\Tenant;
use App\Models\RentPayment;
use App\Services\TenantMetricsService;
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

    protected TenantMetricsService $metricsService;

    public function boot(TenantMetricsService $metricsService): void
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

    // Get tenants with metrics
    public function getTenantsWithMetrics(): LengthAwarePaginator
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        return Tenant::with(['apartment', 'owner'])
            ->when($this->search, fn(Builder $q) => $q->where(function($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhereHas('apartment', function($q) {
                        $q->where('name', 'like', "%{$this->search}%");
                    });
            }))
            ->when($this->activity_status === 'active', fn(Builder $q) => $q->where(function($query) use ($thirtyDaysAgo) {
                $query->where('updated_at', '>=', $thirtyDaysAgo)
                    ->orWhereHas('rentPayments', function($q) use ($thirtyDaysAgo) {
                        $q->where('updated_at', '>=', $thirtyDaysAgo);
                    })
                    ->orWhereHas('tasks', function($q) use ($thirtyDaysAgo) {
                        $q->where('updated_at', '>=', $thirtyDaysAgo);
                    });
            }))
            ->when($this->activity_status === 'inactive', fn(Builder $q) => $q->where(function($query) use ($thirtyDaysAgo) {
                $query->where('updated_at', '<', $thirtyDaysAgo)
                    ->whereDoesntHave('rentPayments', function($q) use ($thirtyDaysAgo) {
                        $q->where('updated_at', '>=', $thirtyDaysAgo);
                    })
                    ->whereDoesntHave('tasks', function($q) use ($thirtyDaysAgo) {
                        $q->where('updated_at', '>=', $thirtyDaysAgo);
                    });
            }))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(10)
            ->through(function($tenant) {
                // Use service to calculate metrics
                $metrics = $this->metricsService->getTenantMetrics($tenant);
                
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'email' => $tenant->email,
                    'apartment_name' => $tenant->apartment?->name ?? 'N/A',
                    'owner_name' => $tenant->owner?->name ?? 'N/A',
                    'monthly_rent' => $tenant->monthly_rent,
                    'status' => $tenant->status,
                    'lease_end_date' => $tenant->lease_end_date,
                    'total_payments' => $metrics['total_payments'],
                    'monthly_payments' => $metrics['monthly_payments'],
                    'payment_compliance_rate' => $metrics['payment_compliance_rate'],
                    'total_tasks' => $metrics['total_tasks'],
                    'completed_tasks' => $metrics['completed_tasks'],
                    'task_completion_rate' => $metrics['task_completion_rate'],
                    'lease_status' => $metrics['lease_status'],
                    'lease_days_remaining' => $metrics['lease_days_remaining'],
                    'last_activity' => $metrics['last_activity'],
                ];
            });
    }

    // Summary statistics
    public function getTotalTenants(): int
    {
        return Tenant::count();
    }

    public function getActiveTenants(): int
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        
        return Tenant::where(function($query) use ($thirtyDaysAgo) {
                $query->where('updated_at', '>=', $thirtyDaysAgo)
                    ->orWhereHas('rentPayments', function($q) use ($thirtyDaysAgo) {
                        $q->where('updated_at', '>=', $thirtyDaysAgo);
                    })
                    ->orWhereHas('tasks', function($q) use ($thirtyDaysAgo) {
                        $q->where('updated_at', '>=', $thirtyDaysAgo);
                    });
            })
            ->count();
    }

    public function getAverageMetrics(): array
    {
        $tenants = Tenant::withCount([
            'rentPayments',
            'rentPayments as paid_payments_count' => function($query) {
                $query->where('status', 'paid');
            },
            'tasks',
            'tasks as completed_tasks_count' => function($query) {
                $query->where('status', 'done');
            },
        ])
        ->get();

        $totalTenants = $tenants->count();
        if ($totalTenants === 0) {
            return [
                'avg_payments' => 0,
                'avg_tasks' => 0,
                'avg_compliance_rate' => 0,
            ];
        }

        $totalPayments = $tenants->sum('rent_payments_count');
        $totalPaidPayments = $tenants->sum('paid_payments_count');
        $totalTasks = $tenants->sum('tasks_count');
        $totalCompletedTasks = $tenants->sum('completed_tasks_count');

        $avgComplianceRate = $totalPayments > 0 
            ? round(($totalPaidPayments / $totalPayments) * 100, 1) 
            : 0;

        return [
            'avg_payments' => round($totalPayments / $totalTenants, 1),
            'avg_tasks' => round($totalTasks / $totalTenants, 1),
            'avg_compliance_rate' => $avgComplianceRate,
        ];
    }

    // Table headers
    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => 'Name', 'class' => 'whitespace-nowrap'],
            ['key' => 'apartment_name', 'label' => 'Apartment', 'class' => 'whitespace-nowrap', 'sortable' => false],
            ['key' => 'monthly_rent', 'label' => 'Monthly Rent', 'class' => 'w-32 text-right', 'sortable' => false],
            ['key' => 'payment_compliance_rate', 'label' => 'Payment Compliance', 'class' => 'w-40', 'sortable' => false],
            ['key' => 'task_completion_rate', 'label' => 'Task Completion', 'class' => 'w-40', 'sortable' => false],
            ['key' => 'lease_status', 'label' => 'Lease Status', 'class' => 'w-32', 'sortable' => false],
            ['key' => 'last_activity', 'label' => 'Last Activity', 'class' => 'w-40', 'sortable' => false],
        ];
    }

    public function with(): array
    {
        $tenants = $this->getTenantsWithMetrics();
        $avgMetrics = $this->getAverageMetrics();

        return [
            'tenants' => $tenants,
            'headers' => $this->headers(),
            'totalTenants' => $this->getTotalTenants(),
            'activeTenants' => $this->getActiveTenants(),
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
    <x-header title="Tenants Monitoring" separator progress-indicator>
        <x-slot:subtitle>
            Track tenant progress, performance metrics, and activity
        </x-slot:subtitle>
        <x-slot:middle class="justify-end!">
            <x-input placeholder="Search by name, email, or apartment..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel" />
        </x-slot:actions>
    </x-header>

    <!-- SUMMARY CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Tenants -->
        <x-card class="bg-base-100 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Total Tenants</div>
                    <div class="text-3xl font-bold text-primary">{{ $totalTenants }}</div>
                </div>
                <x-icon name="o-users" class="w-12 h-12 text-primary/20" />
            </div>
        </x-card>

        <!-- Active Tenants -->
        <x-card class="bg-base-100 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Active Tenants</div>
                    <div class="text-3xl font-bold text-success">{{ $activeTenants }}</div>
                    <div class="text-xs text-base-content/60 mt-1">Last 30 days</div>
                </div>
                <x-icon name="o-check-circle" class="w-12 h-12 text-success/20" />
            </div>
        </x-card>

        <!-- Average Payments -->
        <x-card class="bg-base-100 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Avg Payments</div>
                    <div class="text-3xl font-bold text-info">{{ $avgMetrics['avg_payments'] }}</div>
                    <div class="text-xs text-base-content/60 mt-1">Per tenant</div>
                </div>
                <x-icon name="o-banknotes" class="w-12 h-12 text-info/20" />
            </div>
        </x-card>

        <!-- Average Compliance Rate -->
        <x-card class="bg-base-100 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Avg Compliance</div>
                    <div class="text-3xl font-bold text-warning">{{ $avgMetrics['avg_compliance_rate'] }}%</div>
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
            :rows="$tenants" 
            :sort-by="$sortBy" 
            with-pagination
            class="bg-base-100"
            link="/admin/tenants/{id}"
        >
            @scope('cell_name', $tenant)
                <div class="flex flex-col gap-1">
                    <span class="text-sm font-medium whitespace-nowrap">
                        {{ $tenant['name'] }}
                    </span>
                    <span class="text-xs text-base-content/60">
                        ({{ $tenant['email'] }})
                    </span>
                </div>
            @endscope
            @scope('cell_apartment_name', $tenant)
                <div class="flex flex-col gap-1 text-sm font-medium whitespace-nowrap">
                    <span class="text-sm font-medium whitespace-nowrap">
                        {{ $tenant['apartment_name'] }}
                    </span>
                    <span class="text-xs text-base-content/60">
                        ({{ $tenant['owner_name'] }})
                </div>
            @endscope
            @scope('cell_monthly_rent', $tenant)
                <div class="font-semibold text-right">
                    ₱{{ number_format($tenant['monthly_rent'], 2) }}
                </div>
            @endscope

            @scope('cell_payment_compliance_rate', $tenant)
                <div class="flex items-center gap-2">
                    <div class="flex-1">
                        <div class="w-full bg-base-200 rounded-full h-2">
                            <div 
                                class="h-2 rounded-full transition-all {{ $tenant['payment_compliance_rate'] >= 90 ? 'bg-success' : ($tenant['payment_compliance_rate'] >= 70 ? 'bg-warning' : 'bg-error') }}"
                                style="width: {{ min($tenant['payment_compliance_rate'], 100) }}%"
                            ></div>
                        </div>
                    </div>
                    <span class="text-sm font-medium min-w-12 text-right">
                        {{ $tenant['payment_compliance_rate'] }}%
                    </span>
                </div>
            @endscope

            @scope('cell_task_completion_rate', $tenant)
                <div class="flex items-center gap-2">
                    <div class="flex-1">
                        <div class="w-full bg-base-200 rounded-full h-2">
                            <div 
                                class="h-2 rounded-full transition-all {{ $tenant['task_completion_rate'] >= 80 ? 'bg-success' : ($tenant['task_completion_rate'] >= 50 ? 'bg-warning' : 'bg-error') }}"
                                style="width: {{ min($tenant['task_completion_rate'], 100) }}%"
                            ></div>
                        </div>
                    </div>
                    <span class="text-sm font-medium min-w-12 text-right">
                        {{ $tenant['task_completion_rate'] }}%
                    </span>
                </div>
            @endscope

            @scope('cell_lease_status', $tenant)
                @php
                    $statusConfig = match($tenant['lease_status']) {
                        'expired' => ['badge' => 'badge-error', 'text' => 'Expired'],
                        'expiring_soon' => ['badge' => 'badge-warning', 'text' => 'Expiring Soon'],
                        default => ['badge' => 'badge-success', 'text' => 'Active'],
                    };
                @endphp
                <div class="flex flex-col gap-1">
                    <span class="badge {{ $statusConfig['badge'] }} badge-sm text-white">
                        {{ $statusConfig['text'] }}
                    </span>
                    @if($tenant['lease_days_remaining'] !== null)
                        <div class="text-xs text-base-content/60">
                            {{ round(abs($tenant['lease_days_remaining'])) }} days
                        </div>
                    @endif
                </div>
            @endscope

            @scope('cell_last_activity', $tenant)
                @if($tenant['last_activity'])
                    <div class="text-sm text-base-content/70">
                        {{ \Carbon\Carbon::parse($tenant['last_activity'])->diffForHumans() }}
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
            <x-input placeholder="Search by name, email, or apartment..." wire:model.live.debounce="search" icon="o-magnifying-glass" /> 
            <div class="form-control">
                <label class="label">
                    <span class="label-text">Activity Status</span>
                </label>
                <select wire:model.live="activity_status" class="select select-bordered w-full">
                    <option value="">All Tenants</option>
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
