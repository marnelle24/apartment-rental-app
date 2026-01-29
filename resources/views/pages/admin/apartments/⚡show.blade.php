<?php

use App\Models\Apartment;
use App\Models\RentPayment;
use App\Models\Task;
use Livewire\Component;
use Mary\Traits\Toast;
use App\Traits\AuthorizesRole;
use Carbon\Carbon;

new class extends Component {
    use Toast;
    use AuthorizesRole;

    public Apartment $apartment;

    public function mount(Apartment $apartment): void
    {
        $this->authorizeRole('admin');
        $this->apartment = $apartment->load(['owner', 'location']);
    }

    public function getApartmentProfile(): array
    {
        return [
            'id' => $this->apartment->id,
            'name' => $this->apartment->name,
            'unit_number' => $this->apartment->unit_number,
            'address' => $this->apartment->address,
            'location_name' => $this->apartment->location?->name ?? 'N/A',
            'owner_name' => $this->apartment->owner?->name ?? 'N/A',
            'owner_email' => $this->apartment->owner?->email ?? 'N/A',
            'status' => $this->apartment->status,
            'monthly_rent' => $this->apartment->monthly_rent,
            'bedrooms' => $this->apartment->bedrooms,
            'bathrooms' => $this->apartment->bathrooms,
            'square_meters' => $this->apartment->square_meters,
            'description' => $this->apartment->description,
        ];
    }

    public function getMetrics(): array
    {
        $payments = RentPayment::where('apartment_id', $this->apartment->id);
        $paidCount = (clone $payments)->where('status', 'paid')->count();
        $pendingCount = (clone $payments)->where('status', 'pending')->count();
        $overdueCount = (clone $payments)->where('status', 'overdue')->count();
        $totalPayments = $payments->count();
        $collectionRate = $totalPayments > 0 ? round(($paidCount / $totalPayments) * 100, 1) : 0;

        $monthlyRevenue = (float) RentPayment::where('apartment_id', $this->apartment->id)
            ->where('status', 'paid')
            ->whereBetween('payment_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->sum('amount');
        $yearlyRevenue = (float) RentPayment::where('apartment_id', $this->apartment->id)
            ->where('status', 'paid')
            ->whereBetween('payment_date', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()])
            ->sum('amount');
        $totalRevenue = (float) RentPayment::where('apartment_id', $this->apartment->id)->where('status', 'paid')->sum('amount');

        $pendingAmount = (float) RentPayment::where('apartment_id', $this->apartment->id)->where('status', 'pending')->sum('amount');
        $overdueAmount = (float) RentPayment::where('apartment_id', $this->apartment->id)->where('status', 'overdue')->sum('amount');

        $tasks = Task::where('apartment_id', $this->apartment->id);
        $tasksByStatus = (clone $tasks)->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status')->toArray();
        $overdueTasks = (clone $tasks)->where('status', '!=', 'done')->where('status', '!=', 'cancelled')
            ->whereNotNull('due_date')->where('due_date', '<', Carbon::now())->count();
        $totalTasks = Task::where('apartment_id', $this->apartment->id)->count();
        $completedTasks = $tasksByStatus['done'] ?? 0;
        $taskCompletionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;

        $tenantsCount = $this->apartment->tenants()->where('status', 'active')->count();
        $lastActivity = collect([
            $this->apartment->updated_at,
            $this->apartment->tenants()->max('updated_at'),
            RentPayment::where('apartment_id', $this->apartment->id)->max('updated_at'),
            Task::where('apartment_id', $this->apartment->id)->max('updated_at'),
        ])->filter()->map(fn($d) => $d ? Carbon::parse($d) : null)->filter()->max();

        return [
            'paid_payments' => $paidCount,
            'pending_payments' => $pendingCount,
            'overdue_payments' => $overdueCount,
            'total_payments' => $totalPayments,
            'collection_rate' => $collectionRate,
            'monthly_revenue' => $monthlyRevenue,
            'yearly_revenue' => $yearlyRevenue,
            'total_revenue' => $totalRevenue,
            'pending_amount' => $pendingAmount,
            'overdue_amount' => $overdueAmount,
            'tasks_by_status' => $tasksByStatus,
            'overdue_tasks' => $overdueTasks,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'task_completion_rate' => $taskCompletionRate,
            'active_tenants' => $tenantsCount,
            'last_activity' => $lastActivity,
        ];
    }

    public function getRevenueTrend(): array
    {
        $labels = [];
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->format('M Y');
            $data[] = (float) RentPayment::where('apartment_id', $this->apartment->id)
                ->where('status', 'paid')
                ->whereBetween('payment_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->sum('amount');
        }
        return ['labels' => $labels, 'data' => $data];
    }

    public function getPaymentStatusData(): array
    {
        $m = $this->getMetrics();
        return [
            'labels' => ['Paid', 'Pending', 'Overdue'],
            'data' => [$m['paid_payments'], $m['pending_payments'], $m['overdue_payments']],
            'colors' => ['rgb(34, 197, 94)', 'rgb(251, 191, 36)', 'rgb(239, 68, 68)'],
        ];
    }

    public function getTaskStatusData(): array
    {
        $m = $this->getMetrics();
        $tb = $m['tasks_by_status'];
        return [
            'labels' => ['To Do', 'In Progress', 'Done', 'Overdue'],
            'data' => [$tb['todo'] ?? 0, $tb['in_progress'] ?? 0, $tb['done'] ?? 0, $m['overdue_tasks']],
            'colors' => ['rgb(59, 130, 246)', 'rgb(251, 191, 36)', 'rgb(34, 197, 94)', 'rgb(239, 68, 68)'],
        ];
    }

    public function getCircularProgress(): array
    {
        $m = $this->getMetrics();
        $r = 28;
        $c = 2 * pi() * $r;
        return [
            'collection' => ['circumference' => $c, 'offset' => $c * (1 - min($m['collection_rate'], 100) / 100)],
            'task_completion' => ['circumference' => $c, 'offset' => $c * (1 - min($m['task_completion_rate'], 100) / 100)],
        ];
    }

    public function getRecentActivity(): array
    {
        $activities = collect();

        RentPayment::where('apartment_id', $this->apartment->id)
            ->with('tenant')
            ->latest('updated_at')
            ->limit(15)
            ->get()
            ->each(function ($p) use (&$activities) {
                $cfg = match ($p->status) {
                    'paid' => ['icon' => 'o-banknotes', 'icon_color' => 'text-success', 'badge' => 'badge-success', 'title' => 'Payment Received'],
                    'pending' => ['icon' => 'o-clock', 'icon_color' => 'text-warning', 'badge' => 'badge-warning', 'title' => 'Payment Pending'],
                    'overdue' => ['icon' => 'o-exclamation-triangle', 'icon_color' => 'text-error', 'badge' => 'badge-error', 'title' => 'Payment Overdue'],
                    default => ['icon' => 'o-banknotes', 'icon_color' => 'text-base-content', 'badge' => 'badge-ghost', 'title' => 'Payment'],
                };
                $activities->push([
                    'type' => 'payment_' . $p->status,
                    'icon' => $cfg['icon'],
                    'icon_color' => $cfg['icon_color'],
                    'badge_color' => $cfg['badge'],
                    'title' => $cfg['title'],
                    'description' => '₱' . number_format($p->amount, 2) . ($p->tenant ? ' from ' . $p->tenant->name : ''),
                    'subtitle' => $p->payment_date ? $p->payment_date->format('M d, Y') : 'N/A',
                    'date' => $p->payment_date ?? $p->updated_at,
                    'timestamp' => $p->updated_at,
                ]);
            });

        Task::where('apartment_id', $this->apartment->id)
            ->with('tenant')
            ->latest('updated_at')
            ->limit(15)
            ->get()
            ->each(function ($t) use (&$activities) {
                $activities->push([
                    'type' => 'task',
                    'icon' => 'o-clipboard-document',
                    'icon_color' => 'text-info',
                    'badge_color' => 'badge-info',
                    'title' => 'Task: ' . ucfirst(str_replace('_', ' ', $t->status)),
                    'description' => $t->title,
                    'subtitle' => 'Priority: ' . ucfirst($t->priority),
                    'date' => $t->updated_at,
                    'timestamp' => $t->updated_at,
                ]);
            });

        return $activities->sortByDesc(fn ($a) => $a['timestamp']->timestamp)->take(20)->values()->all();
    }

    public function getTenants(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->apartment->tenants()->with('apartment')->latest()->paginate(5);
    }

    public function getRecentPayments(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return RentPayment::where('apartment_id', $this->apartment->id)
            ->with(['tenant', 'apartment'])
            ->latest('payment_date')
            ->paginate(5);
    }

    public function getActiveTasks(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Task::where('apartment_id', $this->apartment->id)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->with(['apartment', 'tenant'])
            ->latest()
            ->paginate(5);
    }

    public function with(): array
    {
        $metrics = $this->getMetrics();
        return [
            'apartment' => $this->getApartmentProfile(),
            'metrics' => $metrics,
            'revenueTrend' => $this->getRevenueTrend(),
            'paymentStatusData' => $this->getPaymentStatusData(),
            'taskStatusData' => $this->getTaskStatusData(),
            'circularProgress' => $this->getCircularProgress(),
            'recentActivity' => $this->getRecentActivity(),
            'tenants' => $this->getTenants(),
            'recentPayments' => $this->getRecentPayments(),
            'activeTasks' => $this->getActiveTasks(),
        ];
    }
}; ?>

