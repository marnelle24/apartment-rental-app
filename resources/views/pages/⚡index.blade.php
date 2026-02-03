<?php

use App\Models\Apartment;
use App\Models\Location;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public int $location_id = 0;
    public string $min_rent = '';
    public string $max_rent = '';
    public string $bedrooms = '';
    public string $sort = 'newest';

    public bool $showListingModal = false;
    public ?\App\Models\Apartment $selectedApartment = null;

    public function openListingModal(int $apartmentId): void
    {
        $this->selectedApartment = Apartment::with(['location', 'owner'])
            ->where('id', $apartmentId)
            ->where(function (Builder $q) {
                $q->where('status', 'available')
                    ->orWhereHas('tenants', fn (Builder $q) => $q->where('status', 'inactive'));
            })
            ->firstOrFail();
        $this->showListingModal = true;
    }

    public function closeListingModal(): void
    {
        $this->showListingModal = false;
        $this->selectedApartment = null;
    }

    // Component mount - redirects handled by route
    public function mount(): void
    {
        // Redirect logic is handled in the route
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'location_id', 'min_rent', 'max_rent', 'bedrooms']);
        $this->resetPage();
    }

    public function updated($propertyName): void
    {
        if (in_array($propertyName, ['search', 'location_id', 'min_rent', 'max_rent', 'bedrooms', 'sort'])) {
            $this->resetPage();
        }
    }

    public function getListingsProperty()
    {
        $query = Apartment::query()
            ->with('location')
            ->where(function (Builder $q) {
                $q->where('status', 'available')
                    ->orWhereHas('tenants', fn (Builder $q) => $q->where('status', 'inactive'));
            })
            ->when($this->search, fn (Builder $q) => $q->where(function (Builder $q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('address', 'like', '%' . $this->search . '%')
                    ->orWhere('unit_number', 'like', '%' . $this->search . '%')
                    ->orWhereHas('location', fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'));
            }))
            ->when($this->location_id > 0, fn (Builder $q) => $q->where('location_id', $this->location_id))
            ->when($this->min_rent !== '', fn (Builder $q) => $q->where('monthly_rent', '>=', (float) $this->min_rent))
            ->when($this->max_rent !== '', fn (Builder $q) => $q->where('monthly_rent', '<=', (float) $this->max_rent))
            ->when($this->bedrooms !== '', fn (Builder $q) => $q->where('bedrooms', '>=', (int) $this->bedrooms));

        $ordered = match ($this->sort) {
            'rent_asc' => $query->orderBy('monthly_rent', 'asc'),
            'rent_desc' => $query->orderBy('monthly_rent', 'desc'),
            'oldest' => $query->orderBy('created_at', 'asc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        return $ordered->paginate(12);
    }

    public function getLocationsProperty()
    {
        return Location::orderBy('name')->get();
    }

    public function getLayout(): string
    {
        return 'layouts.guest';
    }
};
?>

<div class="min-h-screen bg-linear-to-br from-teal-500/10 via-base-200 to-base-200 dark:from-teal-600/20 dark:via-base-300 dark:to-base-300">
    {{-- Top bar: Logo + Auth --}}
    <header class="sticky top-0 z-50 backdrop-blur-lg">
        <div class="max-w-7xl mx-auto px-4 pt-6 pb-3 flex items-center justify-between">
            <x-app-brand text-size="text-2xl" icon-width="w-8 h-8" />
            <div class="flex items-center gap-2 md:gap-4">
                @auth
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="flex items-center justify-center gap-2 rounded-full bg-base-100 dark:bg-base-800 p-3 md:py-3 md:px-4 shadow-sm hover:shadow-md hover:scale-105 text-sm transition-all duration-200 font-medium cursor-pointer text-base-content dark:text-white/60 hover:bg-base-200 dark:hover:bg-base-700 border-0 dark:border-2 dark:border-white/60 btn-sm" title="Logout">
                            <x-icon name="o-arrow-right-on-rectangle" class="w-5 h-5 md:w-4 md:h-4" />
                            <span class="hidden md:inline">Logout</span>
                        </button>
                    </form>
                @else
                    <a href="/login" wire:navigate class="flex items-center justify-center gap-2 rounded-full bg-base-100 dark:bg-base-800 p-3 md:py-3 md:px-4 shadow-sm hover:shadow-md hover:scale-105 text-sm transition-all duration-200 font-medium cursor-pointer text-base-content dark:text-white/60 hover:bg-base-200 dark:hover:bg-base-700 border-0 dark:border-2 dark:border-white/60 btn-sm" title="Sign In">
                        <x-icon name="o-arrow-right-on-rectangle" class="w-5 h-5 md:w-4 md:h-4" />
                        <span class="hidden md:inline">Sign In</span>
                    </a>
                @endauth
                <a href="{{ auth()->check() ? '/dashboard' : '/register?usertype=owner' }}" wire:navigate class="flex items-center justify-center gap-2 rounded-full bg-teal-600 dark:bg-teal-400 p-3 md:py-3 md:px-4 shadow-sm hover:shadow-md hover:scale-105 text-sm transition-all duration-200 font-medium cursor-pointer text-white dark:text-teal-900 hover:bg-teal-700 dark:hover:bg-teal-300 border-0 btn-sm" title="{{ auth()->check() ? 'Dashboard' : 'Create Account' }}">
                    @auth
                        <x-icon name="o-home" class="w-5 h-5 md:w-4 md:h-4" />
                    @else
                        <x-icon name="o-user-plus" class="w-5 h-5 md:w-4 md:h-4" />
                    @endauth
                    <span class="hidden md:inline">{{ auth()->check() ? 'Dashboard' : 'Create Account' }}</span>
                </a>
            </div>
        </div>
    </header>

    {{-- Hero + Search --}}
    <section class="relative overflow-hidden">
        {{-- <div class="absolute inset-0 bg-linear-to-br from-teal-500/10 via-base-200 to-base-200 dark:from-teal-600/20 dark:via-base-300 dark:to-base-300"></div> --}}
        <div class="relative container mx-auto px-4 py-12 md:py-16 lg:py-20">
            <div class="max-w-4xl mx-auto text-center mb-10">
                <h1 class="text-4xl md:text-4xl lg:text-5xl font-bold bg-linear-to-r from-teal-500 to-teal-700 dark:from-teal-400 dark:to-teal-300 bg-clip-text text-transparent mb-4">
                    Find Your Next <span class="text-teal-600 dark:text-teal-400">Rental</span>
                </h1>
                <p class="text-lg md:text-xl text-base-content/70 mb-2">
                    Search thousands of available apartments. Filter by location, price, and more.
                </p>
            </div>

            {{-- Main search card --}}
            <div class="max-w-6xl mx-auto">
                <div class="card bg-base-100 border border-base-content/10 shadow-xl">
                    <div class="card-body p-4 md:p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-9 gap-4">
                            <div class="col-span-1 lg:col-span-3">
                                <x-input
                                    placeholder="City, address, or location..."
                                    wire:model.live.debounce.300ms="search"
                                    icon="o-magnifying-glass"
                                    class="input-lg w-full"
                                />
                            </div>
                            <div class="col-span-1 lg:col-span-2">
                                <select wire:model.live="location_id" class="select select-bordered select-lg w-full">
                                    <option value="0">All areas</option>
                                    @foreach($this->locations as $loc)
                                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-span-1 lg:col-span-1">
                                <select wire:model.live="bedrooms" class="select select-bordered select-lg w-full">
                                    <option value="">Beds</option>
                                    @foreach(['1', '2', '3', '4', '5'] as $n)
                                        <option value="{{ $n }}">{{ $n }}+</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-span-1 lg:col-span-1">
                                <input type="number" wire:model.live.debounce.300ms="min_rent" placeholder="Min" class="input input-bordered input-lg w-full" min="0" step="500" />
                            </div>
                            <div class="col-span-1 lg:col-span-1">
                                <input type="number" wire:model.live.debounce.300ms="max_rent" placeholder="Max" class="input input-bordered input-lg w-full" min="0" step="500" />
                            </div>
                            <div class="col-span-1 lg:col-span-1">
                                <x-button label="Search" icon="o-magnifying-glass" class="bg-teal-600 dark:bg-teal-400 py-6 md:px-8 lg:text-sm hover:bg-teal-700 hover:scale-105 transition-all duration-200 dark:hover:bg-teal-300 text-white dark:text-teal-900 rounded-lg w-full" />
                            </div>
                        </div>

                    </div>
                </div>
                @if($search || $location_id > 0 || $min_rent !== '' || $max_rent !== '' || $bedrooms !== '')
                    <div class="mt-4 lg:px-0 px-4 flex items-center justify-between">
                        <span class="text-sm text-base-content/60">Filters active</span>
                        <button wire:click="clearFilters" class="text-teal-600 dark:text-teal-400 text-sm hover:text-teal-700 dark:hover:text-teal-300 transition-all duration-200 hover:underline">Clear all</button>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Listings + Filters bar --}}
    <section class="max-w-6xl mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <h2 class="text-xl font-bold text-base-content">
                @if($search || $location_id > 0 || $min_rent !== '' || $max_rent !== '' || $bedrooms !== '')
                    Search results
                @else
                    Available rentals
                @endif
                <span class="text-base-content/60 font-normal text-lg">({{ $this->listings->total() }})</span>
            </h2>
            <div class="flex items-center gap-2">
                <span class="text-sm text-base-content/60">Sort:</span>
                <select wire:model.live="sort" class="select select-bordered select-md border-base-content/10 ">
                    <option value="newest">Newest</option>
                    <option value="oldest">Oldest</option>
                    <option value="rent_asc">Price: Low to High</option>
                    <option value="rent_desc">Price: High to Low</option>
                </select>
            </div>
        </div>

        @if($this->listings->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-8">
                @foreach($this->listings as $apt)
                    <div
                        wire:click="openListingModal({{ $apt->id }})"
                        class="group card bg-base-100 hover:-translate-y-0.5 duration-300 transition-all border border-base-content/10 shadow hover:shadow-lg overflow-hidden cursor-pointer"
                        role="button"
                        tabindex="0"
                    >
                        {{-- Image placeholder / first image --}}
                        <figure class="aspect-100/70 bg-base-300 dark:bg-base-200 relative">
                            @if($apt->images && count($apt->images) > 0)
                                <img src="{{ asset('storage/' . $apt->images[0]) }}" alt="{{ $apt->name }}" class="object-cover w-full h-full" />
                            @else
                                <div class="w-full h-full flex items-center justify-center text-base-content/30">
                                    <x-icon name="o-building-office" class="w-16 h-16" />
                                </div>
                            @endif
                            {{-- <div class="absolute top-2 right-2">
                                <span class="badge badge-success badge-md py-1 px-2 rounded-full">Available</span>
                            </div> --}}
                            <div class="absolute bottom-2 right-2 text-right bg-white dark:bg-base-800 px-3 font-bold text-teal-600 dark:text-teal-400 whitespace-nowrap">
                                ₱{{ number_format($apt->monthly_rent, 0) }}<span class="text-sm font-normal text-base-content/60 dark:text-gray-700">/mo</span>
                            </div>
                        </figure>
                        <div class="card-body p-4">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="card-title text-xl line-clamp-1 text-base-content group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-all duration-200">
                                    {{ $apt->name }}
                                </h3>
                            </div>
                            @if($apt->location)
                                <p class="text-sm text-base-content/60 flex items-center gap-1">
                                    <x-icon name="o-map-pin" class="w-4 h-4" />
                                    {{ $apt->location->name }}
                                </p>
                            @endif
                            {{-- @if($apt->address)
                                <p class="text-sm text-base-content/50 truncate" title="{{ $apt->address }}">{{ $apt->address }}</p>
                            @endif --}}
                            @if($apt->description)
                                <p class="text-sm text-base-content/70 line-clamp-2">{{ Str::limit($apt->description, 80) }}</p>
                            @endif
                            <div class="flex flex-wrap gap-1 mt-2">
                                @if($apt->bedrooms !== null)
                                    <span class="badge badge-ghost border border-gray-200 py-2 px-2 badge-sm">
                                        <x-icon name="o-home" class="w-4 h-4" />
                                        {{ $apt->bedrooms }} bed
                                    </span>
                                @endif
                                @if($apt->bathrooms !== null)
                                    <span class="badge badge-ghost border border-gray-200 py-2 px-3 badge-sm">
                                        <x-icon name="o-wrench-screwdriver" class="w-4 h-4" />
                                        {{ $apt->bathrooms }} bath
                                    </span>
                                @endif
                                @if($apt->square_meters)
                                    <span class="badge badge-ghost border border-gray-200 py-2 px-3 badge-sm">
                                        <x-icon name="o-squares-plus" class="w-4 h-4" />
                                        {{ $apt->square_meters }} m²
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="card bg-base-100 border border-base-content/10 shadow">
                <div class="card-body items-center text-center py-16">
                    <x-icon name="o-magnifying-glass" class="w-16 h-16 text-base-content/30 mb-4" />
                    <h3 class="text-xl font-semibold text-base-content">No rentals match your search</h3>
                    <p class="text-base-content/60 max-w-md">Try adjusting your filters or search term, or clear filters to see all available listings.</p>
                    <x-button label="Clear filters" icon="o-x-mark" wire:click="clearFilters" class="btn-primary mt-4" />
                </div>
            </div>
        @endif
    </section>

    {{-- Apartment Detail Drawer (slides from right, blurred background) --}}
    @if($showListingModal && $selectedApartment)
        <div class="listing-drawer-blur">
            <x-drawer wire:model="showListingModal" :title="$selectedApartment->name" right separator with-close-button close-on-escape class="w-full sm:max-w-xl lg:max-w-2xl min-h-screen rounded-none px-6 lg:px-8">
            <div class="space-y-6">
                {{-- Images carousel (when multiple) or single image --}}
                <div class="rounded-xl overflow-hidden bg-base-200 aspect-video">
                    @if($selectedApartment->images && count($selectedApartment->images) > 0)
                        @if(count($selectedApartment->images) > 1)
                            @php
                                $slides = collect($selectedApartment->images)->map(fn ($img) => [
                                    'image' => asset('storage/' . $img),
                                    'alt' => $selectedApartment->name,
                                ])->all();
                            @endphp
                            <div class="listing-carousel-fill h-full w-full">
                                <x-carousel :slides="$slides" class="h-full! w-full! rounded-xl!" />
                            </div>
                        @else
                            <img src="{{ asset('storage/' . $selectedApartment->images[0]) }}" alt="{{ $selectedApartment->name }}" class="w-full h-full object-cover" />
                        @endif
                    @else
                        <div class="w-full h-full flex items-center justify-center text-base-content/30">
                            <x-icon name="o-building-office" class="w-24 h-24" />
                        </div>
                    @endif
                </div>

                {{-- Title & Price --}}
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-base-content">{{ $selectedApartment->name }}</h2>
                        @if($selectedApartment->location)
                            <p class="text-base-content/70 flex items-center gap-1 mt-1">
                                <x-icon name="o-map-pin" class="w-4 h-4 shrink-0" />
                                {{ $selectedApartment->location->name }}
                            </p>
                        @endif
                    </div>
                    <div class="text-2xl font-bold text-teal-600 dark:text-teal-400 whitespace-nowrap">
                        ₱{{ number_format($selectedApartment->monthly_rent, 0) }}<span class="text-base font-normal text-base-content/60">/mo</span>
                    </div>
                </div>

                {{-- Specs --}}
                <div class="flex flex-wrap gap-2">
                    @if($selectedApartment->bedrooms !== null)
                        <span class="badge badge-ghost border border-base-content/20 py-2 px-3">{{ $selectedApartment->bedrooms }} bed</span>
                    @endif
                    @if($selectedApartment->bathrooms !== null)
                        <span class="badge badge-ghost border border-base-content/20 py-2 px-3">{{ $selectedApartment->bathrooms }} bath</span>
                    @endif
                    @if($selectedApartment->square_meters)
                        <span class="badge badge-ghost border border-base-content/20 py-2 px-3">{{ $selectedApartment->square_meters }} m²</span>
                    @endif
                    @if($selectedApartment->unit_number)
                        <span class="badge badge-ghost border border-base-content/20 py-2 px-3">Unit {{ $selectedApartment->unit_number }}</span>
                    @endif
                </div>

                {{-- Address --}}
                @if($selectedApartment->address)
                    <div>
                        <h4 class="font-semibold text-base-content/80 mb-1">Address</h4>
                        <p class="text-base-content/70">{{ $selectedApartment->address }}</p>
                    </div>
                @endif

                {{-- Description --}}
                @if($selectedApartment->description)
                    <div>
                        <h4 class="font-semibold text-base-content/80 mb-1">Description</h4>
                        <p class="text-base-content/70 whitespace-pre-line">{{ $selectedApartment->description }}</p>
                    </div>
                @endif

                {{-- Amenities --}}
                @if(!empty($selectedApartment->amenities) && is_array($selectedApartment->amenities))
                    <div>
                        <h4 class="font-semibold text-base-content/80 mb-2">Amenities</h4>
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
                            @foreach($selectedApartment->amenities as $amenity)
                                <span class="badge badge-primary badge-outline py-2">
                                    {{ $amenityLabels[$amenity] ?? ucfirst(str_replace('_', ' ', $amenity)) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Owner / Contact --}}
                @if($selectedApartment->owner)
                    <div class="border-t border-base-300 pt-6 mt-6">
                        <h4 class="font-semibold text-base-content/80 mb-3">Contact Owner</h4>
                        <div class="bg-base-200 border border-base-content/10 rounded-xl p-4 space-y-2">
                            <div class="flex items-center gap-3">
                                <div class="avatar placeholder">
                                    <div class="bg-teal-500/20 flex items-center justify-center text-teal-600 dark:text-teal-400 rounded-full w-12">
                                        <span class="text-lg font-semibold">{{ substr($selectedApartment->owner->name, 0, 1) }}</span>
                                    </div>
                                </div>
                                <div>
                                    <p class="font-semibold text-base-content">{{ $selectedApartment->owner->name }}</p>
                                    <a href="mailto:{{ $selectedApartment->owner->email }}" class="text-teal-600 dark:text-teal-400 hover:underline flex items-center gap-1 text-sm">
                                        <x-icon name="o-envelope" class="w-4 h-4" />
                                        {{ $selectedApartment->owner->email }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <x-slot:actions>
                <x-button label="Close" wire:click="closeListingModal" class="bg-base-200 rounded-full gap-2" />
                @if($selectedApartment->owner)
                    <a href="mailto:{{ $selectedApartment->owner->email }}" class="btn rounded-full text-white bg-teal-500 gap-2">
                        <x-icon name="o-chat-bubble-left-right" class="w-4 h-4" />
                        Contact Owner
                    </a>
                @endif
            </x-slot:actions>
        </x-drawer>
        </div>
    @endif

    {{-- CTA footer --}}
    <footer class="border-t border-base-content/10 bg-base-100 mt-16">
        <div class="container mx-auto px-4 py-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <x-app-brand text-size="text-lg" icon-width="w-6 h-6" />
                <p class="text-xs text-base-content/60">
                    &copy; {{ date('Y') }} Rentory. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</div>
