<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\RentPayment;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TenantMetricsService
{
    /**
     * Calculate total payments made by a tenant
     */
    public function getTotalPayments(Tenant $tenant): float
    {
        return (float) $tenant->rentPayments()
            ->where('status', 'paid')
            ->sum('amount');
    }

    /**
     * Calculate monthly payments for a tenant
     */
    public function getMonthlyPayments(Tenant $tenant, ?Carbon $month = null): float
    {
        $month = $month ?? Carbon::now();
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        return (float) $tenant->rentPayments()
            ->where('status', 'paid')
            ->whereNotNull('payment_date')
            ->whereBetween('payment_date', [$monthStart, $monthEnd])
            ->sum('amount');
    }

    /**
     * Calculate yearly payments for a tenant
     */
    public function getYearlyPayments(Tenant $tenant, ?Carbon $year = null): float
    {
        $year = $year ?? Carbon::now();
        $yearStart = $year->copy()->startOfYear();
        $yearEnd = $year->copy()->endOfYear();

        return (float) $tenant->rentPayments()
            ->where('status', 'paid')
            ->whereNotNull('payment_date')
            ->whereBetween('payment_date', [$yearStart, $yearEnd])
            ->sum('amount');
    }

    /**
     * Get payment trend data for the last N months
     */
    public function getPaymentTrend(Tenant $tenant, int $months = 12): array
    {
        $labels = [];
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->format('M Y');
            $data[] = $this->getMonthlyPayments($tenant, $month);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Calculate payment compliance rate for a tenant
     */
    public function getPaymentComplianceRate(Tenant $tenant): float
    {
        $totalPayments = $tenant->rentPayments()->count();

        if ($totalPayments === 0) {
            return 0.0;
        }

        $paidPayments = $tenant->rentPayments()
            ->where('status', 'paid')
            ->count();

        return round(($paidPayments / $totalPayments) * 100, 1);
    }

    /**
     * Get payment amounts by status
     */
    public function getPaymentAmounts(Tenant $tenant): array
    {
        return [
            'pending' => (float) $tenant->rentPayments()->where('status', 'pending')->sum('amount'),
            'overdue' => (float) $tenant->rentPayments()->where('status', 'overdue')->sum('amount'),
            'paid' => (float) $tenant->rentPayments()->where('status', 'paid')->sum('amount'),
        ];
    }

    /**
     * Get payment counts by status
     */
    public function getPaymentCounts(Tenant $tenant): array
    {
        return [
            'total' => $tenant->rentPayments()->count(),
            'paid' => $tenant->rentPayments()->where('status', 'paid')->count(),
            'pending' => $tenant->rentPayments()->where('status', 'pending')->count(),
            'overdue' => $tenant->rentPayments()->where('status', 'overdue')->count(),
        ];
    }

    /**
     * Calculate last activity timestamp for a tenant
     */
    public function getLastActivity(Tenant $tenant): ?Carbon
    {
        $activities = collect([
            $tenant->updated_at,
            $tenant->rentPayments()->max('updated_at'),
            $tenant->tasks()->max('updated_at'),
            $tenant->apartment?->updated_at,
        ])
        ->filter()
        ->map(function($date) {
            if ($date instanceof Carbon) {
                return $date;
            }
            return $date ? Carbon::parse($date) : null;
        })
        ->filter();

        return $activities->isNotEmpty() ? $activities->max() : null;
    }

    /**
     * Get comprehensive metrics for a tenant
     */
    public function getTenantMetrics(Tenant $tenant): array
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $currentYear = Carbon::now()->startOfYear();

        // Payment metrics
        $paymentCounts = $this->getPaymentCounts($tenant);
        $paymentAmounts = $this->getPaymentAmounts($tenant);
        $paymentComplianceRate = $this->getPaymentComplianceRate($tenant);
        $monthlyPayments = $this->getMonthlyPayments($tenant);
        $yearlyPayments = $this->getYearlyPayments($tenant);
        $totalPayments = $this->getTotalPayments($tenant);

        // Task metrics
        $tasksByStatus = Task::where('tenant_id', $tenant->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $overdueTasks = Task::where('tenant_id', $tenant->id)
            ->where('status', '!=', 'done')
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('due_date')
            ->where('due_date', '<', Carbon::now())
            ->count();

        $totalTasks = Task::where('tenant_id', $tenant->id)->count();
        $completedTasks = $tasksByStatus['done'] ?? 0;
        $taskCompletionRate = $totalTasks > 0 
            ? round(($completedTasks / $totalTasks) * 100, 1) 
            : 0.0;

        // Lease metrics
        $leaseDaysRemaining = null;
        $leaseStatus = 'active';
        if ($tenant->lease_end_date) {
            $now = Carbon::now();
            $leaseEnd = Carbon::parse($tenant->lease_end_date);
            $leaseDaysRemaining = $now->diffInDays($leaseEnd, false);
            
            if ($leaseDaysRemaining < 0) {
                $leaseStatus = 'expired';
            } elseif ($leaseDaysRemaining <= 30) {
                $leaseStatus = 'expiring_soon';
            }
        }

        // Tenure (days since move in)
        $tenureDays = $tenant->move_in_date 
            ? Carbon::now()->diffInDays(Carbon::parse($tenant->move_in_date))
            : null;

        return [
            // Payment metrics
            'total_payments' => $totalPayments,
            'monthly_payments' => $monthlyPayments,
            'yearly_payments' => $yearlyPayments,
            'pending_amount' => $paymentAmounts['pending'],
            'overdue_amount' => $paymentAmounts['overdue'],
            'paid_amount' => $paymentAmounts['paid'],
            'total_payment_records' => $paymentCounts['total'],
            'paid_payment_records' => $paymentCounts['paid'],
            'pending_payment_records' => $paymentCounts['pending'],
            'overdue_payment_records' => $paymentCounts['overdue'],
            'payment_compliance_rate' => $paymentComplianceRate,

            // Task metrics
            'tasks_by_status' => $tasksByStatus,
            'overdue_tasks' => $overdueTasks,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'task_completion_rate' => $taskCompletionRate,

            // Lease metrics
            'lease_days_remaining' => $leaseDaysRemaining,
            'lease_status' => $leaseStatus,
            'tenure_days' => $tenureDays,

            // Activity
            'last_activity' => $this->getLastActivity($tenant),
        ];
    }
}
