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
        $this->bedrooms = $this->apartment->bedrooms;
        $this->bathrooms = $this->apartment->bathrooms;
        $this->square_meters = $this->apartment->square_meters;
        $this->status = $this->apartment->status;
        $this->description = $this->apartment->description;
        $this->amenities = $this->apartment->amenities ?? [];
        $this->existingImages = $this->apartment->images ?? [];
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

        // Handle image uploads
        $imagePaths = $this->existingImages;
        if (!empty($this->uploadedImages)) {
            foreach ($this->uploadedImages as $image) {
                $path = $image->store('apartments', 'public');
                $imagePaths[] = $path;
            }
        }
        $data['images'] = !empty($imagePaths) ? $imagePaths : null;

        // Store amenities as array
        $data['amenities'] = !empty($this->amenities) ? $this->amenities : null;

        $this->apartment->update($data);

        $this->success('Apartment updated successfully.', redirectTo: '/apartments');
    }
};
?>

<div>
    <x-header title="Update {{ $apartment->name }}" separator />

    <x-form wire:submit="save"> 
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-input label="Name" wire:model="name" hint="e.g., Studio Unit 101" />
            <x-select label="Location" wire:model="location_id" :options="$locations" placeholder="Select location" />
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

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-input label="Monthly Rent" wire:model="monthly_rent" type="number" step="0.01" hint="Amount in PHP" />
            <x-input label="Bedrooms" wire:model="bedrooms" type="number" />
            <x-input label="Bathrooms" wire:model="bathrooms" type="number" />
        </div>

        <x-input label="Square Meters" wire:model="square_meters" type="number" step="0.01" hint="Area size" />

        <x-textarea label="Description" wire:model="description" rows="4" hint="Additional details about the apartment" />

        <x-choices 
            label="Amenities" 
            wire:model="amenities" 
            :options="collect($amenityOptions)->map(fn($label, $key) => ['id' => $key, 'name' => $label])->values()->toArray()" 
            searchable 
            hint="Select available amenities"
        />

        @if(!empty($existingImages))
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold">Existing Images</span>
                </label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($existingImages as $index => $image)
                        <div class="relative">
                            <img src="{{ asset('storage/' . $image) }}" alt="Apartment image" class="w-full h-32 object-cover rounded-lg" />
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

        <div class="form-control">
            <label class="label">
                <span class="label-text font-semibold">Add More Images</span>
            </label>
            <input 
                type="file" 
                wire:model="uploadedImages" 
                accept="image/*" 
                multiple 
                class="file-input file-input-bordered w-full"
            />
            <label class="label">
                <span class="label-text-alt">Upload additional images (max 2MB each)</span>
            </label>
        </div>

        <x-slot:actions>
            <x-button label="Cancel" link="/apartments" />
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
