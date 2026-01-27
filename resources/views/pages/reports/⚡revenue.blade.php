<?php

use Livewire\Component;
use Mary\Traits\Toast;
use App\Traits\AuthorizesRole;
use App\Models\RentPayment;
use App\Models\Apartment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new class extends Component {
    use Toast;
    use AuthorizesRole;

    public string $period = 'monthly'; // monthly, yearly
    public ?string $start_date = null;
    public ?string $end_date = null;
    public int $year = 0;
    public int $month = 0;
    public bool $drawer = false;

    // Check owner access on mount
    public function mount(): void
    {
        $this->authorizeRole('owner');
        $this->year = now()->year;
        $this->month = now()->month;
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->endOfMonth()->format('Y-m-d');
    }

    // Clear filters
    public function clear(): void
    {
        $this->period = 'monthly';
        $this->year = now()->year;
        $this->month = now()->month;
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->endOfMonth()->format('Y-m-d');
        $this->success('Filters cleared.', position: 'toast-bottom');
    }

    // Get revenue statistics
    public function getRevenueStats(): array
    {
        $query = RentPayment::query()
            ->whereHas('apartment', fn($q) => $q->where('owner_id', auth()->id()))
            ->where('status', 'paid')
            ->whereNotNull('payment_date');

        if ($this->period === 'monthly' && $this->start_date && $this->end_date) {
            $query->whereBetween('payment_date', [$this->start_date, $this->end_date]);
        } elseif ($this->period === 'yearly' && $this->year) {
            $query->whereYear('payment_date', $this->year);
        }

        $totalRevenue = $query->sum('amount');
        $paymentCount = $query->count();
        $averagePayment = $paymentCount > 0 ? $totalRevenue / $paymentCount : 0;

        // Pending payments
        $pendingQuery = RentPayment::query()
            ->whereHas('apartment', fn($q) => $q->where('owner_id', auth()->id()))
            ->where('status', 'pending');

        if ($this->period === 'monthly' && $this->start_date && $this->end_date) {
            $pendingQuery->whereBetween('due_date', [$this->start_date, $this->end_date]);
        } elseif ($this->period === 'yearly' && $this->year) {
            $pendingQuery->whereYear('due_date', $this->year);
        }

        $pendingAmount = $pendingQuery->sum('amount');
        $pendingCount = $pendingQuery->count();

        // Overdue payments
        $overdueQuery = RentPayment::query()
            ->whereHas('apartment', fn($q) => $q->where('owner_id', auth()->id()))
            ->where('status', 'overdue');

        if ($this->period === 'monthly' && $this->start_date && $this->end_date) {
            $overdueQuery->whereBetween('due_date', [$this->start_date, $this->end_date]);
        } elseif ($this->period === 'yearly' && $this->year) {
            $overdueQuery->whereYear('due_date', $this->year);
        }

        $overdueAmount = $overdueQuery->sum('amount');
        $overdueCount = $overdueQuery->count();

        return [
            'total_revenue' => $totalRevenue,
            'payment_count' => $paymentCount,
            'average_payment' => $averagePayment,
            'pending_amount' => $pendingAmount,
            'pending_count' => $pendingCount,
            'overdue_amount' => $overdueAmount,
            'overdue_count' => $overdueCount,
        ];
    }

    // Get monthly revenue trend (last 12 months)
    public function getMonthlyTrend(): array
    {
        $months = [];
        $revenues = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            $revenue = RentPayment::query()
                ->whereHas('apartment', fn($q) => $q->where('owner_id', auth()->id()))
                ->where('status', 'paid')
                ->whereNotNull('payment_date')
                ->whereBetween('payment_date', [$monthStart, $monthEnd])
                ->sum('amount');

            $months[] = $date->format('M Y');
            $revenues[] = (float) $revenue;
        }

        return [
            'months' => $months,
            'revenues' => $revenues,
        ];
    }

    // Get revenue by apartment
    public function getRevenueByApartment(): array
    {
        $query = RentPayment::query()
            ->select('apartments.id', 'apartments.name', DB::raw('SUM(rent_payments.amount) as total_revenue'), DB::raw('COUNT(rent_payments.id) as payment_count'))
            ->join('apartments', 'rent_payments.apartment_id', '=', 'apartments.id')
            ->where('apartments.owner_id', auth()->id())
            ->where('rent_payments.status', 'paid');

        if ($this->period === 'monthly' && $this->start_date && $this->end_date) {
            $query->whereBetween('rent_payments.payment_date', [$this->start_date, $this->end_date]);
        } elseif ($this->period === 'yearly' && $this->year) {
            $query->whereYear('rent_payments.payment_date', $this->year);
        }

        return $query->groupBy('apartments.id', 'apartments.name')
            ->orderByDesc('total_revenue')
            ->get()
            ->toArray();
    }

    // Get payment method breakdown
    public function getPaymentMethodBreakdown(): array
    {
        $query = RentPayment::query()
            ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->whereHas('apartment', fn($q) => $q->where('owner_id', auth()->id()))
            ->where('status', 'paid');

        if ($this->period === 'monthly' && $this->start_date && $this->end_date) {
            $query->whereBetween('payment_date', [$this->start_date, $this->end_date]);
        } elseif ($this->period === 'yearly' && $this->year) {
            $query->whereYear('payment_date', $this->year);
        }

        return $query->groupBy('payment_method')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    public function with(): array
    {
        $stats = $this->getRevenueStats();
        $trend = $this->getMonthlyTrend();
        $byApartment = $this->getRevenueByApartment();
        $byMethod = $this->getPaymentMethodBreakdown();

        return [
            'stats' => $stats,
            'trend' => $trend,
            'byApartment' => $byApartment,
            'byMethod' => $byMethod,
            'years' => range(now()->year - 5, now()->year),
            'months' => [
                ['id' => 1, 'name' => 'January'],
                ['id' => 2, 'name' => 'February'],
                ['id' => 3, 'name' => 'March'],
                ['id' => 4, 'name' => 'April'],
                ['id' => 5, 'name' => 'May'],
                ['id' => 6, 'name' => 'June'],
                ['id' => 7, 'name' => 'July'],
                ['id' => 8, 'name' => 'August'],
                ['id' => 9, 'name' => 'September'],
                ['id' => 10, 'name' => 'October'],
                ['id' => 11, 'name' => 'November'],
                ['id' => 12, 'name' => 'December'],
            ],
        ];
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Revenue Report" separator progress-indicator>
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
                    <div class="text-sm opacity-80">Total Revenue</div>
                    <div class="text-3xl font-bold">₱{{ number_format($stats['total_revenue'], 2) }}</div>
                    <div class="text-sm opacity-70 mt-1">{{ $stats['payment_count'] }} payments</div>
                </div>
                <x-icon name="o-banknotes" class="w-12 h-12 opacity-50" />
            </div>
        </x-card>

        <x-card class="bg-info text-info-content">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm opacity-80">Average Payment</div>
                    <div class="text-3xl font-bold">₱{{ number_format($stats['average_payment'], 2) }}</div>
                    <div class="text-sm opacity-70 mt-1">Per transaction</div>
                </div>
                <x-icon name="o-calculator" class="w-12 h-12 opacity-50" />
            </div>
        </x-card>

        <x-card class="bg-warning text-warning-content">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm opacity-80">Pending Payments</div>
                    <div class="text-3xl font-bold">₱{{ number_format($stats['pending_amount'], 2) }}</div>
                    <div class="text-sm opacity-70 mt-1">{{ $stats['pending_count'] }} pending</div>
                </div>
                <x-icon name="o-clock" class="w-12 h-12 opacity-50" />
            </div>
        </x-card>

        <x-card class="bg-error text-error-content">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm opacity-80">Overdue Payments</div>
                    <div class="text-3xl font-bold">₱{{ number_format($stats['overdue_amount'], 2) }}</div>
                    <div class="text-sm opacity-70 mt-1">{{ $stats['overdue_count'] }} overdue</div>
                </div>
                <x-icon name="o-exclamation-triangle" class="w-12 h-12 opacity-50" />
            </div>
        </x-card>
    </div>

    <!-- CHARTS SECTION -->
    <div class="mb-6">
        <!-- Monthly Trend Chart -->
        <x-card title="Monthly Revenue Trend (Last 12 Months)" shadow>
            @if(isset($trend['revenues']) && count($trend['revenues']) > 0 && max($trend['revenues']) > 0)
                <div class="p-4" wire:ignore 
                     data-revenue-labels="{{ json_encode($trend['months']) }}"
                     data-revenue-data="{{ json_encode($trend['revenues']) }}"
                     style="min-height: 500px; position: relative;">
                    <canvas id="monthlyRevenueChart" style="min-height: 500px;"></canvas>
                </div>
                <div class="mt-4 text-center text-sm text-base-content/70 px-4 pb-4">
                    Total Revenue: ₱{{ number_format(array_sum($trend['revenues']), 2) }}
                </div>
            @else
                <div class="h-64 flex items-center justify-center text-base-content/50">
                    <div class="text-center">
                        <x-icon name="o-chart-bar" class="w-16 h-16 mx-auto mb-2 opacity-30" />
                        <p>No revenue data available for the selected period</p>
                    </div>
                </div>
            @endif
        </x-card>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Payment Method Breakdown -->
        <x-card title="Payment Method Breakdown" shadow>
            @if(count($byMethod) > 0)
                <div class="space-y-4">
                    @foreach($byMethod as $method)
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="font-semibold">{{ $method['payment_method'] ?? 'Unknown' }}</span>
                                <span class="text-sm">₱{{ number_format($method['total'], 2) }}</span>
                            </div>
                            <div class="w-full bg-base-300 rounded-full h-2">
                                @php
                                    $total = array_sum(array_column($byMethod, 'total'));
                                    $percentage = $total > 0 ? ($method['total'] / $total) * 100 : 0;
                                @endphp
                                <div class="bg-primary h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                            <div class="text-xs text-base-content/70 mt-1">{{ $method['count'] }} payments ({{ number_format($percentage, 1) }}%)</div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center text-base-content/50 py-8">No payment data available</div>
            @endif
        </x-card>

        <!-- REVENUE BY APARTMENT TABLE -->
        <x-card title="Revenue by Apartment" shadow>
            @if(count($byApartment) > 0)
                <x-table 
                    :headers="[
                        ['key' => 'name', 'label' => 'Apartment'],
                        ['key' => 'payment_count', 'label' => 'Payments'],
                        ['key' => 'total_revenue', 'label' => 'Total Revenue'],
                    ]"
                    :rows="$byApartment"
                >
                    @scope('cell_total_revenue', $row)
                        <div class="font-semibold">₱{{ number_format($row['total_revenue'], 2) }}</div>
                    @endscope
    
                    @scope('cell_payment_count', $row)
                        <div class="badge badge-ghost">{{ $row['payment_count'] }}</div>
                    @endscope
                </x-table>
            @else
                <div class="text-center text-base-content/50 py-8">No revenue data available</div>
            @endif
        </x-card>
    </div>


    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <div class="grid gap-5">
            <x-select 
                label="Period" 
                wire:model.live="period" 
                :options="[
                    ['id' => 'monthly', 'name' => 'Monthly'],
                    ['id' => 'yearly', 'name' => 'Yearly'],
                ]" 
                icon="o-calendar" 
            />
            
            @if($period === 'monthly')
                <div wire:key="monthly-filters">
                    <x-input type="date" label="Start Date" wire:model.live="start_date" icon="o-calendar" />
                    <x-input type="date" label="End Date" wire:model.live="end_date" icon="o-calendar" />
                </div>
            @endif
            
            @if($period === 'yearly')
                <div wire:key="yearly-filters">
                    <x-select 
                        label="Year" 
                        wire:model.live="year" 
                        :options="collect($years)->map(fn($y) => ['id' => $y, 'name' => $y])->toArray()" 
                        icon="o-calendar" 
                    />
                </div>
            @endif
        </div>
        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>

    <!-- Chart.js Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
    <script defer>
        (function() {
            function initializeChart() {
                const revenueContainer = document.querySelector('[data-revenue-labels]');
                const revenueCtx = document.getElementById('monthlyRevenueChart');
                
                if (revenueCtx && revenueContainer && typeof Chart !== 'undefined') {
                    // Destroy existing chart if it exists
                    if (window.monthlyRevenueChartInstance) {
                        window.monthlyRevenueChartInstance.destroy();
                    }
                    
                    const revenueLabels = JSON.parse(revenueContainer.getAttribute('data-revenue-labels'));
                    const revenueData = JSON.parse(revenueContainer.getAttribute('data-revenue-data'));
                    
                    window.monthlyRevenueChartInstance = new Chart(revenueCtx, {
                        type: 'bar',
                        data: {
                            labels: revenueLabels,
                            datasets: [{
                                label: 'Revenue (₱)',
                                data: revenueData,
                                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                                borderColor: 'rgb(59, 130, 246)',
                                borderWidth: 1,
                                borderRadius: 4,
                                borderSkipped: false,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false,
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
                                    },
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.1)',
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
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
                        document.addEventListener('DOMContentLoaded', initializeChart);
                    } else {
                        initializeChart();
                    }
                } else {
                    setTimeout(waitForChartAndInit, 50);
                }
            }

            waitForChartAndInit();

            // Re-initialize chart on Livewire updates
            document.addEventListener('livewire:init', () => {
                Livewire.hook('morph.updated', ({ el, component }) => {
                    if (el.querySelector('#monthlyRevenueChart')) {
                        setTimeout(initializeChart, 100);
                    }
                });
            });
        })();
    </script>
</div>
