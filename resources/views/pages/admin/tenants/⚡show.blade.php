<?php

use App\Models\Tenant;
use App\Models\RentPayment;
use App\Models\Task;
use App\Services\TenantMetricsService;
use Livewire\Component;
use Mary\Traits\Toast;
use App\Traits\AuthorizesRole;
use Carbon\Carbon;

new class extends Component {
    use Toast;
    use AuthorizesRole;

    public Tenant $tenant;

    protected TenantMetricsService $metricsService;

    public function boot(TenantMetricsService $metricsService): void
    {
        $this->metricsService = $metricsService;
    }

    // Check admin access on mount
    public function mount(Tenant $tenant): void
    {
        $this->authorizeRole('admin');
        $this->tenant = $tenant->load(['apartment', 'owner']);
    }

    // Tenant Profile Info
    public function getTenantProfile(): array
    {
        return [
            'id' => $this->tenant->id,
            'name' => $this->tenant->name,
            'email' => $this->tenant->email,
            'phone' => $this->tenant->phone ?? 'N/A',
            'apartment_name' => $this->tenant->apartment?->name ?? 'N/A',
            'owner_name' => $this->tenant->owner?->name ?? 'N/A',
            'move_in_date' => $this->tenant->move_in_date,
            'lease_start_date' => $this->tenant->lease_start_date,
            'lease_end_date' => $this->tenant->lease_end_date,
            'monthly_rent' => $this->tenant->monthly_rent,
            'status' => $this->tenant->status,
        ];
    }

    // Key Performance Metrics
    public function getTenantMetrics(): array
    {
        return $this->metricsService->getTenantMetrics($this->tenant);
    }

    // Payment Trend (Last 12 months)
    public function getPaymentTrend(): array
    {
        return $this->metricsService->getPaymentTrend($this->tenant, 12);
    }

    // Payment Status Breakdown for Chart
    public function getPaymentStatusData(): array
    {
        $metrics = $this->getTenantMetrics();
        
        return [
            'labels' => ['Paid', 'Pending', 'Overdue'],
            'data' => [
                $metrics['paid_payment_records'],
                $metrics['pending_payment_records'],
                $metrics['overdue_payment_records'],
            ],
            'colors' => [
                'rgb(34, 197, 94)',  // success green
                'rgb(251, 191, 36)', // warning yellow
                'rgb(239, 68, 68)',  // error red
            ],
        ];
    }

    // Task Status Breakdown for Chart
    public function getTaskStatusData(): array
    {
        $metrics = $this->getTenantMetrics();
        $tasksByStatus = $metrics['tasks_by_status'];
        
        return [
            'labels' => ['To Do', 'In Progress', 'Done', 'Overdue'],
            'data' => [
                $tasksByStatus['todo'] ?? 0,
                $tasksByStatus['in_progress'] ?? 0,
                $tasksByStatus['done'] ?? 0,
                $metrics['overdue_tasks'],
            ],
            'colors' => [
                'rgb(59, 130, 246)',  // info blue
                'rgb(251, 191, 36)',  // warning yellow
                'rgb(34, 197, 94)',   // success green
                'rgb(239, 68, 68)',   // error red
            ],
        ];
    }

    // Calculate circular progress values
    public function getCircularProgressValues(): array
    {
        $metrics = $this->getTenantMetrics();
        $radius = 28;
        $circumference = 2 * pi() * $radius;
        
        return [
            'compliance' => [
                'circumference' => $circumference,
                'offset' => $circumference * (1 - min($metrics['payment_compliance_rate'], 100) / 100),
            ],
            'task_completion' => [
                'circumference' => $circumference,
                'offset' => $circumference * (1 - min($metrics['task_completion_rate'], 100) / 100),
            ],
        ];
    }

    // Recent Activity Timeline
    public function getRecentActivity(): array
    {
        $activities = collect();

        // Recent payments
        $recentPayments = $this->tenant->rentPayments()
            ->latest('updated_at')
            ->limit(15)
            ->get()
            ->map(function($payment) {
                $statusConfig = [
                    'paid' => ['icon' => 'o-banknotes', 'icon_color' => 'text-success', 'badge' => 'badge-success', 'title' => 'Payment Made'],
                    'pending' => ['icon' => 'o-clock', 'icon_color' => 'text-warning', 'badge' => 'badge-warning', 'title' => 'Payment Pending'],
                    'overdue' => ['icon' => 'o-exclamation-triangle', 'icon_color' => 'text-error', 'badge' => 'badge-error', 'title' => 'Payment Overdue'],
                ];
                
                $config = $statusConfig[$payment->status] ?? $statusConfig['pending'];
                
                return [
                    'type' => 'payment_' . $payment->status,
                    'icon' => $config['icon'],
                    'icon_color' => $config['icon_color'],
                    'badge_color' => $config['badge'],
                    'title' => $config['title'],
                    'description' => '₱' . number_format($payment->amount, 2),
                    'subtitle' => 'Period: ' . ($payment->payment_date ? $payment->payment_date->format('M Y') : 'N/A'),
                    'date' => $payment->payment_date ?? $payment->updated_at,
                    'timestamp' => $payment->updated_at,
                ];
            });

        // Recent task activities
        $recentTasks = Task::where('tenant_id', $this->tenant->id)
            ->with(['apartment'])
            ->latest('updated_at')
            ->limit(15)
            ->get()
            ->flatMap(function($task) {
                $activities = [];
                
                // Task created
                $activities[] = [
                    'type' => 'task_created',
                    'icon' => 'o-clipboard-document',
                    'icon_color' => 'text-info',
                    'badge_color' => 'badge-info',
                    'title' => 'Task Created',
                    'description' => $task->title,
                    'subtitle' => 'Priority: ' . ucfirst($task->priority),
                    'date' => $task->created_at,
                    'timestamp' => $task->created_at,
                ];
                
                // Task completed
                if ($task->status === 'done' && $task->completed_at) {
                    $activities[] = [
                        'type' => 'task_completed',
                        'icon' => 'o-check-circle',
                        'icon_color' => 'text-success',
                        'badge_color' => 'badge-success',
                        'title' => 'Task Completed',
                        'description' => $task->title,
                        'subtitle' => 'Priority: ' . ucfirst($task->priority),
                        'date' => $task->completed_at,
                        'timestamp' => $task->completed_at,
                    ];
                }
                
                // Task status changed
                if ($task->updated_at->gt($task->created_at) && $task->status !== 'done') {
                    $activities[] = [
                        'type' => 'task_updated',
                        'icon' => 'o-arrow-path',
                        'icon_color' => 'text-warning',
                        'badge_color' => 'badge-warning',
                        'title' => 'Task Status Changed',
                        'description' => $task->title,
                        'subtitle' => 'Status: ' . ucfirst(str_replace('_', ' ', $task->status)),
                        'date' => $task->updated_at,
                        'timestamp' => $task->updated_at,
                    ];
                }
                
                return $activities;
            });

        // Tenant updates
        if ($this->tenant->updated_at->gt($this->tenant->created_at)) {
            $activities->push([
                'type' => 'tenant_updated',
                'icon' => 'o-pencil-square',
                'icon_color' => 'text-info',
                'badge_color' => 'badge-info',
                'title' => 'Tenant Information Updated',
                'description' => $this->tenant->name,
                'subtitle' => 'Status: ' . ucfirst($this->tenant->status),
                'date' => $this->tenant->updated_at,
                'timestamp' => $this->tenant->updated_at,
            ]);
        }

        // Merge all activities and sort by timestamp
        $activities = $activities
            ->merge($recentPayments)
            ->merge($recentTasks)
            ->sortByDesc(function($activity) {
                return $activity['timestamp']->timestamp;
            })
            ->take(20)
            ->values()
            ->map(function($activity) {
                // Ensure date is a Carbon instance
                if (!$activity['date'] instanceof Carbon) {
                    $activity['date'] = Carbon::parse($activity['date']);
                }
                return $activity;
            })
            ->toArray();

        return $activities;
    }

    // Related Resources
    public function getRecentPayments(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->tenant->rentPayments()
            ->latest('payment_date')
            ->paginate(5);
    }

    public function getActiveTasks(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Task::where('tenant_id', $this->tenant->id)
            ->where('status', '!=', 'done')
            ->where('status', '!=', 'cancelled')
            ->with(['apartment'])
            ->latest()
            ->paginate(5);
    }

    public function with(): array
    {
        $metrics = $this->getTenantMetrics();
        $paymentTrend = $this->getPaymentTrend();
        $recentActivity = $this->getRecentActivity();
        $paymentStatusData = $this->getPaymentStatusData();
        $taskStatusData = $this->getTaskStatusData();
        $circularProgress = $this->getCircularProgressValues();

        return [
            'tenant' => $this->getTenantProfile(),
            'metrics' => $metrics,
            'paymentTrend' => $paymentTrend,
            'recentActivity' => $recentActivity,
            'paymentStatusData' => $paymentStatusData,
            'taskStatusData' => $taskStatusData,
            'circularProgress' => $circularProgress,
            'recentPayments' => $this->getRecentPayments(),
            'activeTasks' => $this->getActiveTasks(),
        ];
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Tenant Details" separator progress-indicator>
        <x-slot:subtitle>
            Comprehensive metrics and resources for {{ $tenant['name'] }}
        </x-slot:subtitle>
        <x-slot:actions>
            <x-button label="Back to Tenants" link="/admin/tenants" icon="o-arrow-left" class="btn-ghost" />
        </x-slot:actions>
    </x-header>

    <!-- TENANT PROFILE SECTION -->
    <x-card class="bg-base-100 shadow mb-6">
        <x-header title="Tenant Profile" separator />
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 p-4">
            <div>
                <div class="text-sm text-base-content/70 mb-1">Name</div>
                <div class="font-semibold text-lg">{{ $tenant['name'] }}</div>
            </div>
            <div>
                <div class="text-sm text-base-content/70 mb-1">Email</div>
                <div class="font-semibold">{{ $tenant['email'] }}</div>
            </div>
            <div>
                <div class="text-sm text-base-content/70 mb-1">Apartment</div>
                <div class="font-semibold">{{ $tenant['apartment_name'] }}</div>
            </div>
            <div>
                <div class="text-sm text-base-content/70 mb-1">Owner</div>
                <div class="font-semibold">{{ $tenant['owner_name'] }}</div>
            </div>
            <div>
                <div class="text-sm text-base-content/70 mb-1">Phone</div>
                <div class="font-semibold">{{ $tenant['phone'] }}</div>
            </div>
            <div>
                <div class="text-sm text-base-content/70 mb-1">Monthly Rent</div>
                <div class="font-semibold">₱{{ number_format($tenant['monthly_rent'], 2) }}</div>
            </div>
            <div>
                <div class="text-sm text-base-content/70 mb-1">Move In Date</div>
                <div class="font-semibold">{{ $tenant['move_in_date'] ? $tenant['move_in_date']->format('M d, Y') : 'N/A' }}</div>
                @if($tenant['move_in_date'])
                    <div class="text-xs text-base-content/60">{{ $tenant['move_in_date']->diffForHumans() }}</div>
                @endif
            </div>
            <div>
                <div class="text-sm text-base-content/70 mb-1">Lease End Date</div>
                <div class="font-semibold">{{ $tenant['lease_end_date'] ? $tenant['lease_end_date']->format('M d, Y') : 'N/A' }}</div>
                @if($tenant['lease_end_date'])
                    <div class="text-xs text-base-content/60">{{ $tenant['lease_end_date']->diffForHumans() }}</div>
                @endif
            </div>
        </div>
    </x-card>

    <!-- KEY PERFORMANCE METRICS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Payments -->
        <x-card class="bg-base-100 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Total Payments</div>
                    <div class="text-3xl font-bold text-primary">₱{{ number_format($metrics['total_payments'], 2) }}</div>
                    <div class="text-xs text-base-content/60 mt-1">
                        {{ $metrics['paid_payment_records'] }} paid, {{ $metrics['pending_payment_records'] }} pending
                    </div>
                </div>
                <x-icon name="o-banknotes" class="w-12 h-12 text-primary/20" />
            </div>
        </x-card>

        <!-- Payment Compliance Rate -->
        <x-card class="bg-base-100 shadow">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="text-sm text-base-content/70 mb-1">Payment Compliance</div>
                    <div class="text-3xl font-bold text-success mb-2">{{ $metrics['payment_compliance_rate'] }}%</div>
                    <div class="w-full bg-base-200 rounded-full h-3 mb-1">
                        <div 
                            class="h-3 rounded-full transition-all {{ $metrics['payment_compliance_rate'] >= 90 ? 'bg-success' : ($metrics['payment_compliance_rate'] >= 70 ? 'bg-warning' : 'bg-error') }}"
                            style="width: {{ min($metrics['payment_compliance_rate'], 100) }}%"
                        ></div>
                    </div>
                    <div class="text-xs text-base-content/60 mt-1">
                        {{ $metrics['paid_payment_records'] }} of {{ $metrics['total_payment_records'] }} paid
                    </div>
                </div>
                <div class="relative w-16 h-16 ml-4">
                    <svg class="transform -rotate-90 w-16 h-16">
                        <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="4" fill="none" class="text-base-200" />
                        <circle 
                            cx="32" 
                            cy="32" 
                            r="28" 
                            stroke="currentColor" 
                            stroke-width="4" 
                            fill="none"
                            stroke-dasharray="{{ $circularProgress['compliance']['circumference'] }}"
                            stroke-dashoffset="{{ $circularProgress['compliance']['offset'] }}"
                            class="transition-all {{ $metrics['payment_compliance_rate'] >= 90 ? 'text-success' : ($metrics['payment_compliance_rate'] >= 70 ? 'text-warning' : 'text-error') }}"
                        />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <x-icon name="o-banknotes" class="w-6 h-6 text-success/60" />
                    </div>
                </div>
            </div>
        </x-card>

        <!-- Task Completion Rate -->
        <x-card class="bg-base-100 shadow">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="text-sm text-base-content/70 mb-1">Task Completion</div>
                    <div class="text-3xl font-bold text-info mb-2">{{ $metrics['task_completion_rate'] }}%</div>
                    <div class="w-full bg-base-200 rounded-full h-3 mb-1">
                        <div 
                            class="h-3 rounded-full transition-all {{ $metrics['task_completion_rate'] >= 80 ? 'bg-success' : ($metrics['task_completion_rate'] >= 50 ? 'bg-warning' : 'bg-error') }}"
                            style="width: {{ min($metrics['task_completion_rate'], 100) }}%"
                        ></div>
                    </div>
                    <div class="text-xs text-base-content/60 mt-1">
                        {{ $metrics['completed_tasks'] }} of {{ $metrics['total_tasks'] }} completed
                    </div>
                </div>
                <div class="relative w-16 h-16 ml-4">
                    <svg class="transform -rotate-90 w-16 h-16">
                        <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="4" fill="none" class="text-base-200" />
                        <circle 
                            cx="32" 
                            cy="32" 
                            r="28" 
                            stroke="currentColor" 
                            stroke-width="4" 
                            fill="none"
                            stroke-dasharray="{{ $circularProgress['task_completion']['circumference'] }}"
                            stroke-dashoffset="{{ $circularProgress['task_completion']['offset'] }}"
                            class="transition-all {{ $metrics['task_completion_rate'] >= 80 ? 'text-success' : ($metrics['task_completion_rate'] >= 50 ? 'text-warning' : 'text-error') }}"
                        />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <x-icon name="o-clipboard-document-check" class="w-6 h-6 text-info/60" />
                    </div>
                </div>
            </div>
        </x-card>

        <!-- Lease Status -->
        <x-card class="bg-base-100 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Lease Status</div>
                    @php
                        $leaseStatusConfig = match($metrics['lease_status']) {
                            'expired' => ['text' => 'Expired', 'color' => 'text-error', 'badge' => 'badge-error'],
                            'expiring_soon' => ['text' => 'Expiring Soon', 'color' => 'text-warning', 'badge' => 'badge-warning'],
                            default => ['text' => 'Active', 'color' => 'text-success', 'badge' => 'badge-success'],
                        };
                    @endphp
                    <div class="text-3xl font-bold {{ $leaseStatusConfig['color'] }}">{{ $leaseStatusConfig['text'] }}</div>
                    @if($metrics['lease_days_remaining'] !== null)
                        <div class="text-xs text-base-content/60 mt-1">
                            {{ abs($metrics['lease_days_remaining']) }} days {{ $metrics['lease_days_remaining'] < 0 ? 'overdue' : 'remaining' }}
                        </div>
                    @endif
                    @if($metrics['tenure_days'] !== null)
                        <div class="text-xs text-base-content/60 mt-1">
                            {{ $metrics['tenure_days'] }} days tenure
                        </div>
                    @endif
                </div>
                <x-icon name="o-calendar" class="w-12 h-12 {{ $leaseStatusConfig['color'] }}/20" />
            </div>
        </x-card>
    </div>

    <!-- PAYMENT & FINANCIAL OVERVIEW -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Financial Summary -->
        <x-card class="bg-base-100 shadow">
            <x-header title="Financial Overview" separator />
            <div class="p-4 space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-base-content/70">Monthly Payments (MTD)</span>
                    <span class="text-2xl font-bold text-primary">₱{{ number_format($metrics['monthly_payments'], 2) }}</span>
                </div>
                <div class="divider"></div>
                <div class="flex justify-between items-center">
                    <span class="text-base-content/70">Year-to-Date Payments</span>
                    <span class="text-xl font-semibold text-success">₱{{ number_format($metrics['yearly_payments'], 2) }}</span>
                </div>
                <div class="divider"></div>
                <div class="flex justify-between items-center">
                    <span class="text-base-content/70">Pending Payments</span>
                    <span class="text-lg font-semibold text-warning">₱{{ number_format($metrics['pending_amount'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-base-content/70">Overdue Payments</span>
                    <span class="text-lg font-semibold text-error">₱{{ number_format($metrics['overdue_amount'], 2) }}</span>
                </div>
            </div>
        </x-card>

        <!-- Payment Trend Chart -->
        <x-card class="bg-base-100 shadow lg:col-span-2">
            <x-header title="Payment Trend" subtitle="Last 12 months" separator />
            <div class="p-4" wire:ignore 
                 data-payment-labels="{{ json_encode($paymentTrend['labels']) }}"
                 data-payment-data="{{ json_encode($paymentTrend['data']) }}">
                <canvas id="paymentTrendChart" height="300"></canvas>
            </div>
        </x-card>
    </div>

    <!-- PAYMENT & TASK STATUS CHARTS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Payment Status Chart -->
        <x-card class="bg-base-100 shadow">
            <x-header title="Payment Status Breakdown" separator />
            <div class="p-4" wire:ignore
                 data-payment-labels="{{ json_encode($paymentStatusData['labels']) }}"
                 data-payment-data="{{ json_encode($paymentStatusData['data']) }}"
                 data-payment-colors="{{ json_encode($paymentStatusData['colors']) }}">
                <canvas id="paymentStatusChart" height="300"></canvas>
            </div>
        </x-card>

        <!-- Task Status Chart -->
        <x-card class="bg-base-100 shadow">
            <x-header title="Task Status Breakdown" separator />
            <div class="p-4" wire:ignore
                 data-task-labels="{{ json_encode($taskStatusData['labels']) }}"
                 data-task-data="{{ json_encode($taskStatusData['data']) }}"
                 data-task-colors="{{ json_encode($taskStatusData['colors']) }}">
                <canvas id="taskStatusChart" height="300"></canvas>
            </div>
        </x-card>
    </div>

    <!-- RESOURCE SUMMARY CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Payments Summary -->
        <x-card class="bg-base-100 shadow">
            <x-header title="Payments" separator />
            <div class="p-4 space-y-2">
                <div class="flex justify-between">
                    <span class="text-sm text-base-content/70">Total</span>
                    <span class="font-bold">{{ $metrics['total_payment_records'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-base-content/70">Paid</span>
                    <span class="badge badge-success">{{ $metrics['paid_payment_records'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-base-content/70">Pending</span>
                    <span class="badge badge-warning">{{ $metrics['pending_payment_records'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-base-content/70">Overdue</span>
                    <span class="badge badge-error">{{ $metrics['overdue_payment_records'] }}</span>
                </div>
            </div>
        </x-card>

        <!-- Tasks Summary -->
        <x-card class="bg-base-100 shadow">
            <x-header title="Tasks" separator />
            <div class="p-4 space-y-2">
                <div class="flex justify-between">
                    <span class="text-sm text-base-content/70">Total</span>
                    <span class="font-bold">{{ $metrics['total_tasks'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-base-content/70">To Do</span>
                    <span class="badge badge-info">{{ $metrics['tasks_by_status']['todo'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-base-content/70">In Progress</span>
                    <span class="badge badge-warning">{{ $metrics['tasks_by_status']['in_progress'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-base-content/70">Done</span>
                    <span class="badge badge-success">{{ $metrics['completed_tasks'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-base-content/70">Overdue</span>
                    <span class="badge badge-error">{{ $metrics['overdue_tasks'] }}</span>
                </div>
            </div>
        </x-card>

        <!-- Lease Summary -->
        <x-card class="bg-base-100 shadow">
            <x-header title="Lease Information" separator />
            <div class="p-4 space-y-2">
                <div class="flex justify-between">
                    <span class="text-sm text-base-content/70">Status</span>
                    @php
                        $leaseBadge = match($metrics['lease_status']) {
                            'expired' => 'badge-error',
                            'expiring_soon' => 'badge-warning',
                            default => 'badge-success',
                        };
                    @endphp
                    <span class="badge {{ $leaseBadge }}">{{ ucfirst(str_replace('_', ' ', $metrics['lease_status'])) }}</span>
                </div>
                @if($metrics['lease_days_remaining'] !== null)
                    <div class="flex justify-between">
                        <span class="text-sm text-base-content/70">Days Remaining</span>
                        <span class="font-semibold">{{ abs($metrics['lease_days_remaining']) }}</span>
                    </div>
                @endif
                @if($metrics['tenure_days'] !== null)
                    <div class="flex justify-between">
                        <span class="text-sm text-base-content/70">Tenure</span>
                        <span class="font-semibold">{{ $metrics['tenure_days'] }} days</span>
                    </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-sm text-base-content/70">Monthly Rent</span>
                    <span class="font-semibold">₱{{ number_format($tenant['monthly_rent'], 2) }}</span>
                </div>
            </div>
        </x-card>

        <!-- Activity Summary -->
        <x-card class="bg-base-100 shadow">
            <x-header title="Activity" separator />
            <div class="p-4 space-y-2">
                @if($metrics['last_activity'])
                    <div class="flex justify-between">
                        <span class="text-sm text-base-content/70">Last Activity</span>
                        <span class="text-xs text-base-content/60">{{ \Carbon\Carbon::parse($metrics['last_activity'])->diffForHumans() }}</span>
                    </div>
                @else
                    <div class="text-sm text-base-content/50">No recent activity</div>
                @endif
            </div>
        </x-card>
    </div>

    <!-- RECENT ACTIVITY TIMELINE -->
    <x-card class="bg-base-100 shadow mb-6">
        <x-header title="Recent Activity Timeline" subtitle="Last 20 activities" separator />
        <div class="p-6">
            @if(count($recentActivity) > 0)
                <div class="relative">
                    <!-- Timeline line -->
                    <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-base-300"></div>
                    
                    <div class="space-y-6">
                        @foreach($recentActivity as $index => $activity)
                            <div class="relative flex items-start gap-4 group">
                                <!-- Timeline dot -->
                                @php
                                    $borderColor = match($activity['badge_color'] ?? 'badge-primary') {
                                        'badge-primary' => 'border-primary',
                                        'badge-success' => 'border-success',
                                        'badge-warning' => 'border-warning',
                                        'badge-error' => 'border-error',
                                        'badge-info' => 'border-info',
                                        default => 'border-primary',
                                    };
                                @endphp
                                <div class="relative z-10 flex items-center justify-center w-12 h-12 rounded-full bg-base-100 border-2 {{ $borderColor }} shadow-md group-hover:scale-110 transition-transform">
                                    <x-icon name="{{ $activity['icon'] }}" class="w-5 h-5 {{ $activity['icon_color'] ?? 'text-primary' }}" />
                                </div>
                                
                                <!-- Activity content -->
                                <div class="flex-1 min-w-0 pb-6">
                                    <div class="bg-base-200/50 hover:bg-base-200 rounded-lg p-4 transition-all border border-base-300 group-hover:border-primary/30 group-hover:shadow-md">
                                        <div class="flex items-start justify-between gap-4 mb-2">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <h4 class="font-semibold text-base text-base-content">{{ $activity['title'] }}</h4>
                                                    <span class="badge {{ $activity['badge_color'] ?? 'badge-primary' }} badge-sm">
                                                        {{ ucfirst(str_replace('_', ' ', explode('_', $activity['type'])[0])) }}
                                                    </span>
                                                </div>
                                                <p class="text-sm text-base-content/80 font-medium mb-1">
                                                    {{ $activity['description'] }}
                                                </p>
                                                @if(isset($activity['subtitle']))
                                                    <p class="text-xs text-base-content/60 mt-1">
                                                        {{ $activity['subtitle'] }}
                                                    </p>
                                                @endif
                                            </div>
                                            <div class="flex flex-col items-end shrink-0 text-right">
                                                <time class="text-xs font-medium text-base-content/70 whitespace-nowrap">
                                                    {{ $activity['date']->format('M d, Y') }}
                                                </time>
                                                <time class="text-xs text-base-content/50 whitespace-nowrap">
                                                    {{ $activity['date']->format('h:i A') }}
                                                </time>
                                                <span class="text-xs text-base-content/50 mt-1">
                                                    {{ $activity['date']->diffForHumans() }}
                                                </span>
                                            </div>
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
                    <p class="text-lg font-medium mb-2">No recent activity</p>
                    <p class="text-sm">Activity will appear here as the tenant interacts with the system.</p>
                </div>
            @endif
        </div>
    </x-card>

    <!-- RELATED RESOURCES TABLES -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Payments -->
        <x-card class="bg-base-100 shadow">
            <x-header title="Recent Payments" separator />
            <div class="overflow-x-auto">
                <x-table :headers="[
                    ['key' => 'amount', 'label' => 'Amount'],
                    ['key' => 'payment_date', 'label' => 'Date'],
                    ['key' => 'status', 'label' => 'Status'],
                ]" :rows="$recentPayments" no-pagination>
                    @scope('cell_amount', $payment)
                        <div class="font-semibold">
                            ₱{{ number_format($payment->amount, 2) }}
                        </div>
                    @endscope

                    @scope('cell_payment_date', $payment)
                        @if($payment->payment_date)
                            <div class="text-sm">
                                {{ $payment->payment_date->format('M d, Y') }}
                            </div>
                        @else
                            <span class="text-base-content/50">N/A</span>
                        @endif
                    @endscope

                    @scope('cell_status', $payment)
                        @php
                            $statusColors = [
                                'paid' => 'badge-success',
                                'pending' => 'badge-warning',
                                'overdue' => 'badge-error',
                            ];
                            $color = $statusColors[$payment->status] ?? 'badge-ghost';
                        @endphp
                        <span class="badge {{ $color }} badge-sm">
                            {{ ucfirst($payment->status) }}
                        </span>
                    @endscope
                </x-table>
            </div>
        </x-card>

        <!-- Active Tasks -->
        <x-card class="bg-base-100 shadow">
            <x-header title="Active Tasks" separator />
            <div class="overflow-x-auto">
                <x-table :headers="[
                    ['key' => 'title', 'label' => 'Title'],
                    ['key' => 'priority', 'label' => 'Priority'],
                    ['key' => 'due_date', 'label' => 'Due Date'],
                    ['key' => 'status', 'label' => 'Status'],
                ]" :rows="$activeTasks" no-pagination>
                    @scope('cell_priority', $task)
                        @php
                            $priorityColors = [
                                'low' => 'badge-info',
                                'medium' => 'badge-warning',
                                'high' => 'badge-error',
                            ];
                            $color = $priorityColors[$task->priority] ?? 'badge-ghost';
                        @endphp
                        <span class="badge {{ $color }} badge-sm">
                            {{ ucfirst($task->priority) }}
                        </span>
                    @endscope

                    @scope('cell_due_date', $task)
                        @if($task->due_date)
                            <div class="text-sm">
                                {{ $task->due_date->format('M d, Y') }}
                            </div>
                            @if($task->due_date->isPast())
                                <div class="text-xs text-error">Overdue</div>
                            @endif
                        @else
                            <span class="text-base-content/50">No due date</span>
                        @endif
                    @endscope

                    @scope('cell_status', $task)
                        @php
                            $statusColors = [
                                'todo' => 'badge-info',
                                'in_progress' => 'badge-warning',
                                'done' => 'badge-success',
                                'cancelled' => 'badge-ghost',
                            ];
                            $color = $statusColors[$task->status] ?? 'badge-ghost';
                        @endphp
                        <span class="badge {{ $color }} badge-sm">
                            {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                        </span>
                    @endscope
                </x-table>
            </div>
        </x-card>
    </div>

    <!-- Chart.js Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
    <script defer>
        (function() {
            function initializeCharts() {
                // Payment Trend Chart
                const paymentTrendContainer = document.querySelector('[data-payment-labels]');
                const paymentTrendCtx = document.getElementById('paymentTrendChart');
                
                if (paymentTrendCtx && paymentTrendContainer && typeof Chart !== 'undefined') {
                    if (window.paymentTrendChartInstance) {
                        window.paymentTrendChartInstance.destroy();
                    }
                    
                    const paymentLabels = JSON.parse(paymentTrendContainer.getAttribute('data-payment-labels'));
                    const paymentData = JSON.parse(paymentTrendContainer.getAttribute('data-payment-data'));
                    
                    window.paymentTrendChartInstance = new Chart(paymentTrendCtx, {
                        type: 'line',
                        data: {
                            labels: paymentLabels,
                            datasets: [{
                                label: 'Payments (₱)',
                                data: paymentData,
                                borderColor: 'rgb(34, 197, 94)',
                                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                tension: 0.4,
                                fill: true,
                                pointRadius: 4,
                                pointHoverRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return '₱' + context.parsed.y.toLocaleString('en-US', {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2
                                            });
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return '₱' + value.toLocaleString();
                                        }
                                    }
                                }
                            }
                        }
                    });
                }

                // Payment Status Chart
                const paymentContainer = document.querySelector('[data-payment-labels]');
                const paymentCtx = document.getElementById('paymentStatusChart');
                
                if (paymentCtx && paymentContainer && typeof Chart !== 'undefined') {
                    if (window.paymentChartInstance) {
                        window.paymentChartInstance.destroy();
                    }
                    
                    const paymentLabels = JSON.parse(paymentContainer.getAttribute('data-payment-labels'));
                    const paymentData = JSON.parse(paymentContainer.getAttribute('data-payment-data'));
                    const paymentColors = JSON.parse(paymentContainer.getAttribute('data-payment-colors'));
                    
                    window.paymentChartInstance = new Chart(paymentCtx, {
                        type: 'doughnut',
                        data: {
                            labels: paymentLabels,
                            datasets: [{
                                data: paymentData,
                                backgroundColor: paymentColors,
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'bottom'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.parsed || 0;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                            return label + ': ' + value + ' (' + percentage + '%)';
                                        }
                                    }
                                }
                            }
                        }
                    });
                }

                // Task Status Chart
                const taskContainer = document.querySelector('[data-task-labels]');
                const taskCtx = document.getElementById('taskStatusChart');
                
                if (taskCtx && taskContainer && typeof Chart !== 'undefined') {
                    if (window.taskChartInstance) {
                        window.taskChartInstance.destroy();
                    }
                    
                    const taskLabels = JSON.parse(taskContainer.getAttribute('data-task-labels'));
                    const taskData = JSON.parse(taskContainer.getAttribute('data-task-data'));
                    const taskColors = JSON.parse(taskContainer.getAttribute('data-task-colors'));
                    
                    window.taskChartInstance = new Chart(taskCtx, {
                        type: 'doughnut',
                        data: {
                            labels: taskLabels,
                            datasets: [{
                                data: taskData,
                                backgroundColor: taskColors,
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'bottom'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.parsed || 0;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                            return label + ': ' + value + ' (' + percentage + '%)';
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }

            // Wait for Chart.js to load and DOM to be ready
            function waitForChartAndInit() {
                if (typeof Chart !== 'undefined') {
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', initializeCharts);
                    } else {
                        initializeCharts();
                    }
                } else {
                    setTimeout(waitForChartAndInit, 50);
                }
            }

            waitForChartAndInit();
        })();
    </script>
</div>
