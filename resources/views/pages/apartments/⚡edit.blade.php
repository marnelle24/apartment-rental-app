<?php

use Livewire\Component;
use Mary\Traits\Toast;
use App\Models\Apartment;
use App\Models\Location;
use App\Models\OwnerSetting;
use Livewire\Attributes\Rule;
use App\Traits\AuthorizesRole;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use Toast;
    use AuthorizesRole;
    use WithFileUploads;
    
    public Apartment $apartment;

    // Form fields
    #[Rule('required|min:2|max:255')] 
    public string $name = '';

    #[Rule('required|exists:locations,id')]
    public ?int $location_id = null;

    #[Rule('required|min:5|max:500')]
    public string $address = '';

    #[Rule('nullable|max:50')]
    public ?string $unit_number = null;

    #[Rule('required|numeric|min:0')]
    public float $monthly_rent = 0;

    #[Rule('required|string|in:PHP,USD,SGD,JPY,EUR,GBP,AUD,CAD,HKD,AED')]
    public string $currency = 'PHP';

    #[Rule('nullable|integer|min:0')]
    public ?int $bedrooms = null;

    #[Rule('nullable|integer|min:0')]
    public ?int $bathrooms = null;

    #[Rule('nullable|numeric|min:0')]
    public ?float $square_meters = null;

    #[Rule('required|in:available,occupied,maintenance')]
    public string $status = 'available';

    #[Rule('nullable|max:2000')]
    public ?string $description = null;

    #[Rule('nullable|array')]
    public array $amenities = [];

    #[Rule('nullable|array|max:10')]
    public array $images = [];

    public array $uploadedImages = [];
    public array $existingImages = [];

    // Available amenities options
    public array $amenityOptions = [
        'wifi' => 'WiFi',
        'air_conditioning' => 'Air Conditioning',
        'parking' => 'Parking',
        'elevator' => 'Elevator',
        'security' => 'Security',
        'gym' => 'Gym',
        'pool' => 'Swimming Pool',
        'laundry' => 'Laundry',
        'balcony' => 'Balcony',
        'furnished' => 'Furnished',
    ];

    // Check owner access and populate form with apartment data
    public function mount(): void
    {
        $this->authorizeRole('owner');
        
        // Ensure owner can only edit their own apartments
        if ($this->apartment->owner_id !== auth()->id()) {
            abort(403, 'Unauthorized access');
        }

        $this->name = $this->apartment->name;
        $this->location_id = $this->apartment->location_id;
        $this->address = $this->apartment->address;
        $this->unit_number = $this->apartment->unit_number;
        $this->monthly_rent = $this->apartment->monthly_rent;
        $this->currency = $this->apartment->currency ?? auth()->user()->ownerSetting?->currency ?? 'PHP';
        $this->bedrooms = $this->apartment->bedrooms;
        $this->bathrooms = $this->apartment->bathrooms;
        $this->square_meters = $this->apartment->square_meters;
        $this->status = $this->apartment->status;
        $this->description = $this->apartment->description;
        
        // Ensure amenities is always an array and filter to only valid options
        $amenities = $this->apartment->amenities;
        if (is_string($amenities)) {
            $amenities = json_decode($amenities, true) ?? [];
        }
        $amenities = is_array($amenities) ? $amenities : [];
        // Filter to only include valid amenity keys (values in the array should match option IDs)
        // Also ensure all values are strings to match the option IDs
        $this->amenities = array_values(
            array_filter(
                array_map('strval', $amenities), 
                fn($value) => isset($this->amenityOptions[$value])
            )
        );
        
        $this->existingImages = $this->apartment->images ?? [];
    }

    // Currency options for dropdown (same as OwnerSetting)
    public function getCurrencyOptionsProperty(): array
    {
        return collect(OwnerSetting::currencyOptions())
            ->map(fn (string $label, string $code) => ['id' => $code, 'name' => $label])
            ->values()
            ->toArray();
    }

    // Get formatted amenity options for x-choices component
    public function getAmenityOptionsForChoices(): array
    {
        return collect($this->amenityOptions)
            ->map(fn($label, $key) => ['id' => $key, 'name' => $label])
            ->values()
            ->toArray();
    }

    // Load locations and plan for the form
    public function with(): array 
    {
        $plan = auth()->user()->getEffectivePlan();
        return [
            'locations' => Location::all(),
            'amenityChoices' => $this->getAmenityOptionsForChoices(),
            'plan' => $plan,
            'canUploadImages' => ! $plan || ! $plan->isFree(),
        ];
    }

    // Handle image uploads (no-op when Free plan)
    public function updatedUploadedImages(): void
    {
        $plan = auth()->user()->getEffectivePlan();
        if ($plan && $plan->isFree()) {
            $this->uploadedImages = [];
            $this->error('Image uploads are not available on the Free plan. Upgrade to add photos.', position: 'toast-bottom');
            return;
        }
        $this->validateOnly('uploadedImages', [
            'uploadedImages.*' => 'image|max:2048', // 2MB max per image
        ]);
    }

    // Remove existing image
    public function removeImage(int $index): void
    {
        unset($this->existingImages[$index]);
        $this->existingImages = array_values($this->existingImages);
    }

    // Save the updated apartment
    public function save(): void
    {
        $data = $this->validate();

        $disk = config('filesystems.apartment_images_disk', 'public');

        // Handle image uploads (new uploads disabled for Free plan)
        $imagePaths = $this->existingImages;
        $plan = auth()->user()->getEffectivePlan();
        if ((! $plan || ! $plan->isFree()) && !empty($this->uploadedImages)) {
            foreach ($this->uploadedImages as $image) {
                $path = $image->store('apartments', $disk);
                $imagePaths[] = $path;
            }
        }
        $data['images'] = !empty($imagePaths) ? $imagePaths : null;

        // Delete from storage any images that were removed
        $previousImages = $this->apartment->images ?? [];
        $removedPaths = array_diff($previousImages, $imagePaths);
        foreach ($removedPaths as $path) {
            Storage::disk($disk)->delete($path);
        }

        // Store amenities as array
        $data['amenities'] = !empty($this->amenities) ? $this->amenities : null;

        $this->apartment->update($data);

        $this->success('Apartment updated successfully.', redirectTo: '/apartments');
    }
};
?>

