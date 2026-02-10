<?php

use Livewire\Component;
use App\Traits\AuthorizesRole;
use App\Models\Tenant;
use App\Models\RentPayment;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;
use Illuminate\Support\Facades\Hash;

new class extends Component
{
    use AuthorizesRole;
    use Toast;

    public function render()
    {
        return view('pages.portal.⚡profile')->layout('layouts.portal');
    }

    #[Rule('required|min:2|max:255')]
    public string $name = '';

    #[Rule('required|email')]
    public string $email = '';

    public string $currentPassword = '';
    #[Rule('nullable|min:8')]
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';

    public function mount(): void
    {
        $this->authorizeRole('tenant');
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function getTenantIdsProperty()
    {
        return Tenant::where('user_id', auth()->id())->pluck('id');
    }

    public function getPaymentsProperty()
    {
        $ids = $this->tenantIds;
        if ($ids->isEmpty()) {
            return collect();
        }
        return RentPayment::whereIn('tenant_id', $ids)
            ->with(['apartment', 'tenant'])
            ->orderByDesc('due_date')
            ->limit(100)
            ->get();
    }

    public function saveProfile(): void
    {
        $user = auth()->user();
        $this->validate([
            'name' => 'required|min:2|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);
        $user->update(['name' => $this->name, 'email' => $this->email]);
        $this->success('Profile updated.', position: 'toast-bottom');
    }

    public function changePassword(): void
    {
        $this->validate([
            'currentPassword' => 'required',
            'newPassword' => 'nullable|min:8|same:newPasswordConfirmation',
        ]);
        $user = auth()->user();
        if (!Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', 'Current password is incorrect.');
            return;
        }
        if (strlen($this->newPassword) < 8) {
            $this->addError('newPassword', 'New password must be at least 8 characters.');
            return;
        }
        $user->update(['password' => Hash::make($this->newPassword)]);
        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->success('Password updated.', position: 'toast-bottom');
    }

}; ?>

<div>
    {{-- <x-header title="Profile" separator class="mb-4" /> --}}

    <h3 class="text-xl font-bold mb-8 mt-4 px-4">Profile</h3>

    <div class="space-y-6">
        {{-- Update profile --}}
        <x-card class="bg-base-100 border border-base-content/10">
            <x-header title="Update profile" subtitle="Name and email" size="text-base" class="mb-4" />
            <x-form wire:submit="saveProfile" class="space-y-3">
                <x-input label="Name" wire:model="name" />
                <x-input label="Email" wire:model="email" type="email" />
                <x-button label="Save Profile" type="submit" spinner="saveProfile" class="mx-auto btn-md w-1/2 text-white rounded-full bg-teal-500" />
            </x-form>
        </x-card>

        {{-- Change password --}}
        <x-card class="bg-base-100 border border-base-content/10">
            <x-header title="Change password" size="text-base" class="mb-4" />
            <x-form wire:submit="changePassword" class="space-y-3">
                <x-input label="Current password" wire:model="currentPassword" type="password" />
                <x-input label="New password" wire:model="newPassword" type="password" hint="Min 8 characters" />
                <x-input label="Confirm new password" wire:model="newPasswordConfirmation" type="password" />
                <x-button label="Update password" type="submit" spinner="changePassword" class="mx-auto btn-md w-1/2 text-white rounded-full bg-teal-500" />
            </x-form>
        </x-card>

        {{-- My Payment History --}}
        <x-card class="bg-base-100 border border-base-content/10">
            <x-header title="My Payment History" size="text-base" class="mb-4" />
            @if($this->payments->isEmpty())
                <p class="text-base-content/70 text-sm">No payment records yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Due date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Apartment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->payments as $payment)
                                <tr>
                                    <td>{{ $payment->due_date->format('M j, Y') }}</td>
                                    <td>{{ currency_symbol($payment->apartment->currency ?? 'PHP') }}{{ number_format($payment->amount, 2) }}</td>
                                    <td>
                                        <span class="badge badge-sm {{ $payment->status === 'paid' ? 'badge-success' : ($payment->status === 'overdue' ? 'badge-error' : 'badge-warning') }}">
                                            {{ $payment->status }}
                                        </span>
                                    </td>
                                    <td>{{ $payment->apartment->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</div>
