<?php

use App\Models\User;
use App\Models\Apartment;
use App\Models\Tenant;
use App\Models\RentPayment;
use App\Models\Task;
use App\Models\Notification;
use App\Services\OwnerMetricsService;
use Livewire\Component;
use Mary\Traits\Toast;
use App\Traits\AuthorizesRole;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use Toast;
    use AuthorizesRole;

    public User $user;

    protected OwnerMetricsService $metricsService;

    public function boot(OwnerMetricsService $metricsService): void
    {
        $this->metricsService = $metricsService;
    }

    // Check admin access on mount
    public function mount(User $user): void
    {
        $this->authorizeRole('admin');
        
        // Ensure user is an owner
        if ($user->role !== 'owner') {
            $this->error('User is not an owner.', position: 'toast-bottom');
            $this->redirect('/admin/owners');
            return;
        }
        
        $this->user = $user;
    }

    // Owner Profile Info
    public function getOwnerProfile(): array
    {
        return [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'country' => $this->user->country?->name ?? 'N/A',
            'registration_date' => $this->user->created_at,
            'last_login' => $this->user->updated_at,
        ];
    }

    // Key Performance Metrics
    public function getOwnerMetrics(): array
    {
        return $this->metricsService->getOwnerMetrics($this->user);
    }

    // Revenue Trend (Last 12 months)
    public function getRevenueTrend(): array
    {
        return $this->metricsService->getRevenueTrend($this->user, 12);
    }

    // Payment Status Breakdown for Chart
    public function getPaymentStatusData(): array
    {
        $metrics = $this->getOwnerMetrics();
        
        return [
            'labels' => ['Paid', 'Pending', 'Overdue'],
            'data' => [
                $metrics['paid_payments'],
                $metrics['pending_payments'],
                $metrics['overdue_payments'],
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
        $metrics = $this->getOwnerMetrics();
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
        $metrics = $this->getOwnerMetrics();
        $radius = 28;
        $circumference = 2 * pi() * $radius;
        
        return [
            'occupancy' => [
                'circumference' => $circumference,
                'offset' => $circumference * (1 - min($metrics['occupancy_rate'], 100) / 100),
            ],
            'collection' => [
                'circumference' => $circumference,
                'offset' => $circumference * (1 - min($metrics['collection_rate'], 100) / 100),
            ],
        ];
    }

    // Recent Activity Timeline
    public function getRecentActivity(): array
    {
        $activities = collect();

        // Recent apartments (created)
        $recentApartments = $this->user->apartments()
            ->with('location')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function($apartment) {
                return [
                    'type' => 'apartment_created',
                    'icon' => 'o-building-office',
                    'icon_color' => 'text-primary',
                    'badge_color' => 'badge-primary',
                    'title' => 'Apartment Added',
                    'description' => $apartment->name . ' in ' . ($apartment->location->name ?? 'N/A'),
                    'subtitle' => 'Status: ' . ucfirst($apartment->status),
                    'date' => $apartment->created_at,
                    'timestamp' => $apartment->created_at,
                ];
            });

        // Recent apartment updates (only if updated after creation)
        $updatedApartments = $this->user->apartments()
            ->with('location')
            ->whereColumn('updated_at', '>', 'created_at')
            ->latest('updated_at')
            ->limit(10)
            ->get()
            ->map(function($apartment) {
                return [
                    'type' => 'apartment_updated',
                    'icon' => 'o-pencil-square',
                    'icon_color' => 'text-info',
                    'badge_color' => 'badge-info',
                    'title' => 'Apartment Updated',
                    'description' => $apartment->name . ' in ' . ($apartment->location->name ?? 'N/A'),
                    'subtitle' => 'Status: ' . ucfirst($apartment->status),
                    'date' => $apartment->updated_at,
                    'timestamp' => $apartment->updated_at,
                ];
            });

        // Recent tenants (created)
        $recentTenants = $this->user->tenants()
            ->with('apartment')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function($tenant) {
                return [
                    'type' => 'tenant_created',
                    'icon' => 'o-user-plus',
                    'icon_color' => 'text-success',
                    'badge_color' => 'badge-success',
                    'title' => 'New Tenant Registered',
                    'description' => $tenant->name,
                    'subtitle' => 'Apartment: ' . ($tenant->apartment->name ?? 'N/A'),
                    'date' => $tenant->created_at,
                    'timestamp' => $tenant->created_at,
                ];
            });

        // Recent payments (all statuses)
        $recentPayments = RentPayment::whereHas('tenant', function($query) {
                $query->where('owner_id', $this->user->id);
            })
            ->with(['tenant', 'apartment'])
            ->latest('updated_at')
            ->limit(15)
            ->get()
            ->map(function($payment) {
                $statusConfig = [
                    'paid' => ['icon' => 'o-banknotes', 'icon_color' => 'text-success', 'badge' => 'badge-success', 'title' => 'Payment Received'],
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
                    'description' => '₱' . number_format($payment->amount, 2) . ' from ' . ($payment->tenant->name ?? 'N/A'),
                    'subtitle' => 'Apartment: ' . ($payment->apartment->name ?? 'N/A'),
                    'date' => $payment->payment_date ?? $payment->updated_at,
                    'timestamp' => $payment->updated_at,
                ];
            });

        // Recent task activities (created, updated, completed)
        $recentTasks = Task::where('owner_id', $this->user->id)
            ->with(['apartment', 'tenant'])
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
                
                // Task status changed (if updated after creation)
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

        // System notifications/interactions
        $recentNotifications = Notification::where('user_id', $this->user->id)
            ->latest()
            ->limit(10)
            ->get()
            ->map(function($notification) {
                return [
                    'type' => 'notification',
                    'icon' => 'o-bell',
                    'icon_color' => 'text-info',
                    'badge_color' => 'badge-info',
                    'title' => $notification->title,
                    'description' => $notification->message,
                    'subtitle' => 'Type: ' . ucfirst($notification->type),
                    'date' => $notification->created_at,
                    'timestamp' => $notification->created_at,
                ];
            });

        // Merge all activities and sort by timestamp
        $activities = $activities
            ->merge($recentApartments)
            ->merge($updatedApartments)
            ->merge($recentTenants)
            ->merge($recentPayments)
            ->merge($recentTasks)
            ->merge($recentNotifications)
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
    public function getApartments(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->user->apartments()
            ->with(['location'])
            ->withCount('tenants')
            ->latest()
            ->paginate(5);
    }

    public function getTenants(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->user->tenants()
            ->with(['apartment'])
            ->latest()
            ->paginate(5);
    }

    public function getRecentPayments(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return RentPayment::whereHas('tenant', function($query) {
                $query->where('owner_id', $this->user->id);
            })
            ->with(['tenant', 'apartment'])
            ->latest('payment_date')
            ->paginate(5);
    }

    public function getActiveTasks(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Task::where('owner_id', $this->user->id)
            ->where('status', '!=', 'done')
            ->where('status', '!=', 'cancelled')
            ->with(['apartment', 'tenant'])
            ->latest()
            ->paginate(5);
    }

    public function with(): array
    {
        $metrics = $this->getOwnerMetrics();
        $revenueTrend = $this->getRevenueTrend();
        $recentActivity = $this->getRecentActivity();
        $paymentStatusData = $this->getPaymentStatusData();
        $taskStatusData = $this->getTaskStatusData();
        $circularProgress = $this->getCircularProgressValues();

        return [
            'owner' => $this->getOwnerProfile(),
            'metrics' => $metrics,
            'revenueTrend' => $revenueTrend,
            'recentActivity' => $recentActivity,
            'paymentStatusData' => $paymentStatusData,
            'taskStatusData' => $taskStatusData,
            'circularProgress' => $circularProgress,
            'apartments' => $this->getApartments(),
            'tenants' => $this->getTenants(),
            'recentPayments' => $this->getRecentPayments(),
            'activeTasks' => $this->getActiveTasks(),
        ];
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Owner Details" separator progress-indicator>
        <x-slot:subtitle>
            Comprehensive metrics and resources for {{ $owner['name'] }}
        </x-slot:subtitle>
        <x-slot:actions>
            <x-button label="Back to Owners" link="/admin/owners" icon="o-arrow-left" class="btn-ghost" />
        </x-slot:actions>
    </x-header>

    <!-- OWNER PROFILE SECTION -->
    <x-card class="bg-base-100 shadow mb-6">
        <x-header title="Owner Profile" separator />
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 p-4">
            <div>
                <div class="text-sm text-base-content/70 mb-1">Name</div>
                <div class="font-semibold text-lg">{{ $owner['name'] }}</div>
            </div>
            <div>
                <div class="text-sm text-base-content/70 mb-1">Email</div>
                <div class="font-semibold">{{ $owner['email'] }}</div>
            </div>
            <div>
                <div class="text-sm text-base-content/70 mb-1">Country</div>
                <div class="font-semibold">{{ $owner['country'] }}</div>
            </div>
            <div>
                <div class="text-sm text-base-content/70 mb-1">Registration Date</div>
                <div class="font-semibold">{{ $owner['registration_date']->format('M d, Y') }}</div>
                <div class="text-xs text-base-content/60">{{ $owner['registration_date']->diffForHumans() }}</div>
            </div>
        </div>
    </x-card>

    <!-- KEY PERFORMANCE METRICS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Apartments -->
        <x-card class="bg-base-100 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Total Apartments</div>
                    <div class="text-3xl font-bold text-primary">{{ $metrics['total_apartments'] }}</div>
                    <div class="text-xs text-base-content/60 mt-1">
                        {{ $metrics['occupied_apartments'] }} occupied, {{ $metrics['available_apartments'] }} available
                    </div>
                </div>
                <x-icon name="o-building-office" class="w-12 h-12 text-primary/20" />
            </div>
        </x-card>

        <!-- Active Tenants -->
        <x-card class="bg-base-100 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/70 mb-1">Active Tenants</div>
                    <div class="text-3xl font-bold text-success">{{ $metrics['active_tenants'] }}</div>
                    <div class="text-xs text-base-content/60 mt-1">
                        {{ $metrics['new_tenants_this_month'] }} new this month
                    </div>
                </div>
                <x-icon name="o-users" class="w-12 h-12 text-success/20" />
            </div>
        </x-card>

        <!-- Occupancy Rate -->
        <x-card class="bg-base-100 shadow">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="text-sm text-base-content/70 mb-1">Occupancy Rate</div>
                    <div class="text-3xl font-bold text-info mb-2">{{ $metrics['occupancy_rate'] }}%</div>
                    <div class="w-full bg-base-200 rounded-full h-3 mb-1">
                        <div 
                            class="h-3 rounded-full transition-all {{ $metrics['occupancy_rate'] >= 80 ? 'bg-success' : ($metrics['occupancy_rate'] >= 50 ? 'bg-warning' : 'bg-error') }}"
                            style="width: {{ min($metrics['occupancy_rate'], 100) }}%"
                        ></div>
                    </div>
                    <div class="text-xs text-base-content/60 mt-1">
                        {{ $metrics['occupied_apartments'] }} of {{ $metrics['total_apartments'] }} occupied
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
                            stroke-dasharray="{{ $circularProgress['occupancy']['circumference'] }}"
                            stroke-dashoffset="{{ $circularProgress['occupancy']['offset'] }}"
                            class="transition-all {{ $metrics['occupancy_rate'] >= 80 ? 'text-success' : ($metrics['occupancy_rate'] >= 50 ? 'text-warning' : 'text-error') }}"
                        />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <x-icon name="o-chart-bar" class="w-6 h-6 text-info/60" />
                    </div>
                </div>
            </div>
        </x-card>

        <!-- Payment Collection Rate -->
        <x-card class="bg-base-100 shadow">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="text-sm text-base-content/70 mb-1">Collection Rate</div>
                    <div class="text-3xl font-bold text-warning mb-2">{{ $metrics['collection_rate'] }}%</div>
                    <div class="w-full bg-base-200 rounded-full h-3 mb-1">
                        <div 
                            class="h-3 rounded-full transition-all {{ $metrics['collection_rate'] >= 90 ? 'bg-success' : ($metrics['collection_rate'] >= 70 ? 'bg-warning' : 'bg-error') }}"
                            style="width: {{ min($metrics['collection_rate'], 100) }}%"
                        ></div>
                    </div>
                    <div class="text-xs text-base-content/60 mt-1">
                        {{ $metrics['paid_payments'] }} of {{ $metrics['total_payments'] }} paid
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
                            stroke-dasharray="{{ $circularProgress['collection']['circumference'] }}"
                            stroke-dashoffset="{{ $circularProgress['collection']['offset'] }}"
                            class="transition-all {{ $metrics['collection_rate'] >= 90 ? 'text-success' : ($metrics['collection_rate'] >= 70 ? 'text-warning' : 'text-error') }}"
                        />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <x-icon name="o-banknotes" class="w-6 h-6 text-warning/60" />
                    </div>
                </div>
            </div>
        </x-card>
    </div>

    <!-- REVENUE & FINANCIAL OVERVIEW -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Financial Summary -->
        <x-card class="bg-base-100 shadow">
            <x-header title="Financial Overview" separator />
            <div class="p-4 space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-base-content/70">Monthly Revenue (MTD)</span>
                    <span class="text-2xl font-bold text-primary">₱{{ number_format($metrics['monthly_revenue'], 2) }}</span>
                </div>
                <div class="divider"></div>
                <div class="flex justify-between items-center">
                    <span class="text-base-content/70">Year-to-Date Revenue</span>
                    <span class="text-xl font-semibold text-success">₱{{ number_format($metrics['yearly_revenue'], 2) }}</span>
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

        <!-- Revenue Trend Chart -->
        <x-card class="bg-base-100 shadow lg:col-span-2">
            <x-header title="Revenue Trend" subtitle="Last 12 months" separator />
            <div class="p-4" wire:ignore 
                 data-revenue-labels="{{ json_encode($revenueTrend['labels']) }}"
                 data-revenue-data="{{ json_encode($revenueTrend['data']) }}">
                <canvas id="revenueChart" height="300"></canvas>
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
        <!-- Apartments Summary -->
        <x-card class="bg-base-100 shadow">
            <x-header title="Apartments" separator />
            <div class="p-4 space-y-2">
                <div class="flex justify-between">
                    <span class="text-sm text-base-content/70">Total</span>
                    <span class="font-bold">{{ $metrics['total_apartments'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-base-content/70">Occupied</span>
                    <span class="badge badge-success">{{ $metrics['occupied_apartments'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-base-content/70">Available</span>
                    <span class="badge badge-info">{{ $metrics['available_apartments'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-base-content/70">Maintenance</span>
                    <span class="badge badge-warning">{{ $metrics['maintenance_apartments'] }}</span>
                </div>
            </div>
        </x-card>

        <!-- Tenants Summary -->
        <x-card class="bg-base-100 shadow">
            <x-header title="Tenants" separator />
            <div class="p-4 space-y-2">
                <div class="flex justify-between">
                    <span class="text-sm text-base-content/70">Active</span>
                    <span class="font-bold">{{ $metrics['active_tenants'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-base-content/70">Expiring Soon</span>
                    <span class="badge badge-warning">{{ $metrics['expiring_leases'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-base-content/70">New This Month</span>
                    <span class="badge badge-success">{{ $metrics['new_tenants_this_month'] }}</span>
                </div>
            </div>
        </x-card>

        <!-- Payments Summary -->
        <x-card class="bg-base-100 shadow">
            <x-header title="Payments" separator />
            <div class="p-4 space-y-2">
                <div class="flex justify-between">
                    <span class="text-sm text-base-content/70">Paid</span>
                    <span class="badge badge-success">{{ $metrics['collection_rate'] }}%</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-base-content/70">Pending</span>
                    <span class="badge badge-warning">₱{{ number_format($metrics['pending_amount'], 0) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-base-content/70">Overdue</span>
                    <span class="badge badge-error">₱{{ number_format($metrics['overdue_amount'], 0) }}</span>
                </div>
            </div>
        </x-card>

        <!-- Tasks Summary -->
        <x-card class="bg-base-100 shadow">
            <x-header title="Tasks" separator />
            <div class="p-4 space-y-2">
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
                    <span class="badge badge-success">{{ $metrics['tasks_by_status']['done'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-base-content/70">Overdue</span>
                    <span class="badge badge-error">{{ $metrics['overdue_tasks'] }}</span>
                </div>
            </div>
        </x-card>
    </div>

    <!-- RECENT ACTIVITY TIMELINE -->
    <x-card class="bg-base-100 shadow mb-6">
        <x-header title="Recent Activity Timeline" subtitle="Last 20 activities across all resources" separator />
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
                    <p class="text-sm">Activity will appear here as the owner interacts with the system.</p>
                </div>
            @endif
        </div>
    </x-card>

    <!-- RELATED RESOURCES TABLES -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Apartments List -->
        <x-card class="bg-base-100 shadow">
            <x-header title="Apartments" separator />
            <div class="overflow-x-auto">
                <x-table :headers="[
                    ['key' => 'name', 'label' => 'Name'],
                    ['key' => 'location.name', 'label' => 'Location'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'monthly_rent', 'label' => 'Rent'],
                ]" :rows="$apartments" no-pagination>
                    @scope('cell_status', $apartment)
                        @php
                            $statusColors = [
                                'available' => 'badge-success',
                                'occupied' => 'badge-info',
                                'maintenance' => 'badge-warning',
                            ];
                            $color = $statusColors[$apartment->status] ?? 'badge-ghost';
                        @endphp
                        <span class="badge {{ $color }} badge-sm">
                            {{ ucfirst($apartment->status) }}
                        </span>
                    @endscope

                    @scope('cell_monthly_rent', $apartment)
                        <div class="font-semibold">
                            ₱{{ number_format($apartment->monthly_rent, 2) }}
                        </div>
                    @endscope
                </x-table>
            </div>
        </x-card>

        <!-- Tenants List -->
        <x-card class="bg-base-100 shadow">
            <x-header title="Tenants" separator />
            <div class="overflow-x-auto">
                <x-table :headers="[
                    ['key' => 'name', 'label' => 'Name'],
                    ['key' => 'apartment.name', 'label' => 'Apartment'],
                    ['key' => 'lease_end_date', 'label' => 'Lease End'],
                    ['key' => 'status', 'label' => 'Status'],
                ]" :rows="$tenants" no-pagination>
                    @scope('cell_lease_end_date', $tenant)
                        @if($tenant->lease_end_date)
                            <div class="text-sm">
                                {{ $tenant->lease_end_date->format('M d, Y') }}
                            </div>
                            <div class="text-xs text-base-content/60">
                                {{ $tenant->lease_end_date->diffForHumans() }}
                            </div>
                        @else
                            <span class="text-base-content/50">N/A</span>
                        @endif
                    @endscope

                    @scope('cell_status', $tenant)
                        @php
                            $statusColors = [
                                'active' => 'badge-success',
                                'inactive' => 'badge-ghost',
                            ];
                            $color = $statusColors[$tenant->status] ?? 'badge-ghost';
                        @endphp
                        <span class="badge {{ $color }} badge-sm">
                            {{ ucfirst($tenant->status) }}
                        </span>
                    @endscope
                </x-table>
            </div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Payments -->
        <x-card class="bg-base-100 shadow">
            <x-header title="Recent Payments" separator />
            <div class="overflow-x-auto">
                <x-table :headers="[
                    ['key' => 'tenant.name', 'label' => 'Tenant'],
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
                // Revenue Chart
                const revenueContainer = document.querySelector('[data-revenue-labels]');
                const revenueCtx = document.getElementById('revenueChart');
                
                if (revenueCtx && revenueContainer && typeof Chart !== 'undefined') {
                    if (window.revenueChartInstance) {
                        window.revenueChartInstance.destroy();
                    }
                    
                    const revenueLabels = JSON.parse(revenueContainer.getAttribute('data-revenue-labels'));
                    const revenueData = JSON.parse(revenueContainer.getAttribute('data-revenue-data'));
                    
                    window.revenueChartInstance = new Chart(revenueCtx, {
                        type: 'line',
                        data: {
                            labels: revenueLabels,
                            datasets: [{
                                label: 'Revenue (₱)',
                                data: revenueData,
                                borderColor: 'rgb(59, 130, 246)',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
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