<div>
    <x-header title="Update {{ $apartment->name }}" separator />
    <div class="max-w-6xl">
        <x-card class="bg-base-100 border border-base-content/10" shadow>
            <x-form wire:submit="save"> 
                <div class="grid grid-cols-1 lg:grid-cols-6 gap-6">
                    <!-- Left side: Form inputs (75%) -->
                    <div class="lg:col-span-4 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-input label="Name" wire:model="name" hint="e.g., Angel's Apartment - Unit 101" />
                            <x-choices
                                label="Location"
                                wire:model="location_id"
                                :options="$locations->map(fn($l) => ['id' => $l->id, 'name' => $l->name])->values()->toArray()"
                                option-value="id"
                                option-label="name"
                                placeholder="Search or select location..."
                                searchable
                                single
                            />
                        </div>

                        <x-input label="Address" wire:model="address" hint="Full address of the apartment" />

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-input label="Unit Number" wire:model="unit_number" hint="e.g., Unit 101, Apt 2B" />
                            <x-select label="Status" wire:model="status" :options="[
                                ['id' => 'available', 'name' => 'Available'],
                                ['id' => 'occupied', 'name' => 'Occupied'],
                                ['id' => 'maintenance', 'name' => 'Maintenance'],
                            ]" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-input label="Monthly Rent" wire:model="monthly_rent" type="number" step="0.01" hint="Rent amount" />
                            <x-select
                                label="Currency"
                                wire:model="currency"
                                :options="$this->currencyOptions"
                                option-value="id"
                                option-label="name"
                            />
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <x-input label="Bedrooms" wire:model="bedrooms" type="number" />
                            <x-input label="Bathrooms" wire:model="bathrooms" type="number" />
                            <x-input label="Square Meters" wire:model="square_meters" type="number" step="0.01" hint="Area size" />
                        </div>

                        <x-textarea label="Description" wire:model="description" rows="4" hint="Additional details about the apartment" />

                        <x-choices 
                            label="Amenities" 
                            wire:model="amenities" 
                            :options="$amenityChoices" 
                            searchable 
                            hint="Select available amenities"
                        />
                    </div>

                    <!-- Right side: Photo upload (25%) -->
                    <div class="lg:col-span-2 rounded-lg bg-gray-50 dark:bg-gray-900/30 border border-gray-200 dark:border-gray-700 p-4">
                        <div class="form-control">
                            <label class="label mb-2">
                                <span class="label-text font-semibold">Images</span>
                            </label>
                            @if($canUploadImages)
                                <input 
                                    type="file" 
                                    wire:model="uploadedImages" 
                                    accept="image/*" 
                                    multiple 
                                    class="file-input file-input-bordered w-full"
                                />
                                <label class="label mt-1">
                                    <span class="label-text-alt text-xs">Upload up to 10 images (max 2MB each)</span>
                                </label>
                            @else
                                <div class="rounded-lg border border-base-300 bg-base-200/50 p-4 text-center">
                                    <x-icon name="o-photo" class="w-10 h-10 text-base-content/40 mx-auto mb-2" />
                                    <p class="text-sm text-base-content/70">Image uploads are available on paid plans.</p>
                                    <x-button label="Upgrade plan" link="/subscription/pricing" class="btn-sm mt-2 bg-teal-500 text-white" />
                                </div>
                            @endif
                            
                            @if(!empty($existingImages))
                                <div class="mt-4 space-y-2">
                                    <div class="text-sm font-semibold text-base-content/70">Existing Images:</div>
                                    <div class="grid grid-cols-1 gap-2">
                                        @foreach($existingImages as $index => $image)
                                            <div class="relative">
                                                <img src="{{ apartment_image_url($image) }}" alt="Existing image {{ $index + 1 }}" class="w-full h-32 object-cover rounded-lg border border-base-300">
                                                <button 
                                                    type="button"
                                                    wire:click="removeImage({{ $index }})"
                                                    class="absolute top-2 right-2 btn btn-circle btn-xs btn-error"
                                                    wire:confirm="Remove this image?"
                                                >
                                                    <x-icon name="o-x-mark" class="w-4 h-4" />
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                            @if(!empty($uploadedImages))
                                <div class="mt-4 space-y-2" wire:key="uploaded-images-container">
                                    <div class="text-sm font-semibold text-base-content/70">Uploaded Images:</div>
                                    <div class="grid grid-cols-1 gap-2">
                                        @foreach($uploadedImages as $index => $image)
                                            <div class="relative" wire:key="uploaded-image-{{ $index }}-{{ $image->getClientOriginalName() }}">
                                                @php
                                                    try {
                                                        $imageUrl = $image->temporaryUrl();
                                                    } catch (\Exception $e) {
                                                        $imageUrl = null;
                                                    }
                                                @endphp
                                                @if($imageUrl)
                                                    <img src="{{ $imageUrl }}" alt="Preview {{ $index + 1 }}" class="w-full h-32 object-cover rounded-lg border border-base-300">
                                                @else
                                                    <div class="w-full h-32 bg-base-200 rounded-lg border border-base-300 flex items-center justify-center">
                                                        <span class="text-sm text-base-content/70">{{ $image->getClientOriginalName() }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <x-slot:actions>
                    <x-button label="Cancel" link="/apartments" class="border border-gray-400 text-gray-500 dark:text-gray-400 dark:hover:bg-gray-200/30" />
                    <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="bg-teal-500 text-white" />
                </x-slot:actions>
            </x-form>
        </x-card>
    </div>
</div>