<div>
    <x-header title="Apartment Details" separator progress-indicator>
        <x-slot:subtitle>
            {{ $apartment['name'] }}@if($apartment['unit_number']) · Unit {{ $apartment['unit_number'] }}@endif
        </x-slot:subtitle>
        <x-slot:actions>
            <x-button label="Back to Apartments" link="/admin/apartments" icon="o-arrow-left" class="btn-ghost" />
        </x-slot:actions>
    </x-header>

    <!-- PROFILE -->
    <x-card class="bg-base-100 mb-6 border border-base-content/10" shadow>
        <x-header title="Apartment Profile" separator />
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 p-4">
            <div>
                <div class="text-sm text-base-content/70 mb-1">Name</div>
                <div class="font-semibold text-lg">{{ $apartment['name'] }}</div>
                @if($apartment['unit_number'])
                    <div class="text-xs text-base-content/60">Unit {{ $apartment['unit_number'] }}</div>
                @endif
            </div>
            <div>
                <div class="text-sm text-base-content/70 mb-1">Location</div>
                <div class="font-semibold">{{ $apartment['location_name'] }}</div>
            </div>
            <div>
                <div class="text-sm text-base-content/70 mb-1">Owner</div>
                <div class="font-semibold">{{ $apartment['owner_name'] }}</div>
                <div class="text-xs text-base-content/60">{{ $apartment['owner_email'] }}</div>
            </div>
            <div>
                <div class="text-sm text-base-content/70 mb-1">Status</div>
                @php
                    $sc = match($apartment['status']) {
                        'occupied' => 'badge-success',
                        'available' => 'badge-info',
                        'maintenance' => 'badge-warning',
                        default => 'badge-ghost',
                    };
                @endphp
                <span class="badge {{ $sc }} badge-sm">{{ ucfirst($apartment['status']) }}</span>
            </div>
            <div>
                <div class="text-sm text-base-content/70 mb-1">Monthly Rent</div>
                <div class="font-semibold">₱{{ number_format($apartment['monthly_rent'], 2) }}</div>
            </div>
            @if($apartment['address'])
                <div class="md:col-span-2">
                    <div class="text-sm text-base-content/70 mb-1">Address</div>
                    <div class="font-semibold">{{ $apartment['address'] }}</div>
                </div>
            @endif
            @if($apartment['bedrooms'] !== null || $apartment['bathrooms'] !== null || $apartment['square_meters'] !== null)
                <div class="flex gap-4">
                    @if($apartment['bedrooms'] !== null)
                        <div><span class="text-base-content/70">Bedrooms:</span> <span class="font-semibold">{{ $apartment['bedrooms'] }}</span></div>
                    @endif
                    @if($apartment['bathrooms'] !== null)
                        <div><span class="text-base-content/70">Bathrooms:</span> <span class="font-semibold">{{ $apartment['bathrooms'] }}</span></div>
                    @endif
                    @if($apartment['square_meters'] !== null)
                        <div><span class="text-base-content/70">Size:</span> <span class="font-semibold">{{ $apartment['square_meters'] . 'm²' }}</span></div>
                    @endif
                </div>
            @endif
        </div>
    </x-card>

    <!-- METRICS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-card class="bg-base-100 border border-base-content/10" shadow>
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Monthly Revenue (MTD)</div>
                    <div class="text-2xl font-bold text-primary">₱{{ number_format($metrics['monthly_revenue'], 2) }}</div>
                </div>
                <x-icon name="o-currency-dollar" class="w-12 h-12 text-primary/80" />
            </div>
        </x-card>
        <x-card class="bg-base-100 border border-base-content/10" shadow>
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Active Tenants</div>
                    <div class="text-2xl font-bold text-success">{{ $metrics['active_tenants'] }}</div>
                </div>
                <x-icon name="o-users" class="w-12 h-12 text-success/80" />
            </div>
        </x-card>
        <x-card class="bg-base-100 border border-base-content/10" shadow>
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="text-sm text-base-content/70 mb-1">Collection Rate</div>
                    <div class="text-2xl font-bold text-info mb-1">{{ $metrics['collection_rate'] }}%</div>
                    <div class="w-full bg-base-200 rounded-full h-2">
                        <div class="h-2 rounded-full {{ $metrics['collection_rate'] >= 90 ? 'bg-success' : ($metrics['collection_rate'] >= 70 ? 'bg-warning' : 'bg-error') }}"
                             style="width: {{ min($metrics['collection_rate'], 100) }}%"></div>
                    </div>
                </div>
            </div>
        </x-card>
        <x-card class="bg-base-100 border border-base-content/10" shadow>
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="text-sm text-base-content/70 mb-1">Task Completion</div>
                    <div class="text-2xl font-bold text-warning mb-1">{{ $metrics['task_completion_rate'] }}%</div>
                    <div class="w-full bg-base-200 rounded-full h-2">
                        <div class="h-2 rounded-full {{ $metrics['task_completion_rate'] >= 80 ? 'bg-success' : ($metrics['task_completion_rate'] >= 50 ? 'bg-warning' : 'bg-error') }}"
                             style="width: {{ min($metrics['task_completion_rate'], 100) }}%"></div>
                    </div>
                </div>
            </div>
        </x-card>
    </div>

    <!-- REVENUE & CHARTS -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <x-card class="bg-base-100 border border-base-content/10" shadow>
            <x-header title="Financial Overview" separator />
            <div class="p-4 space-y-4">
                <div class="flex justify-between"><span class="text-base-content/70">YTD Revenue</span><span class="font-bold text-success">₱{{ number_format($metrics['yearly_revenue'], 2) }}</span></div>
                <div class="divider"></div>
                <div class="flex justify-between"><span class="text-base-content/70">Total Revenue</span><span class="font-semibold">₱{{ number_format($metrics['total_revenue'], 2) }}</span></div>
                <div class="flex justify-between"><span class="text-base-content/70">Pending</span><span class="font-semibold text-warning">₱{{ number_format($metrics['pending_amount'], 2) }}</span></div>
                <div class="flex justify-between"><span class="text-base-content/70">Overdue</span><span class="font-semibold text-error">₱{{ number_format($metrics['overdue_amount'], 2) }}</span></div>
            </div>
        </x-card>
        <x-card class="bg-base-100 border border-base-content/10 lg:col-span-2" shadow>
            <x-header title="Revenue Trend" subtitle="Last 12 months" separator />
            <div class="p-4" wire:ignore
                 data-revenue-labels="{{ json_encode($revenueTrend['labels']) }}"
                 data-revenue-data="{{ json_encode($revenueTrend['data']) }}">
                <canvas id="apartmentRevenueChart" height="260"></canvas>
            </div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <x-card class="bg-base-100 border border-base-content/10" shadow>
            <x-header title="Payment Status" separator />
            <div class="p-4" wire:ignore
                 data-payment-labels="{{ json_encode($paymentStatusData['labels']) }}"
                 data-payment-data="{{ json_encode($paymentStatusData['data']) }}"
                 data-payment-colors="{{ json_encode($paymentStatusData['colors']) }}">
                <canvas id="apartmentPaymentChart" height="260"></canvas>
            </div>
        </x-card>
        <x-card class="bg-base-100 border border-base-content/10" shadow>
            <x-header title="Task Status" separator />
            <div class="p-4" wire:ignore
                 data-task-labels="{{ json_encode($taskStatusData['labels']) }}"
                 data-task-data="{{ json_encode($taskStatusData['data']) }}"
                 data-task-colors="{{ json_encode($taskStatusData['colors']) }}">
                <canvas id="apartmentTaskChart" height="260"></canvas>
            </div>
        </x-card>
    </div>

    <!-- RECENT ACTIVITY -->
    <x-card class="bg-base-100 border border-base-content/10 mb-6" shadow>
        <x-header title="Recent Activity" subtitle="Last 20 activities" separator />
        <div class="p-6">
            @if(count($recentActivity) > 0)
                <div class="relative">
                    <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-base-300"></div>
                    <div class="space-y-6">
                        @foreach($recentActivity as $a)
                            <div class="relative flex items-start gap-4">
                                @php $bc = match($a['badge_color'] ?? '') { 'badge-success' => 'border-success', 'badge-warning' => 'border-warning', 'badge-error' => 'border-error', 'badge-info' => 'border-info', default => 'border-primary' }; @endphp
                                <div class="relative z-10 w-12 h-12 rounded-full bg-base-100 border-2 {{ $bc }} flex items-center justify-center">
                                    <x-icon name="{{ $a['icon'] }}" class="w-5 h-5 {{ $a['icon_color'] ?? 'text-primary' }}" />
                                </div>
                                <div class="flex-1 pb-6">
                                    <div class="bg-base-200/50 rounded-lg p-4 border border-base-300">
                                        <div class="flex justify-between gap-4">
                                            <div>
                                                <h4 class="font-semibold">{{ $a['title'] }}</h4>
                                                <p class="text-sm text-base-content/80">{{ $a['description'] }}</p>
                                                @if(!empty($a['subtitle']))<p class="text-xs text-base-content/60 mt-1">{{ $a['subtitle'] }}</p>@endif
                                            </div>
                                            <div class="text-right text-sm text-base-content/60">{{ $a['date']->format('M d, Y') }} · {{ $a['date']->diffForHumans() }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="text-center py-12 text-base-content/60">
                    <x-icon name="o-information-circle" class="w-16 h-16 mx-auto mb-4 opacity-50" />
                    <p class="font-medium">No recent activity</p>
                </div>
            @endif
        </div>
    </x-card>

    <!-- TENANTS, PAYMENTS, TASKS -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <x-card class="bg-base-100 border border-base-content/10" shadow>
            <x-header title="Tenants" separator />
            <div class="overflow-x-auto">
                <x-table :headers="[['key' => 'name', 'label' => 'Name'], ['key' => 'email', 'label' => 'Email'], ['key' => 'status', 'label' => 'Status'], ['key' => 'lease_end_date', 'label' => 'Lease End']]" :rows="$tenants" no-pagination>
                    @scope('cell_status', $t)
                        <span class="badge {{ $t->status === 'active' ? 'badge-success' : 'badge-ghost' }} badge-sm">{{ ucfirst($t->status) }}</span>
                    @endscope
                    @scope('cell_lease_end_date', $t)
                        {{ $t->lease_end_date ? $t->lease_end_date->format('M d, Y') : '—' }}
                    @endscope
                </x-table>
            </div>
        </x-card>
        <x-card class="bg-base-100 border border-base-content/10" shadow>
            <x-header title="Recent Payments" separator />
            <div class="overflow-x-auto">
                <x-table :headers="[['key' => 'tenant.name', 'label' => 'Tenant'], ['key' => 'amount', 'label' => 'Amount'], ['key' => 'payment_date', 'label' => 'Date'], ['key' => 'status', 'label' => 'Status']]" :rows="$recentPayments" no-pagination>
                    @scope('cell_amount', $p)<span class="font-semibold">₱{{ number_format($p->amount, 2) }}</span>@endscope
                    @scope('cell_payment_date', $p){{ $p->payment_date ? $p->payment_date->format('M d, Y') : '—' }}@endscope
                    @scope('cell_status', $p)
                        @php $pc = match($p->status) { 'paid' => 'badge-success', 'pending' => 'badge-warning', 'overdue' => 'badge-error', default => 'badge-ghost' }; @endphp
                        <span class="badge {{ $pc }} badge-sm">{{ ucfirst($p->status) }}</span>
                    @endscope
                </x-table>
            </div>
        </x-card>
        <x-card class="bg-base-100 border border-base-content/10" shadow>
            <x-header title="Active Tasks" separator />
            <div class="overflow-x-auto">
                <x-table :headers="[['key' => 'title', 'label' => 'Title'], ['key' => 'priority', 'label' => 'Priority'], ['key' => 'due_date', 'label' => 'Due'], ['key' => 'status', 'label' => 'Status']]" :rows="$activeTasks" no-pagination>
                    @scope('cell_priority', $t)
                        @php $prc = match($t->priority) { 'high' => 'badge-error', 'medium' => 'badge-warning', 'low' => 'badge-info', default => 'badge-ghost' }; @endphp
                        <span class="badge {{ $prc }} badge-sm">{{ ucfirst($t->priority) }}</span>
                    @endscope
                    @scope('cell_due_date', $t){{ $t->due_date ? $t->due_date->format('M d, Y') : '—' }}@endscope
                    @scope('cell_status', $t)
                        <span class="badge badge-warning badge-sm">{{ ucfirst(str_replace('_', ' ', $t->status)) }}</span>
                    @endscope
                </x-table>
            </div>
        </x-card>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
    <script defer>
        (function() {
            function init() {
                if (typeof Chart === 'undefined') { setTimeout(init, 50); return; }
                const rev = document.querySelector('[data-revenue-labels]');
                const revCtx = document.getElementById('apartmentRevenueChart');
                if (rev && revCtx) {
                    if (window.aptRevChart) window.aptRevChart.destroy();
                    window.aptRevChart = new Chart(revCtx, {
                        type: 'line',
                        data: {
                            labels: JSON.parse(rev.getAttribute('data-revenue-labels')),
                            datasets: [{ label: 'Revenue (₱)', data: JSON.parse(rev.getAttribute('data-revenue-data')), borderColor: 'rgb(59, 130, 246)', backgroundColor: 'rgba(59, 130, 246, 0.1)', fill: true, tension: 0.4 }]
                        },
                        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: true } } }
                    });
                }
                const pay = document.querySelector('[data-payment-labels]');
                const payCtx = document.getElementById('apartmentPaymentChart');
                if (pay && payCtx) {
                    if (window.aptPayChart) window.aptPayChart.destroy();
                    window.aptPayChart = new Chart(payCtx, {
                        type: 'doughnut',
                        data: {
                            labels: JSON.parse(pay.getAttribute('data-payment-labels')),
                            datasets: [{ data: JSON.parse(pay.getAttribute('data-payment-data')), backgroundColor: JSON.parse(pay.getAttribute('data-payment-colors')), borderWidth: 2 }]
                        },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
                    });
                }
                const tsk = document.querySelector('[data-task-labels]');
                const tskCtx = document.getElementById('apartmentTaskChart');
                if (tsk && tskCtx) {
                    if (window.aptTaskChart) window.aptTaskChart.destroy();
                    window.aptTaskChart = new Chart(tskCtx, {
                        type: 'doughnut',
                        data: {
                            labels: JSON.parse(tsk.getAttribute('data-task-labels')),
                            datasets: [{ data: JSON.parse(tsk.getAttribute('data-task-data')), backgroundColor: JSON.parse(tsk.getAttribute('data-task-colors')), borderWidth: 2 }]
                        },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
                    });
                }
            }
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
        })();
    </script>
</div>
