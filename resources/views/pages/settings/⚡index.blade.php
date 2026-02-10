<?php

use Livewire\Component;
use Mary\Traits\Toast;
use App\Models\OwnerSetting;
use Livewire\Attributes\Rule;
use App\Traits\AuthorizesRole;

new class extends Component
{
    use Toast;
    use AuthorizesRole;

    #[Rule('required|max:255')]
    public ?string $business_name = null;

    #[Rule('required|max:255')]
    public ?string $contact_person = null;

    #[Rule('required|max:50')]
    public ?string $mobile_number = null;

    #[Rule('nullable|max:50')]
    public ?string $office_tel = null;

    #[Rule('required|email|max:255')]
    public ?string $contact_email = null;

    #[Rule('nullable|max:50')]
    public ?string $whatsapp = null;

    #[Rule('nullable|max:255')]
    public ?string $instagram = null;

    #[Rule('nullable|max:255')]
    public ?string $facebook = null;

    #[Rule('nullable|url|max:255')]
    public ?string $website = null;

    #[Rule('required|in:PHP,USD,SGD,JPY,EUR,GBP,AUD,CAD,HKD,AED')]
    public string $currency = 'PHP';

    public function mount(): void
    {
        $this->authorizeRole('owner');

        $setting = auth()->user()->ownerSetting;
        if ($setting) {
            $this->business_name = $setting->business_name;
            $this->contact_person = $setting->contact_person;
            $this->mobile_number = $setting->mobile_number;
            $this->office_tel = $setting->office_tel;
            $this->contact_email = $setting->contact_email;
            $this->whatsapp = $setting->whatsapp;
            $this->instagram = $setting->instagram;
            $this->facebook = $setting->facebook;
            $this->website = $setting->website;
            $this->currency = $setting->currency ?? 'PHP';
        }
    }

    public function save(): void
    {
        $data = $this->validate();

        auth()->user()->ownerSetting()->updateOrCreate(
            ['user_id' => auth()->id()],
            $data
        );

        // Sync currency to all apartments owned by this user
        auth()->user()->apartments()->update(['currency' => $data['currency']]);

        $this->success('Settings saved. This information will appear in the apartment marketplace for interested tenants.');
    }

    public function getCurrencyOptionsProperty(): array
    {
        $options = OwnerSetting::currencyOptions();
        return collect($options)->map(fn ($label, $id) => ['id' => $id, 'name' => $label])->values()->all();
    }
};
?>

<div>
    <x-header title="Settings" subtitle="Rental business information for the marketplace" separator />

    <div class="max-w-4xl">
        <x-card shadow class="bg-base-100 border border-base-content/10">
            <p class="text-sm text-base-content/70 mb-6">
                This information will be shown to interested tenants when they view your apartments on the marketplace. 
                The currency you choose here will be used to display rent amounts for all your listings.
            </p>
            <x-form wire:submit="save">
                <x-input label="Rental Business Name" wire:model="business_name" placeholder="e.g. ABC Rentals" />
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input label="Contact Person" wire:model="contact_person" placeholder="Full name or role" />
                    <x-input label="Mobile Contact Number" wire:model="mobile_number" placeholder="e.g. +63 912 345 6789" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input label="Office Tel Number" wire:model="office_tel" placeholder="Landline or office number" />
                    <x-input label="Contact Email" wire:model="contact_email" type="email" placeholder="contact@example.com" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-select
                        label="Currency (Default for all your apartments)"
                        wire:model="currency"
                        :options="$this->currencyOptions"
                        hint="Rent for all your apartments will be shown in this currency on the marketplace. You can change the currency for each apartment individually."
                    />
                </div>
                {{-- add a separator --}}
                <div class="divider mt-6 font-semibold text-base-content/70">Social Media Accounts</div>
                <div class="grid grid-cols-1 gap-4">
                    <x-input label="WhatsApp" wire:model="whatsapp" placeholder="Number or link" />
                    <x-input label="Instagram Account" wire:model="instagram" placeholder="Username or URL" />
                    <x-input label="Facebook Account" wire:model="facebook" placeholder="Page name or URL" />
                    <x-input label="Website (optional)" wire:model="website" placeholder="https://..." />
                </div>

                <x-slot:actions>
                    <x-button label="Save Settings" icon="o-check" spinner="save" type="submit" class="rounded-full py-6 px-6 text-base bg-teal-600 dark:bg-teal-200 text-white dark:text-teal-900 hover:bg-teal-700 dark:hover:bg-teal-300 border-0" />
                </x-slot:actions>
            </x-form>
        </x-card>
    </div>
</div>
