<?php

use App\Models\Apartment;
use App\Models\RentPayment;
use Livewire\Component;
use Mary\Traits\Toast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;
use App\Traits\AuthorizesRole;
use Carbon\Carbon;

new class extends Component {
    use Toast;
    use WithPagination;
    use AuthorizesRole;

    public string $search = '';
    public string $status_filter = '';
    public string $activity_status = '';
    public bool $drawer = false;

    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];

    // Check admin access on mount
    public function mount(): void
    {
        $this->authorizeRole('admin');
    }

    // Clear filters
    public function clear(): void
    {
        $this->reset(['search', 'status_filter', 'activity_status']);
        $this->resetPage();
        $this->success('Filters cleared.', position: 'toast-bottom');
    }

    // Get apartments with metrics
    public function getApartmentsWithMetrics(): LengthAwarePaginator
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        return Apartment::with(['owner', 'location'])
            ->withCount(['tenants'])
            ->when($this->search, fn(Builder $q) => $q->where(function($query) {
                $query->where('apartments.name', 'like', "%{$this->search}%")
                    ->orWhere('apartments.address', 'like', "%{$this->search}%")
                    ->orWhere('apartments.unit_number', 'like', "%{$this->search}%")
                    ->orWhereHas('owner', fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%"))
                    ->orWhereHas('location', fn($q) => $q->where('name', 'like', "%{$this->search}%"));
            }))
            ->when($this->status_filter, fn(Builder $q) => $q->where('apartments.status', $this->status_filter))
            ->when($this->activity_status === 'active', fn(Builder $q) => $q->where(function (Builder $q) use ($thirtyDaysAgo) {
                $q->where('apartments.updated_at', '>=', $thirtyDaysAgo)
                    ->orWhereHas('tenants', fn($q2) => $q2->where('updated_at', '>=', $thirtyDaysAgo))
                    ->orWhereHas('rentPayments', fn($q2) => $q2->where('updated_at', '>=', $thirtyDaysAgo));
            }))
            ->when($this->activity_status === 'inactive', fn(Builder $q) => $q->where('apartments.updated_at', '<', $thirtyDaysAgo)
                ->whereDoesntHave('tenants', fn($q2) => $q2->where('updated_at', '>=', $thirtyDaysAgo))
                ->whereDoesntHave('rentPayments', fn($q2) => $q2->where('updated_at', '>=', $thirtyDaysAgo)))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(10)
            ->through(function($apartment) {
                $currentTenant = $apartment->tenants()->where('status', 'active')->first();
                $revenueMtd = (float) RentPayment::where('apartment_id', $apartment->id)
                    ->where('status', 'paid')
                    ->whereBetween('payment_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                    ->sum('amount');
                $lastActivity = collect([
                    $apartment->updated_at,
                    $apartment->tenants()->max('updated_at'),
                    $apartment->rentPayments()->max('updated_at'),
                ])->filter()->map(fn($d) => $d ? Carbon::parse($d) : null)->filter()->max();

                return [
                    'id' => $apartment->id,
                    'name' => $apartment->name,
                    'unit_number' => $apartment->unit_number,
                    'address' => $apartment->address,
                    'location_name' => $apartment->location?->name ?? 'N/A',
                    'owner_name' => $apartment->owner?->name ?? 'N/A',
                    'owner_email' => $apartment->owner?->email ?? 'N/A',
                    'status' => $apartment->status,
                    'monthly_rent' => $apartment->monthly_rent,
                    'tenants_count' => $apartment->tenants_count,
                    'current_tenant_name' => $currentTenant?->name ?? null,
                    'revenue_mtd' => $revenueMtd,
                    'last_activity' => $lastActivity?->toIso8601String(),
                ];
            });
    }

    public function getTotalApartments(): int
    {
        return Apartment::count();
    }

    public function getOccupiedCount(): int
    {
        return Apartment::where('status', 'occupied')->count();
    }

    public function getAvailableCount(): int
    {
        return Apartment::where('status', 'available')->count();
    }

    public function getMaintenanceCount(): int
    {
        return Apartment::where('status', 'maintenance')->count();
    }

    public function getAverageRent(): float
    {
        $avg = Apartment::avg('monthly_rent');
        return round((float) $avg, 2);
    }

    public function getAverageMetrics(): array
    {
        $total = Apartment::count();
        if ($total === 0) {
            return ['avg_rent' => 0, 'occupancy_rate' => 0];
        }
        $occupied = Apartment::where('status', 'occupied')->count();
        return [
            'avg_rent' => $this->getAverageRent(),
            'occupancy_rate' => $total > 0 ? round(($occupied / $total) * 100, 1) : 0,
        ];
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => 'Apartment', 'class' => 'whitespace-nowrap'],
            ['key' => 'location_name', 'label' => 'Location', 'class' => 'whitespace-nowrap', 'sortable' => false],
            ['key' => 'owner_name', 'label' => 'Owner', 'class' => 'whitespace-nowrap', 'sortable' => false],
            ['key' => 'status', 'label' => 'Status', 'class' => 'w-28', 'sortable' => false],
            ['key' => 'monthly_rent', 'label' => 'Monthly Rent', 'class' => 'w-32 text-right', 'sortable' => false],
            ['key' => 'current_tenant_name', 'label' => 'Current Tenant', 'class' => 'whitespace-nowrap', 'sortable' => false],
            ['key' => 'revenue_mtd', 'label' => 'Revenue (MTD)', 'class' => 'w-32 text-right', 'sortable' => false],
            ['key' => 'last_activity', 'label' => 'Last Activity', 'class' => 'w-28 text-right', 'sortable' => false],
        ];
    }

    public function with(): array
    {
        $avgMetrics = $this->getAverageMetrics();
        return [
            'apartments' => $this->getApartmentsWithMetrics(),
            'headers' => $this->headers(),
            'totalApartments' => $this->getTotalApartments(),
            'occupiedCount' => $this->getOccupiedCount(),
            'availableCount' => $this->getAvailableCount(),
            'maintenanceCount' => $this->getMaintenanceCount(),
            'avgMetrics' => $avgMetrics,
        ];
    }

    public function updated($property): void
    {
        if (! is_array($property) && $property !== '') {
            $this->resetPage();
        }
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Apartment Monitoring" separator progress-indicator>
        <x-slot:subtitle>
            Track apartments across all owners, occupancy, and revenue
        </x-slot:subtitle>
        <x-slot:middle class="justify-end!">
            <x-input placeholder="Search by name, address, owner, or location..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel" />
        </x-slot:actions>
    </x-header>

    <!-- SUMMARY CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <x-card class="bg-base-100 border border-base-content/10 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Total Apartments</div>
                    <div class="text-3xl font-bold text-primary">{{ $totalApartments }}</div>
                </div>
                <x-icon name="o-building-office" class="w-12 h-12 text-primary/80" />
            </div>
        </x-card>
        <x-card class="bg-base-100 border border-base-content/10 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Occupied</div>
                    <div class="text-3xl font-bold text-success">{{ $occupiedCount }}</div>
                </div>
                <x-icon name="o-user" class="w-12 h-12 text-success/80" />
            </div>
        </x-card>
        <x-card class="bg-base-100 border border-base-content/10 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Available</div>
                    <div class="text-3xl font-bold text-info">{{ $availableCount }}</div>
                </div>
                <x-icon name="o-home" class="w-12 h-12 text-info/80" />
            </div>
        </x-card>
        <x-card class="bg-base-100 border border-base-content/10 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Maintenance</div>
                    <div class="text-3xl font-bold text-warning">{{ $maintenanceCount }}</div>
                </div>
                <x-icon name="o-wrench-screwdriver" class="w-12 h-12 text-warning/80" />
            </div>
        </x-card>
        <x-card class="bg-base-100 border border-base-content/10 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Avg Rent</div>
                    <div class="text-2xl font-bold text-base-content">₱{{ number_format($avgMetrics['avg_rent'], 0) }}</div>
                    <div class="text-xs text-base-content/60 mt-1">{{ $avgMetrics['occupancy_rate'] }}% occupancy</div>
                </div>
                <x-icon name="o-currency-dollar" class="w-12 h-12 text-base-content/40" />
            </div>
        </x-card>
    </div>

    <!-- TABLE -->
    <x-card class="border border-base-content/10" shadow>
        <x-table
            :headers="$headers"
            :rows="$apartments"
            :sort-by="$sortBy"
            with-pagination
            class="bg-base-100"
            link="/admin/apartments/{id}"
        >
            @scope('cell_name', $apt)
                <div class="flex flex-col gap-1">
                    <span class="text-sm font-medium whitespace-nowrap">{{ $apt['name'] }}</span>
                    @if($apt['unit_number'])
                        <span class="text-xs text-base-content/60">Unit {{ $apt['unit_number'] }}</span>
                    @endif
                    @if($apt['address'])
                        <span class="text-xs text-base-content/50 truncate max-w-[200px]" title="{{ $apt['address'] }}">{{ $apt['address'] }}</span>
                    @endif
                </div>
            @endscope

            @scope('cell_owner_name', $apt)
                <div class="flex flex-col gap-1">
                    <span class="text-sm font-medium whitespace-nowrap">{{ $apt['owner_name'] }}</span>
                    <span class="text-xs text-base-content/60">{{ $apt['owner_email'] }}</span>
                </div>
            @endscope

            @scope('cell_status', $apt)
                @php
                    $statusConfig = match($apt['status']) {
                        'occupied' => ['badge' => 'badge-success', 'label' => 'Occupied'],
                        'available' => ['badge' => 'badge-info', 'label' => 'Available'],
                        'maintenance' => ['badge' => 'badge-warning', 'label' => 'Maintenance'],
                        default => ['badge' => 'badge-ghost', 'label' => ucfirst($apt['status'] ?? 'N/A')],
                    };
                @endphp
                <span class="badge {{ $statusConfig['badge'] }} badge-sm">{{ $statusConfig['label'] }}</span>
            @endscope

            @scope('cell_monthly_rent', $apt)
                <div class="font-semibold text-right">₱{{ number_format($apt['monthly_rent'], 2) }}</div>
            @endscope

            @scope('cell_current_tenant_name', $apt)
                @if($apt['current_tenant_name'])
                    <span class="text-sm font-medium">{{ $apt['current_tenant_name'] }}</span>
                @else
                    <span class="text-base-content/50">—</span>
                @endif
            @endscope

            @scope('cell_revenue_mtd', $apt)
                <div class="font-semibold text-right">₱{{ number_format($apt['revenue_mtd'], 2) }}</div>
            @endscope

            @scope('cell_last_activity', $apt)
                @if($apt['last_activity'])
                    <div class="text-sm text-base-content/70">{{ \Carbon\Carbon::parse($apt['last_activity'])->diffForHumans() }}</div>
                @else
                    <div class="text-sm text-base-content/50">No activity</div>
                @endif
            @endscope
        </x-table>
    </x-card>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <div class="grid gap-5">
            <x-input placeholder="Search..." wire:model.live.debounce="search" icon="o-magnifying-glass" />
            <div class="form-control">
                <label class="label"><span class="label-text">Status</span></label>
                <select wire:model.live="status_filter" class="select select-bordered w-full">
                    <option value="">All</option>
                    <option value="available">Available</option>
                    <option value="occupied">Occupied</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text">Activity</span></label>
                <select wire:model.live="activity_status" class="select select-bordered w-full">
                    <option value="">All</option>
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
