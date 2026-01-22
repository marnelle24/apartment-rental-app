<?php

namespace App\Services;

use App\Models\User;
use App\Models\RentPayment;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OwnerMetricsService
{
    /**
     * Calculate monthly revenue for an owner
     */
    public function getMonthlyRevenue(User $owner, ?Carbon $month = null): float
    {
        $month = $month ?? Carbon::now();
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        return (float) RentPayment::whereHas('tenant', function($query) use ($owner) {
                $query->where('owner_id', $owner->id);
            })
            ->where('status', 'paid')
            ->whereNotNull('payment_date')
            ->whereBetween('payment_date', [$monthStart, $monthEnd])
            ->sum('amount');
    }

    /**
     * Calculate yearly revenue for an owner
     */
    public function getYearlyRevenue(User $owner, ?Carbon $year = null): float
    {
        $year = $year ?? Carbon::now();
        $yearStart = $year->copy()->startOfYear();
        $yearEnd = $year->copy()->endOfYear();

        return (float) RentPayment::whereHas('tenant', function($query) use ($owner) {
                $query->where('owner_id', $owner->id);
            })
            ->where('status', 'paid')
            ->whereNotNull('payment_date')
            ->whereBetween('payment_date', [$yearStart, $yearEnd])
            ->sum('amount');
    }

    /**
     * Get revenue trend data for the last N months
     */
    public function getRevenueTrend(User $owner, int $months = 12): array
    {
        $labels = [];
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->format('M Y');
            $data[] = $this->getMonthlyRevenue($owner, $month);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Calculate occupancy rate for an owner
     */
    public function getOccupancyRate(User $owner): float
    {
        $totalApartments = $owner->apartments()->count();
        
        if ($totalApartments === 0) {
            return 0.0;
        }

        $occupiedApartments = $owner->apartments()->where('status', 'occupied')->count();
        
        return round(($occupiedApartments / $totalApartments) * 100, 1);
    }

    /**
     * Calculate payment collection rate for an owner
     */
    public function getCollectionRate(User $owner): float
    {
        $totalPayments = RentPayment::whereHas('tenant', function($query) use ($owner) {
                $query->where('owner_id', $owner->id);
            })->count();

        if ($totalPayments === 0) {
            return 0.0;
        }

        $paidPayments = RentPayment::whereHas('tenant', function($query) use ($owner) {
                $query->where('owner_id', $owner->id);
            })
            ->where('status', 'paid')
            ->count();

        return round(($paidPayments / $totalPayments) * 100, 1);
    }

    /**
     * Get payment amounts by status
     */
    public function getPaymentAmounts(User $owner): array
    {
        $baseQuery = RentPayment::whereHas('tenant', function($query) use ($owner) {
            $query->where('owner_id', $owner->id);
        });

        return [
            'pending' => (float) (clone $baseQuery)->where('status', 'pending')->sum('amount'),
            'overdue' => (float) (clone $baseQuery)->where('status', 'overdue')->sum('amount'),
            'paid' => (float) (clone $baseQuery)->where('status', 'paid')->sum('amount'),
        ];
    }

    /**
     * Get payment counts by status
     */
    public function getPaymentCounts(User $owner): array
    {
        $baseQuery = RentPayment::whereHas('tenant', function($query) use ($owner) {
            $query->where('owner_id', $owner->id);
        });

        return [
            'total' => (clone $baseQuery)->count(),
            'paid' => (clone $baseQuery)->where('status', 'paid')->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'overdue' => (clone $baseQuery)->where('status', 'overdue')->count(),
        ];
    }

    /**
     * Calculate last activity timestamp for an owner
     */
    public function getLastActivity(User $owner): ?Carbon
    {
        $activities = collect([
            $owner->apartments()->max('updated_at'),
            $owner->tenants()->max('updated_at'),
            RentPayment::whereHas('tenant', function($query) use ($owner) {
                $query->where('owner_id', $owner->id);
            })->max('updated_at'),
            Task::where('owner_id', $owner->id)->max('updated_at'),
            $owner->updated_at,
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
     * Get comprehensive metrics for an owner
     */
    public function getOwnerMetrics(User $owner): array
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $currentYear = Carbon::now()->startOfYear();
        $thirtyDaysFromNow = Carbon::now()->addDays(30);

        // Apartment metrics
        $totalApartments = $owner->apartments()->count();
        $occupiedApartments = $owner->apartments()->where('status', 'occupied')->count();
        $availableApartments = $owner->apartments()->where('status', 'available')->count();
        $maintenanceApartments = $owner->apartments()->where('status', 'maintenance')->count();
        $occupancyRate = $this->getOccupancyRate($owner);

        // Tenant metrics
        $activeTenants = $owner->tenants()->where('status', 'active')->count();
        $expiringLeases = $owner->tenants()
            ->where('status', 'active')
            ->whereNotNull('lease_end_date')
            ->whereBetween('lease_end_date', [Carbon::now(), $thirtyDaysFromNow])
            ->count();
        $newTenantsThisMonth = $owner->tenants()
            ->where('move_in_date', '>=', $currentMonth)
            ->count();

        // Revenue metrics
        $monthlyRevenue = $this->getMonthlyRevenue($owner);
        $yearlyRevenue = $this->getYearlyRevenue($owner);

        // Payment metrics
        $paymentCounts = $this->getPaymentCounts($owner);
        $paymentAmounts = $this->getPaymentAmounts($owner);
        $collectionRate = $this->getCollectionRate($owner);

        // Task metrics
        $tasksByStatus = Task::where('owner_id', $owner->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $overdueTasks = Task::where('owner_id', $owner->id)
            ->where('status', '!=', 'done')
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('due_date')
            ->where('due_date', '<', Carbon::now())
            ->count();

        $totalTasks = Task::where('owner_id', $owner->id)->count();
        $completedTasks = $tasksByStatus['done'] ?? 0;
        $taskCompletionRate = $totalTasks > 0 
            ? round(($completedTasks / $totalTasks) * 100, 1) 
            : 0.0;

        return [
            // Apartment metrics
            'total_apartments' => $totalApartments,
            'occupied_apartments' => $occupiedApartments,
            'available_apartments' => $availableApartments,
            'maintenance_apartments' => $maintenanceApartments,
            'occupancy_rate' => $occupancyRate,

            // Tenant metrics
            'active_tenants' => $activeTenants,
            'expiring_leases' => $expiringLeases,
            'new_tenants_this_month' => $newTenantsThisMonth,

            // Revenue metrics
            'monthly_revenue' => $monthlyRevenue,
            'yearly_revenue' => $yearlyRevenue,

            // Payment metrics
            'pending_amount' => $paymentAmounts['pending'],
            'overdue_amount' => $paymentAmounts['overdue'],
            'paid_amount' => $paymentAmounts['paid'],
            'total_payments' => $paymentCounts['total'],
            'paid_payments' => $paymentCounts['paid'],
            'pending_payments' => $paymentCounts['pending'],
            'overdue_payments' => $paymentCounts['overdue'],
            'collection_rate' => $collectionRate,

            // Task metrics
            'tasks_by_status' => $tasksByStatus,
            'overdue_tasks' => $overdueTasks,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'task_completion_rate' => $taskCompletionRate,

            // Activity
            'last_activity' => $this->getLastActivity($owner),
        ];
    }

    /**
     * Get metrics for multiple owners (optimized for list views)
     * Note: This method is kept for potential future optimization but currently
     * the index page uses getOwnerMetrics() for each owner individually.
     */
    public function getOwnersMetrics(Collection $owners): Collection
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();

        // Pre-load relationships to avoid N+1 queries
        $ownerIds = $owners->pluck('id');
        
        // Get all payments for these owners in one query
        $payments = DB::table('rent_payments')
            ->join('tenants', 'rent_payments.tenant_id', '=', 'tenants.id')
            ->whereIn('tenants.owner_id', $ownerIds)
            ->selectRaw('
                tenants.owner_id,
                COUNT(*) as total_payments,
                SUM(CASE WHEN rent_payments.status = "paid" THEN 1 ELSE 0 END) as paid_payments,
                SUM(CASE WHEN rent_payments.status = "paid" AND rent_payments.payment_date BETWEEN ? AND ? THEN rent_payments.amount ELSE 0 END) as monthly_revenue
            ')
            ->setBindings([$currentMonth->format('Y-m-d'), $currentMonthEnd->format('Y-m-d')])
            ->groupBy('tenants.owner_id')
            ->get()
            ->keyBy('owner_id');

        return $owners->map(function($owner) use ($payments) {
            $paymentData = $payments->get($owner->id);
            
            $totalApartments = $owner->apartments_count ?? $owner->apartments()->count();
            $occupiedApartments = $owner->occupied_apartments_count ?? $owner->apartments()->where('status', 'occupied')->count();
            
            $occupancyRate = $totalApartments > 0 
                ? round(($occupiedApartments / $totalApartments) * 100, 1) 
                : 0.0;

            $totalPayments = $paymentData->total_payments ?? 0;
            $paidPayments = $paymentData->paid_payments ?? 0;
            $collectionRate = $totalPayments > 0 
                ? round(($paidPayments / $totalPayments) * 100, 1) 
                : 0.0;

            $monthlyRevenue = (float) ($paymentData->monthly_revenue ?? 0);

            return [
                'id' => $owner->id,
                'name' => $owner->name,
                'email' => $owner->email,
                'apartments_count' => $totalApartments,
                'occupied_apartments' => $occupiedApartments,
                'available_apartments' => $owner->available_apartments_count ?? 0,
                'tenants_count' => $owner->active_tenants_count ?? 0,
                'monthly_revenue' => $monthlyRevenue,
                'occupancy_rate' => $occupancyRate,
                'collection_rate' => $collectionRate,
                'last_activity' => $this->getLastActivity($owner),
            ];
        });
    }
}
