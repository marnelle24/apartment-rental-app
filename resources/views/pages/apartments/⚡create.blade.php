<?php

use Livewire\Component;
use Mary\Traits\Toast;
use App\Models\Apartment;
use App\Models\Location;
use Livewire\Attributes\Rule;
use App\Traits\AuthorizesRole;
use Livewire\WithFileUploads;

new class extends Component
{
    use Toast;
    use AuthorizesRole;
    use WithFileUploads;

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

    // Check owner access on mount
    public function mount(): void
    {
        $this->authorizeRole('owner');
    }

    // Load locations for the form
    public function with(): array 
    {
        return [
            'locations' => Location::all(),
        ];
    }

    // Handle image uploads
    public function updatedUploadedImages(): void
    {
        $this->validate([
            'uploadedImages.*' => 'image|max:2048', // 2MB max per image
        ]);
    }

    // Save the new apartment
    public function save(): void
    {
        $data = $this->validate();
        
        // Set owner_id to current user
        $data['owner_id'] = auth()->id();

        // Handle image uploads
        $imagePaths = [];
        if (!empty($this->uploadedImages)) {
            foreach ($this->uploadedImages as $image) {
                $path = $image->store('apartments', config('filesystems.apartment_images_disk', 'public'));
                $imagePaths[] = $path;
            }
        }
        $data['images'] = !empty($imagePaths) ? $imagePaths : null;

        // Store amenities as array
        $data['amenities'] = !empty($this->amenities) ? $this->amenities : null;

        Apartment::create($data);

        $this->success('Apartment created successfully.', redirectTo: '/apartments');
    }
};
?>

<div>
    <x-header title="Create Apartment" separator />
    <div class="max-w-4xl">
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

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <x-input label="Monthly Rent" wire:model="monthly_rent" type="number" step="0.01" hint="Amount in PHP" />
                            <x-input label="Bedrooms" wire:model="bedrooms" type="number" />
                            <x-input label="Bathrooms" wire:model="bathrooms" type="number" />
                            <x-input label="Square Meters" wire:model="square_meters" type="number" step="0.01" hint="Area size" />
                        </div>


                        <x-textarea label="Description" wire:model="description" rows="4" hint="Additional details about the apartment" />

                        <x-choices 
                            label="Amenities" 
                            wire:model="amenities" 
                            :options="collect($amenityOptions)->map(fn($label, $key) => ['id' => $key, 'name' => $label])->values()->toArray()" 
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
                            
                            @if(!empty($uploadedImages))
                                <div class="mt-4 space-y-2">
                                    <div class="text-sm font-semibold text-base-content/70">Uploaded Images:</div>
                                    <div class="grid grid-cols-1 gap-2">
                                        @foreach($uploadedImages as $index => $image)
                                            <div class="relative">
                                                <img src="{{ $image->temporaryUrl() }}" alt="Preview {{ $index + 1 }}" class="w-full h-32 object-cover rounded-lg border border-base-300">
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <x-slot:actions>
                    <x-button label="Cancel" link="/apartments" />
                    <x-button label="Create" icon="o-plus" spinner="save" type="submit" class="btn-primary" />
                </x-slot:actions>
            </x-form>
        </x-card>
    </div>
</div>
