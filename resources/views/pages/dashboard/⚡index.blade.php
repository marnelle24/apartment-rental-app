<?php

use Livewire\Component;
use App\Traits\AuthorizesRole;
use App\Models\Apartment;
use App\Models\Tenant;
use App\Models\Task;
use App\Models\RentPayment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new class extends Component
{
    use AuthorizesRole;

    // Check owner access on mount
    public function mount(): void
    {
        $this->authorizeRole('owner');
    }

    // Quick Stats
    public function getTotalApartmentsProperty(): int
    {
        return Apartment::where('owner_id', auth()->id())->count();
    }

    public function getOccupiedApartmentsProperty(): int
    {
        return Apartment::where('owner_id', auth()->id())
            ->where('status', 'occupied')
            ->count();
    }

    public function getAvailableApartmentsProperty(): int
    {
        return Apartment::where('owner_id', auth()->id())
            ->where('status', 'available')
            ->count();
    }

    public function getMonthlyRevenueProperty(): float
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();
        
        return RentPayment::whereHas('tenant', function($query) {
                $query->where('owner_id', auth()->id());
            })
            ->where('status', 'paid')
            ->whereNotNull('payment_date')
            ->whereBetween('payment_date', [$currentMonth, $currentMonthEnd])
            ->sum('amount');
    }

    public function getPendingPaymentsProperty(): int
    {
        return RentPayment::whereHas('tenant', function($query) {
                $query->where('owner_id', auth()->id());
            })
            ->where('status', 'pending')
            ->count();
    }

    public function getOverduePaymentsProperty(): int
    {
        return RentPayment::whereHas('tenant', function($query) {
                $query->where('owner_id', auth()->id());
            })
            ->where('status', 'overdue')
            ->count();
    }

    public function getUpcomingLeaseExpirationsProperty(): int
    {
        $thirtyDaysFromNow = Carbon::now()->addDays(30);
        
        return Tenant::where('owner_id', auth()->id())
            ->where('status', 'active')
            ->whereNotNull('lease_end_date')
            ->whereBetween('lease_end_date', [Carbon::now(), $thirtyDaysFromNow])
            ->count();
    }

    // Revenue Chart Data (Last 12 months)
    public function getMonthlyRevenueDataProperty(): array
    {
        $months = [];
        $revenues = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $months[] = $month->format('M Y');
            
            $revenue = RentPayment::whereHas('tenant', function($query) {
                    $query->where('owner_id', auth()->id());
                })
                ->where('status', 'paid')
                ->whereNotNull('payment_date')
                ->whereYear('payment_date', $month->year)
                ->whereMonth('payment_date', $month->month)
                ->sum('amount');
            
            $revenues[] = (float) $revenue;
        }
        
        return [
            'labels' => $months,
            'data' => $revenues,
        ];
    }

    // Payment Status Breakdown
    public function getPaymentStatusDataProperty(): array
    {
        $paid = RentPayment::whereHas('tenant', function($query) {
                $query->where('owner_id', auth()->id());
            })
            ->where('status', 'paid')
            ->count();
        
        $pending = RentPayment::whereHas('tenant', function($query) {
                $query->where('owner_id', auth()->id());
            })
            ->where('status', 'pending')
            ->count();
        
        $overdue = RentPayment::whereHas('tenant', function($query) {
                $query->where('owner_id', auth()->id());
            })
            ->where('status', 'overdue')
            ->count();
        
        return [
            'labels' => ['Paid', 'Pending', 'Overdue'],
            'data' => [$paid, $pending, $overdue],
            'colors' => ['#10b981', '#f59e0b', '#ef4444'],
        ];
    }

    // Task Overview
    public function getTasksByStatusProperty(): array
    {
        return Task::where('owner_id', auth()->id())
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    public function getOverdueTasksCountProperty(): int
    {
        return Task::where('owner_id', auth()->id())
            ->where('status', '!=', 'done')
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('due_date')
            ->where('due_date', '<', Carbon::now())
            ->count();
    }

    public function getTasksDueThisWeekProperty(): int
    {
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        
        return Task::where('owner_id', auth()->id())
            ->where('status', '!=', 'done')
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$weekStart, $weekEnd])
            ->count();
    }

    // Tenant Overview
    public function getActiveTenantsCountProperty(): int
    {
        return Tenant::where('owner_id', auth()->id())
            ->where('status', 'active')
            ->count();
    }

    public function getLeaseExpiringSoonProperty(): int
    {
        return $this->upcomingLeaseExpirations;
    }

    public function getNewTenantsThisMonthProperty(): int
    {
        $currentMonth = Carbon::now()->startOfMonth();
        
        return Tenant::where('owner_id', auth()->id())
            ->where('move_in_date', '>=', $currentMonth)
            ->count();
    }

    // Alerts & Notifications
    public function getOverduePaymentsAlertsProperty(): array
    {
        return RentPayment::whereHas('tenant', function($query) {
                $query->where('owner_id', auth()->id());
            })
            ->where('status', 'overdue')
            ->with(['tenant', 'apartment'])
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    public function getLeaseExpiringAlertsProperty(): array
    {
        $thirtyDaysFromNow = Carbon::now()->addDays(30);
        
        return Tenant::where('owner_id', auth()->id())
            ->where('status', 'active')
            ->whereNotNull('lease_end_date')
            ->whereBetween('lease_end_date', [Carbon::now(), $thirtyDaysFromNow])
            ->with('apartment')
            ->orderBy('lease_end_date', 'asc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    public function getUnreadNotificationsProperty(): int
    {
        if (!auth()->check()) {
            return 0;
        }
        
        return \App\Models\Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->count();
    }

    public function with(): array
    {
        return [
            'totalApartments' => $this->totalApartments,
            'occupiedApartments' => $this->occupiedApartments,
            'availableApartments' => $this->availableApartments,
            'monthlyRevenue' => $this->monthlyRevenue,
            'pendingPayments' => $this->pendingPayments,
            'overduePayments' => $this->overduePayments,
            'upcomingLeaseExpirations' => $this->upcomingLeaseExpirations,
            'monthlyRevenueData' => $this->monthlyRevenueData,
            'paymentStatusData' => $this->paymentStatusData,
            'tasksByStatus' => $this->tasksByStatus,
            'overdueTasksCount' => $this->overdueTasksCount,
            'tasksDueThisWeek' => $this->tasksDueThisWeek,
            'activeTenantsCount' => $this->activeTenantsCount,
            'leaseExpiringSoon' => $this->leaseExpiringSoon,
            'newTenantsThisMonth' => $this->newTenantsThisMonth,
            'overduePaymentsAlerts' => $this->overduePaymentsAlerts,
            'leaseExpiringAlerts' => $this->leaseExpiringAlerts,
            'unreadNotifications' => $this->unreadNotifications,
        ];
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Dashboard" separator progress-indicator>
        <x-slot:actions>
            <div class="relative inline-block">
                <x-button label="View All Notifications" icon="o-bell" link="/notifications" class="border border-gray-300 relative bg-gray-200 dark:bg-gray-700 dark:border-gray-600 hover:bg-gray-300 dark:hover:bg-gray-600 dark:hover:border-gray-500 text-gray-800 dark:text-gray-300 hover:border-gray-300 cursor-pointer rounded-full py-1 px-4" responsive />
                @if($unreadNotifications > 0)
                    <span class="rounded-full bg-green-400 badge-sm text-xs absolute top-0 -left-4 text-green-800 flex items-center justify-center font-bold z-10 py-1 px-2">
                        {{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}
                    </span>
                @endif
            </div>
        </x-slot:actions>
    </x-header>

    <!-- QUICK STATS CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-4 mb-6">
        <!-- Total Apartments -->
        <x-card class="bg-base-100 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/60">Total Apartments</div>
                    <div class="text-3xl font-bold">{{ $totalApartments }}</div>
                </div>
                <x-icon name="o-building-office" class="w-10 h-10 text-primary opacity-50" />
            </div>
        </x-card>

        <!-- Occupied vs Available -->
        <x-card class="bg-base-100 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/60">Occupied</div>
                    <div class="text-3xl font-bold text-success">{{ $occupiedApartments }}</div>
                    <div class="text-xs text-base-content/60 mt-1">Available: {{ $availableApartments }}</div>
                </div>
                <x-icon name="o-check-circle" class="w-10 h-10 text-success opacity-50" />
            </div>
        </x-card>

        <!-- Monthly Revenue -->
        <x-card class="bg-base-100 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/60">Monthly Revenue</div>
                    <div class="text-2xl font-bold text-primary">₱{{ number_format($monthlyRevenue, 2) }}</div>
                </div>
                <x-icon name="o-banknotes" class="w-10 h-10 text-primary opacity-50" />
            </div>
        </x-card>

        <!-- Pending Payments -->
        <x-card class="bg-base-100 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/60">Pending Payments</div>
                    <div class="text-3xl font-bold text-warning">{{ $pendingPayments }}</div>
                </div>
                <x-icon name="o-clock" class="w-10 h-10 text-warning opacity-50" />
            </div>
        </x-card>

        <!-- Overdue Payments -->
        <x-card class="bg-base-100 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/60">Overdue</div>
                    <div class="text-3xl font-bold text-error">{{ $overduePayments }}</div>
                </div>
                <x-icon name="o-exclamation-triangle" class="w-10 h-10 text-error opacity-50" />
            </div>
        </x-card>

        <!-- Upcoming Lease Expirations -->
        <x-card class="bg-base-100 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-base-content/60">Leases Expiring</div>
                    <div class="text-3xl font-bold text-info">{{ $upcomingLeaseExpirations }}</div>
                    <div class="text-xs text-base-content/60 mt-1">Next 30 days</div>
                </div>
                <x-icon name="o-calendar" class="w-10 h-10 text-info opacity-50" />
            </div>
        </x-card>
    </div>

    <!-- CHARTS ROW -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Revenue Chart -->
        <x-card class="bg-base-100 shadow">
            <x-header title="Monthly Revenue Trend" subtitle="Last 12 months" separator />
            <div class="p-4" wire:ignore 
                 data-revenue-labels="{{ json_encode($monthlyRevenueData['labels']) }}"
                 data-revenue-data="{{ json_encode($monthlyRevenueData['data']) }}">
                <canvas id="revenueChart" height="300"></canvas>
            </div>
        </x-card>

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
    </div>

    <!-- TASK & TENANT OVERVIEW ROW -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Task Overview -->
        <x-card class="bg-base-100 shadow">
            <x-header title="Task Overview" separator />
            <div class="p-4 space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-info">{{ $tasksByStatus['todo'] ?? 0 }}</div>
                        <div class="text-sm text-base-content/60">To Do</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-warning">{{ $tasksByStatus['in_progress'] ?? 0 }}</div>
                        <div class="text-sm text-base-content/60">In Progress</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-success">{{ $tasksByStatus['done'] ?? 0 }}</div>
                        <div class="text-sm text-base-content/60">Done</div>
                    </div>
                </div>
                <div class="divider"></div>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-sm">Overdue Tasks</span>
                        <span class="badge badge-error">{{ $overdueTasksCount }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm">Due This Week</span>
                        <span class="badge badge-warning">{{ $tasksDueThisWeek }}</span>
                    </div>
                </div>
            </div>
        </x-card>

        <!-- Tenant Overview -->
        <x-card class="bg-base-100 shadow">
            <x-header title="Tenant Overview" separator />
            <div class="p-4 space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-primary">{{ $activeTenantsCount }}</div>
                        <div class="text-sm text-base-content/60">Active</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-warning">{{ $leaseExpiringSoon }}</div>
                        <div class="text-sm text-base-content/60">Expiring Soon</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-success">{{ $newTenantsThisMonth }}</div>
                        <div class="text-sm text-base-content/60">New This Month</div>
                    </div>
                </div>
            </div>
        </x-card>
    </div>

    <!-- ALERTS & NOTIFICATIONS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Overdue Payments Alerts -->
        <x-card class="bg-base-100 shadow">
            <x-header title="Overdue Payments" separator />
            <div class="p-4">
                @if(count($overduePaymentsAlerts) > 0)
                    <div class="space-y-3">
                        @foreach($overduePaymentsAlerts as $payment)
                            <div class="flex items-center justify-between p-3 bg-error/10 rounded-lg border border-error/20">
                                <div>
                                    <div class="font-semibold">{{ $payment['tenant']['name'] ?? 'N/A' }}</div>
                                    <div class="text-sm text-base-content/60">{{ $payment['apartment']['name'] ?? 'N/A' }}</div>
                                    <div class="text-xs text-base-content/50">Due: {{ \Carbon\Carbon::parse($payment['due_date'])->format('M d, Y') }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-error">₱{{ number_format($payment['amount'], 2) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-base-content/60">
                        <x-icon name="o-check-circle" class="w-12 h-12 mx-auto mb-2 text-success opacity-50" />
                        <p>No overdue payments</p>
                    </div>
                @endif
            </div>
        </x-card>

        <!-- Lease Expiring Alerts -->
        <x-card class="bg-base-100 shadow">
            <x-header title="Leases Expiring Soon" subtitle="Next 30 days" separator />
            <div class="p-4">
                @if(count($leaseExpiringAlerts) > 0)
                    <div class="space-y-3">
                        @foreach($leaseExpiringAlerts as $tenant)
                            <div class="flex items-center justify-between p-3 bg-warning/10 rounded-lg border border-warning/20">
                                <div>
                                    <div class="font-semibold">{{ $tenant['name'] }}</div>
                                    <div class="text-sm text-base-content/60">{{ $tenant['apartment']['name'] ?? 'N/A' }}</div>
                                    <div class="text-xs text-base-content/50">
                                        Expires: {{ \Carbon\Carbon::parse($tenant['lease_end_date'])->format('M d, Y') }}
                                        ({{ \Carbon\Carbon::parse($tenant['lease_end_date'])->diffForHumans() }})
                                    </div>
                                </div>
                                <x-icon name="o-calendar-days" class="w-6 h-6 text-warning" />
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-base-content/60">
                        <x-icon name="o-check-circle" class="w-12 h-12 mx-auto mb-2 text-success opacity-50" />
                        <p>No leases expiring soon</p>
                    </div>
                @endif
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
                                fill: true
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
