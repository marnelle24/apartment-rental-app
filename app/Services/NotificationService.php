<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\RentPayment;
use App\Models\Task;
use App\Models\Tenant;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Create a notification for a user
     */
    public function createNotification(int $userId, string $type, string $title, string $message): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ]);
    }

    /**
     * Check if a notification of the same type already exists for today
     */
    public function notificationExistsToday(int $userId, string $type, string $identifier = null): bool
    {
        $query = Notification::where('user_id', $userId)
            ->where('type', $type)
            ->whereDate('created_at', Carbon::today());

        if ($identifier) {
            // Include identifier in message check to avoid duplicates for same entity
            $query->where('message', 'like', "%{$identifier}%");
        }

        return $query->exists();
    }

    /**
     * Check for overdue payments and create notifications
     */
    public function checkOverduePayments(): int
    {
        $count = 0;
        $today = Carbon::today();

        // Get all overdue payments with their relationships
        $overduePayments = RentPayment::overdue()
            ->with(['tenant.owner', 'apartment.owner'])
            ->get();

        foreach ($overduePayments as $payment) {
            // Get owner from tenant or apartment
            $owner = $payment->tenant?->owner ?? $payment->apartment?->owner;

            if (!$owner) {
                continue;
            }

            // Skip if tenant or apartment is missing
            if (!$payment->tenant || !$payment->apartment) {
                continue;
            }

            // Create unique identifier for this payment
            $identifier = "Payment #{$payment->id} - {$payment->tenant->name}";

            // Check if notification already exists today for this payment
            if (!$this->notificationExistsToday($owner->id, 'overdue_payment', $identifier)) {
                $daysOverdue = $today->diffInDays($payment->due_date);
                $unitNumber = $payment->apartment->unit_number ? "Unit: {$payment->apartment->unit_number}" : '';
                $title = "Overdue Payment: {$payment->tenant->name}";
                $message = "Payment of ₱" . number_format($payment->amount, 2) . 
                    " for {$payment->apartment->name}" . 
                    ($unitNumber ? " ({$unitNumber})" : '') . 
                    " was due on {$payment->due_date->format('M d, Y')}. " .
                    "It is now {$daysOverdue} day(s) overdue.";

                $this->createNotification(
                    $owner->id,
                    'overdue_payment',
                    $title,
                    $message
                );
                $count++;
            }
        }

        return $count;
    }

    /**
     * Check for lease expirations and create notifications
     */
    public function checkLeaseExpirations(): int
    {
        $count = 0;
        $today = Carbon::today();

        // Check for leases expiring in 30, 60, and 90 days
        $warningDays = [30, 60, 90];

        foreach ($warningDays as $days) {
            $expirationDate = $today->copy()->addDays($days);

            $tenants = Tenant::where('status', 'active')
                ->whereNotNull('lease_end_date')
                ->whereDate('lease_end_date', $expirationDate->format('Y-m-d'))
                ->with(['apartment', 'owner'])
                ->get();

            foreach ($tenants as $tenant) {
                if (!$tenant->owner || !$tenant->lease_end_date || !$tenant->apartment) {
                    continue;
                }

                // Create unique identifier for this lease expiration
                $identifier = "Lease #{$tenant->id} - {$tenant->name}";

                // Check if notification already exists today for this lease
                if (!$this->notificationExistsToday($tenant->owner->id, 'lease_expiration', $identifier)) {
                    $unitNumber = $tenant->apartment->unit_number ? "Unit: {$tenant->apartment->unit_number}" : '';
                    $title = "Lease Expiring in {$days} Days: {$tenant->name}";
                    $message = "The lease for {$tenant->name} at {$tenant->apartment->name}" .
                        ($unitNumber ? " ({$unitNumber})" : '') .
                        " will expire on {$tenant->lease_end_date->format('M d, Y')}. " .
                        "Please prepare for renewal or move-out procedures.";

                    $this->createNotification(
                        $tenant->owner->id,
                        'lease_expiration',
                        $title,
                        $message
                    );
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Run all notification checks
     */
    public function runAllChecks(): array
    {
        return [
            'overdue_payments' => $this->checkOverduePayments(),
            'lease_expirations' => $this->checkLeaseExpirations(),
        ];
    }

    /**
     * Create a notification when a new task is added (Kanban).
     */
    public function notifyTaskCreated(Task $task): void
    {
        $task->loadMissing('apartment');
        $ownerId = $task->apartment?->owner_id ?? $task->owner_id;
        if (!$ownerId) {
            return;
        }

        $apartmentName = $task->apartment?->name ?? 'Apartment';
        $title = "New task: {$task->title}";
        $message = "A new task \"{$task->title}\" was added to {$apartmentName}.";
        if ($task->due_date) {
            $message .= " Due: {$task->due_date->format('M d, Y')}.";
        }

        $this->createNotification($ownerId, 'task_created', $title, $message);
    }

    /**
     * Create a notification when a task is updated (e.g. status change in Kanban).
     */
    public function notifyTaskUpdated(Task $task, ?string $previousStatus = null): void
    {
        $task->loadMissing('apartment');
        $ownerId = $task->apartment?->owner_id ?? $task->owner_id;
        if (!$ownerId) {
            return;
        }

        $title = "Task updated: {$task->title}";
        $message = "Task \"{$task->title}\" was updated.";
        if ($previousStatus !== null) {
            $message .= " Status changed from " . $this->formatTaskStatus($previousStatus) . " to " . $this->formatTaskStatus($task->status) . ".";
        }

        $this->createNotification($ownerId, 'task_updated', $title, $message);
    }

    private function formatTaskStatus(string $status): string
    {
        return match ($status) {
            'todo' => 'To Do',
            'in_progress' => 'In Progress',
            'done' => 'Done',
            'cancelled' => 'Cancelled',
            default => $status,
        };
    }
}
