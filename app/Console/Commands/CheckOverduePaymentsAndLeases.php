<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckOverduePaymentsAndLeases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for overdue payments and lease expirations and create notifications';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService): int
    {
        if (!$this->option('quiet')) {
            $this->info('Checking for overdue payments and lease expirations...');
        }

        $results = $notificationService->runAllChecks();

        if (!$this->option('quiet')) {
            $this->info("Created {$results['overdue_payments']} overdue payment notification(s).");
            $this->info("Created {$results['lease_expirations']} lease expiration notification(s).");
            $this->info('Notification check completed.');
        }

        return Command::SUCCESS;
    }
}
