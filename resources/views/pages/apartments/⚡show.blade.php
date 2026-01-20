<?php

use Livewire\Component;
use App\Models\Apartment;
use App\Traits\AuthorizesRole;

new class extends Component
{
    use AuthorizesRole;
    
    public Apartment $apartment;

    // Check owner access on mount
    public function mount(): void
    {
        $this->authorizeRole('owner');
        
        // Ensure owner can only view their own apartments
        if ($this->apartment->owner_id !== auth()->id()) {
            abort(403, 'Unauthorized access');
        }
    }

    public function with(): array
    {
        return [
            'apartment' => $this->apartment->load(['location', 'tenants', 'tasks']),
        ];
    }
};
?>

<div>
    <x-header title="{{ $apartment->name }}" separator>
        <x-slot:actions>
            <x-button label="Edit" link="/apartments/{{ $apartment->id }}/edit" icon="o-pencil" class="btn-primary" />
            <x-button label="Back" link="/apartments" icon="o-arrow-left" />
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Images -->
            @if(!empty($apartment->images))
                <x-card shadow>
                    <x-slot:title>Images</x-slot:title>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        @foreach($apartment->images as $image)
                            <img src="{{ asset('storage/' . $image) }}" alt="Apartment image" class="w-full h-48 object-cover rounded-lg" />
                        @endforeach
                    </div>
                </x-card>
            @endif

            <!-- Description -->
            @if($apartment->description)
                <x-card shadow>
                    <x-slot:title>Description</x-slot:title>
                    <p class="text-base-content/70 whitespace-pre-line">{{ $apartment->description }}</p>
                </x-card>
            @endif

            <!-- Amenities -->
            @if(!empty($apartment->amenities))
                <x-card shadow>
                    <x-slot:title>Amenities</x-slot:title>
                    <div class="flex flex-wrap gap-2">
                        @php
                            $amenityLabels = [
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
                        @endphp
                        @foreach($apartment->amenities as $amenity)
                            <div class="badge badge-primary badge-lg">
                                {{ $amenityLabels[$amenity] ?? ucfirst(str_replace('_', ' ', $amenity)) }}
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif

            <!-- Tenants -->
            <x-card shadow>
                <x-slot:title>Tenants ({{ $apartment->tenants->count() }})</x-slot:title>
                @if($apartment->tenants->count() > 0)
                    <div class="space-y-4">
                        @foreach($apartment->tenants as $tenant)
                            <div class="border-b border-base-300 pb-4 last:border-0 last:pb-0">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-semibold">{{ $tenant->name }}</h4>
                                        <p class="text-sm text-base-content/70">{{ $tenant->email }}</p>
                                        <p class="text-sm text-base-content/70">{{ $tenant->phone }}</p>
                                    </div>
                                    <div class="badge {{ $tenant->status === 'active' ? 'badge-success' : 'badge-ghost' }}">
                                        {{ ucfirst($tenant->status) }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-base-content/70">No tenants assigned to this apartment.</p>
                @endif
            </x-card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Details Card -->
            <x-card shadow>
                <x-slot:title>Details</x-slot:title>
                <div class="space-y-4">
                    <div>
                        <div class="text-sm text-base-content/70">Location</div>
                        <div class="font-semibold">{{ $apartment->location->name }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-base-content/70">Address</div>
                        <div class="font-semibold">{{ $apartment->address }}</div>
                    </div>
                    @if($apartment->unit_number)
                        <div>
                            <div class="text-sm text-base-content/70">Unit Number</div>
                            <div class="font-semibold">{{ $apartment->unit_number }}</div>
                        </div>
                    @endif
                    <div>
                        <div class="text-sm text-base-content/70">Status</div>
                        @php
                            $statusColors = [
                                'available' => 'badge-success',
                                'occupied' => 'badge-info',
                                'maintenance' => 'badge-warning',
                            ];
                            $color = $statusColors[$apartment->status] ?? 'badge-ghost';
                        @endphp
                        <div class="badge {{ $color }} badge-lg mt-1">
                            {{ ucfirst($apartment->status) }}
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Financial Info -->
            <x-card shadow>
                <x-slot:title>Financial Information</x-slot:title>
                <div class="space-y-4">
                    <div>
                        <div class="text-sm text-base-content/70">Monthly Rent</div>
                        <div class="text-2xl font-bold text-primary">₱{{ number_format($apartment->monthly_rent, 2) }}</div>
                    </div>
                </div>
            </x-card>

            <!-- Property Info -->
            <x-card shadow>
                <x-slot:title>Property Information</x-slot:title>
                <div class="space-y-4">
                    @if($apartment->bedrooms)
                        <div>
                            <div class="text-sm text-base-content/70">Bedrooms</div>
                            <div class="font-semibold">{{ $apartment->bedrooms }}</div>
                        </div>
                    @endif
                    @if($apartment->bathrooms)
                        <div>
                            <div class="text-sm text-base-content/70">Bathrooms</div>
                            <div class="font-semibold">{{ $apartment->bathrooms }}</div>
                        </div>
                    @endif
                    @if($apartment->square_meters)
                        <div>
                            <div class="text-sm text-base-content/70">Square Meters</div>
                            <div class="font-semibold">{{ number_format($apartment->square_meters, 2) }} m²</div>
                        </div>
                    @endif
                </div>
            </x-card>

            <!-- Quick Stats -->
            <x-card shadow>
                <x-slot:title>Quick Stats</x-slot:title>
                <div class="space-y-4">
                    <div>
                        <div class="text-sm text-base-content/70">Total Tenants</div>
                        <div class="text-2xl font-bold">{{ $apartment->tenants->count() }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-base-content/70">Active Tasks</div>
                        <div class="text-2xl font-bold">{{ $apartment->tasks->where('status', '!=', 'done')->count() }}</div>
                    </div>
                </div>
            </x-card>
        </div>
    </div>

    <!-- Kanban Board Section -->
    <div class="mt-8">
        @livewire('pages.apartments.kanban', ['apartment' => $apartment])
    </div>
</div>
