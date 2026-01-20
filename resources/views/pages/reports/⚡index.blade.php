<?php

use Livewire\Component;
use Mary\Traits\Toast;
use App\Traits\AuthorizesRole;

new class extends Component {
    use Toast;
    use AuthorizesRole;

    // Check owner access on mount
    public function mount(): void
    {
        $this->authorizeRole('owner');
    }

    public function with(): array
    {
        return [];
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Reports" separator progress-indicator>
        <x-slot:actions>
            <x-button label="Back" link="/" icon="o-arrow-left" class="btn-ghost" responsive />
        </x-slot:actions>
    </x-header>

    <!-- REPORT CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Revenue Report -->
        <x-card class="hover:shadow-lg transition-shadow cursor-pointer" link="/reports/revenue">
            <div class="flex items-center gap-4">
                <div class="avatar placeholder">
                    <div class="bg-primary text-primary-content rounded-full w-16">
                        <x-icon name="o-banknotes" class="w-8 h-8" />
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold">Revenue Report</h3>
                    <p class="text-sm text-base-content/70 mt-1">
                        View monthly revenue, payment history, and financial trends
                    </p>
                </div>
            </div>
        </x-card>

        <!-- Occupancy Report -->
        <x-card class="hover:shadow-lg transition-shadow cursor-pointer" link="/reports/occupancy">
            <div class="flex items-center gap-4">
                <div class="avatar placeholder">
                    <div class="bg-info text-info-content rounded-full w-16">
                        <x-icon name="o-building-office" class="w-8 h-8" />
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold">Occupancy Report</h3>
                    <p class="text-sm text-base-content/70 mt-1">
                        Track apartment occupancy rates and availability status
                    </p>
                </div>
            </div>
        </x-card>

        <!-- Tenant Turnover Report -->
        <x-card class="hover:shadow-lg transition-shadow cursor-pointer" link="/reports/tenant-turnover">
            <div class="flex items-center gap-4">
                <div class="avatar placeholder">
                    <div class="bg-success text-success-content rounded-full w-16">
                        <x-icon name="o-user-group" class="w-8 h-8" />
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold">Tenant Turnover</h3>
                    <p class="text-sm text-base-content/70 mt-1">
                        Analyze tenant retention, move-ins, and move-outs
                    </p>
                </div>
            </div>
        </x-card>
    </div>
</div>
