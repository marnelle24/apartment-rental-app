<?php

use Livewire\Component;
use App\Traits\AuthorizesRole;
use App\Models\Tenant;
use App\Models\Notification;
use App\Models\Task;
use App\Models\RentPayment;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;

new class extends Component
{
    use AuthorizesRole;
    use Toast;

    public function render()
    {
        return view('pages.portal.⚡notifications')->layout('layouts.portal');
    }

    public string $activeTab = 'notifications';

    // Request form
    #[Rule('required|min:3')]
    public string $requestTitle = '';
    #[Rule('nullable')]
    public ?string $requestDescription = null;
    #[Rule('required|in:payment_followup,electricity_bill,water_bill,inquiry,maintenance,contract_renewal,move_out,other')]
    public string $requestType = 'maintenance';
    #[Rule('nullable|date')]
    public ?string $requestDueDate = null;

    public bool $showRequestModal = false;

    public function mount(): void
    {
        $this->authorizeRole('tenant');
    }

    public function getTenantRecordsProperty()
    {
        return Tenant::where('user_id', auth()->id())->with('apartment')->get();
    }

    public function getCurrentTenantProperty(): ?Tenant
    {
        return $this->tenantRecords->where('status', 'active')->first() ?? $this->tenantRecords->first();
    }

    public function getNotificationsProperty()
    {
        return Notification::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }

    public function getRequestsProperty()
    {
        $tenantIds = $this->tenantRecords->pluck('id');
        if ($tenantIds->isEmpty()) {
            return collect();
        }
        return Task::whereIn('tenant_id', $tenantIds)
            ->with(['apartment', 'tenant'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }

    public function getPaymentsProperty()
    {
        $tenantIds = $this->tenantRecords->pluck('id');
        if ($tenantIds->isEmpty()) {
            return collect();
        }
        return RentPayment::whereIn('tenant_id', $tenantIds)
            ->with(['apartment', 'tenant'])
            ->orderByDesc('due_date')
            ->limit(100)
            ->get();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function markAsRead(Notification $notification): void
    {
        if ($notification->user_id !== auth()->id()) {
            $this->error('Unauthorized.', position: 'toast-bottom');
            return;
        }
        $notification->markAsRead();
        $this->success('Marked as read.', position: 'toast-bottom');
    }

    public function openRequestModal(): void
    {
        if (!$this->currentTenant) {
            $this->error('No current lease to submit a request for.', position: 'toast-bottom');
            return;
        }
        $this->reset(['requestTitle', 'requestDescription', 'requestType', 'requestDueDate']);
        $this->requestType = 'maintenance';
        $this->showRequestModal = true;
    }

    public function closeRequestModal(): void
    {
        $this->showRequestModal = false;
        $this->resetValidation();
    }

    public function submitRequest(): void
    {
        $current = $this->currentTenant;
        if (!$current) {
            $this->error('No current lease.', position: 'toast-bottom');
            return;
        }
        $data = $this->validate([
            'requestTitle' => 'required|min:3',
            'requestDescription' => 'nullable',
            'requestType' => 'required|in:payment_followup,electricity_bill,water_bill,inquiry,maintenance,contract_renewal,move_out,other',
            'requestDueDate' => 'nullable|date',
        ]);
        Task::create([
            'apartment_id' => $current->apartment_id,
            'tenant_id' => $current->id,
            'owner_id' => $current->owner_id,
            'title' => $data['requestTitle'],
            'description' => $data['requestDescription'],
            'type' => $data['requestType'],
            'status' => 'todo',
            'priority' => 'medium',
            'due_date' => $data['requestDueDate'] ? $data['requestDueDate'] : null,
        ]);
        $this->success('Request submitted. Your landlord will see it on the apartment board.', position: 'toast-bottom');
        $this->closeRequestModal();
    }

}; ?>

<div>
    {{-- <x-header title="Notifications" separator class="mb-4" /> --}}
    <h3 class="text-xl font-bold mb-8 mt-4 px-4">Notifications, Requests & Payments</h3>

    {{-- Tabs --}}
    <div class="tabs tabs-boxed bg-base-200/50 rounded-lg p-1 mb-4">
        <button
            type="button"
            wire:click="switchTab('notifications')"
            class="tab text-left flex-1 {{ $activeTab === 'notifications' ? 'tab-active text-teal-500 dark:text-teal-400 font-semibold border-b-2 border-teal-500' : 'text-gray-400 dark:text-gray-200 font-semibold border-b-2 dark:border-gray-200 border-gray-200' }}"
        >
            Notifications
        </button>
        <button
            type="button"
            wire:click="switchTab('requests')"
            class="tab text-left flex-1 {{ $activeTab === 'requests' ? 'tab-active text-teal-500 dark:text-teal-400 font-semibold border-b-2 border-teal-500' : 'text-gray-400 dark:text-gray-200 font-semibold border-b-2 dark:border-gray-200 border-gray-200' }}"
        >
            Requests
        </button>
        <button
            type="button"
            wire:click="switchTab('payment-history')"
            class="tab text-left flex-1 {{ $activeTab === 'payment-history' ? 'tab-active text-teal-500 dark:text-teal-400 font-semibold border-b-2 border-teal-500' : 'text-gray-400 dark:text-gray-200 font-semibold border-b-2 dark:border-gray-200 border-gray-200' }}"
        >
            Payments
        </button>
    </div>

    @if($activeTab === 'notifications')
        <div class="pt-4 space-y-3 px-4">
            @forelse($this->notifications as $notification)
                <x-card class="bg-base-100 border border-base-content/10 {{ $notification->isUnread() ? 'ring-1 ring-primary/20' : '' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ $notification->title }}</p>
                            <p class="text-sm text-base-content/70 mt-0.5">{{ $notification->message }}</p>
                            <p class="text-xs text-base-content/50 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                        @if($notification->isUnread())
                            <button type="button" wire:click="markAsRead({{ $notification->id }})" class="btn btn-ghost btn-xs shrink-0">
                                Mark read
                            </button>
                        @endif
                    </div>
                </x-card>
            @empty
                <x-card class="bg-base-100 border border-base-content/10">
                    <p class="text-base-content/70">No notifications yet.</p>
                </x-card>
            @endforelse
        </div>
    @elseif($activeTab === 'payment-history')
        {{-- Payment History tab --}}
        <div class="pt-4 space-y-3 px-4">
            @forelse($this->payments as $payment)
                <x-card class="bg-base-100 border border-base-content/10">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ currency_symbol($payment->apartment->currency ?? 'PHP') }}{{ number_format($payment->amount, 2) }}</p>
                            <p class="text-sm text-base-content/70 mt-0.5">Due {{ $payment->due_date->format('M j, Y') }}</p>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <span class="badge badge-sm {{ $payment->status === 'paid' ? 'badge-success' : ($payment->status === 'overdue' ? 'badge-error' : 'badge-warning') }}">{{ $payment->status }}</span>
                            </div>
                            <p class="text-xs text-base-content/50 mt-1">{{ $payment->apartment->name ?? '—' }}</p>
                        </div>
                    </div>
                </x-card>
            @empty
                <x-card class="bg-base-100 border border-base-content/10">
                    <p class="text-base-content/70">No payment history yet.</p>
                </x-card>
            @endforelse
        </div>
    @else
        {{-- Requests tab --}}
        @if($this->currentTenant)
            <div class="pt-4 mb-4 px-4">
                <x-button label="Submit a request" icon="o-plus" wire:click="openRequestModal" class="btn-sm text-white rounded-full bg-teal-500" />
            </div>
        @else
            <p class="pt-4 px-4 text-sm text-base-content/70 mb-4">Link your account to a lease to submit requests.</p>
        @endif

        <div class="pt-4 space-y-3 px-4">
            @forelse($this->requests as $task)
                <x-card class="bg-base-100 border border-base-content/10">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ $task->title }}</p>
                            @if($task->description)
                                <p class="text-sm text-base-content/70 mt-0.5">{{ Str::limit($task->description, 100) }}</p>
                            @endif
                            <div class="flex flex-wrap gap-2 mt-2">
                                <span class="badge badge-sm badge-ghost">{{ $task->type }}</span>
                                <span class="badge badge-sm {{ $task->status === 'done' ? 'badge-success' : ($task->status === 'cancelled' ? 'badge-ghost' : 'badge-warning') }}">{{ $task->status }}</span>
                            </div>
                            <p class="text-xs text-base-content/50 mt-1">{{ $task->created_at->diffForHumans() }} · {{ $task->apartment->name ?? '—' }}</p>
                        </div>
                    </div>
                </x-card>
            @empty
                <x-card class="bg-base-100 border border-base-content/10">
                    <p class="text-base-content/70">No requests yet.</p>
                </x-card>
            @endforelse
        </div>
    @endif

    {{-- Submit request modal --}}
    @if($showRequestModal)
        <x-modal name="request-modal" wire:model="showRequestModal" class="backdrop-blur">
            <x-form wire:submit="submitRequest" class="space-y-4">
                <x-header title="Submit a request" subtitle="Your request will be posted on the apartment board." separator />
                <x-input label="Title" wire:model="requestTitle" placeholder="e.g. Fix leaking faucet" />
                <x-textarea label="Description (optional)" wire:model="requestDescription" placeholder="Details..." rows="3" />
                <x-select
                    label="Type"
                    wire:model="requestType"
                    :options="[
                        ['id' => 'maintenance', 'name' => 'Maintenance'],
                        ['id' => 'inquiry', 'name' => 'Inquiry'],
                        // ['id' => 'electricity_bill', 'name' => 'Electricity bill'],
                        // ['id' => 'water_bill', 'name' => 'Water bill'],
                        // ['id' => 'payment_followup', 'name' => 'Payment follow-up'],
                        // ['id' => 'contract_renewal', 'name' => 'Contract renewal'],
                        // ['id' => 'move_out', 'name' => 'Move out'],
                        ['id' => 'other', 'name' => 'Other'],
                    ]"
                    option-value="id"
                    option-label="name"
                />
                <x-input label="Due date (optional)" wire:model="requestDueDate" type="date" />
                <x-slot:actions>
                    <x-button label="Cancel" wire:click="closeRequestModal" class="btn-lg text-white rounded-full bg-gray-500" />
                    <x-button label="Submit" type="submit" spinner="submitRequest" class="btn-lg text-white rounded-full bg-teal-500" />
                </x-slot:actions>
            </x-form>
        </x-modal>
    @endif
</div>
