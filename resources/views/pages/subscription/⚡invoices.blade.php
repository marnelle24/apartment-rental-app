<?php

use Livewire\Component;
use Mary\Traits\Toast;
use App\Traits\AuthorizesRole;

new class extends Component
{
    use Toast;
    use AuthorizesRole;

    public function mount(): void
    {
        $this->authorizeRole('owner');
    }

    public function with(): array
    {
        $user = auth()->user();
        $invoices = [];
        $upcomingInvoice = null;

        if ($user->hasStripeId()) {
            try {
                $invoices = $user->invoices();
                $upcomingInvoice = $user->subscription('default')?->active() ? $user->upcomingInvoice() : null;
            } catch (\Exception $e) {
                // Stripe API error — show empty state
            }
        }

        $currentPlan = $user->getEffectivePlan();

        return [
            'invoices' => $invoices,
            'upcomingInvoice' => $upcomingInvoice,
            'currentPlan' => $currentPlan,
            'hasStripeId' => $user->hasStripeId(),
        ];
    }
};
?>

<div>
    <x-header title="Invoice History" separator>
        <x-slot:subtitle>
            View and download your past invoices
        </x-slot:subtitle>
        <x-slot:actions>
            <x-button label="Back to Plans" icon="o-arrow-left" link="/subscription/pricing" class="btn-ghost" />
        </x-slot:actions>
    </x-header>

    {{-- Upcoming Invoice --}}
    @if($upcomingInvoice)
        <div class="mb-6">
            <x-card class="bg-base-100 border border-teal-500/20" shadow>
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-lg bg-teal-500/10">
                            <x-icon name="o-calendar" class="w-6 h-6 text-teal-500" />
                        </div>
                        <div>
                            <div class="font-semibold text-base-content">Upcoming Invoice</div>
                            <div class="text-sm text-base-content/60">
                                Next billing on {{ $upcomingInvoice->date()->format('M d, Y') }}
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xl font-bold text-base-content">{{ $upcomingInvoice->total() }}</div>
                        <div class="text-xs text-base-content/50">{{ strtoupper($upcomingInvoice->asStripeInvoice()->currency ?? 'usd') }}</div>
                    </div>
                </div>
            </x-card>
        </div>
    @endif

    {{-- Invoice List --}}
    @if($hasStripeId && count($invoices) > 0)
        <x-card class="border border-base-content/10" shadow>
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th class="text-right">Amount</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $invoice)
                            <tr class="hover">
                                <td class="whitespace-nowrap">
                                    {{ $invoice->date()->format('M d, Y') }}
                                </td>
                                <td>
                                    <div class="flex flex-col">
                                        @foreach($invoice->invoiceLineItems() as $item)
                                            <span class="text-sm">{{ $item->description }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="text-right font-semibold whitespace-nowrap">
                                    {{ $invoice->total() }}
                                </td>
                                <td class="text-center">
                                    @if($invoice->asStripeInvoice()->status === 'paid')
                                        <span class="badge badge-sm badge-success gap-1">
                                            <x-icon name="o-check" class="w-3 h-3" /> Paid
                                        </span>
                                    @elseif($invoice->asStripeInvoice()->status === 'open')
                                        <span class="badge badge-sm badge-warning gap-1">
                                            <x-icon name="o-clock" class="w-3 h-3" /> Open
                                        </span>
                                    @elseif($invoice->asStripeInvoice()->status === 'void')
                                        <span class="badge badge-sm badge-ghost gap-1">
                                            <x-icon name="o-x-mark" class="w-3 h-3" /> Void
                                        </span>
                                    @elseif($invoice->asStripeInvoice()->status === 'uncollectible')
                                        <span class="badge badge-sm badge-error gap-1">
                                            <x-icon name="o-x-mark" class="w-3 h-3" /> Uncollectible
                                        </span>
                                    @else
                                        <span class="badge badge-sm badge-ghost">{{ ucfirst($invoice->asStripeInvoice()->status ?? 'unknown') }}</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('subscription.invoice.download', $invoice->asStripeInvoice()->id) }}" target="_blank" class="btn btn-ghost btn-sm gap-1">
                                        <x-icon name="o-arrow-down-tray" class="w-4 h-4" />
                                        PDF
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @else
        {{-- Empty state --}}
        <x-card class="border border-base-content/10" shadow>
            <div class="text-center py-12">
                <x-icon name="o-document-text" class="w-16 h-16 mx-auto mb-4 text-base-content/20" />
                <h3 class="text-lg font-semibold text-base-content/70 mb-2">No invoices yet</h3>
                <p class="text-base-content/50 mb-6">Invoices will appear here once you subscribe to a paid plan.</p>
                <x-button label="View Plans" icon="o-rectangle-stack" link="/subscription/pricing" class="bg-teal-500 hover:bg-teal-600 text-white" />
            </div>
        </x-card>
    @endif
</div>
