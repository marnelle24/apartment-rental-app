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
    public string $property_type = '';
    public string $rent_range = '';
    public string $listing_type = 'rent'; // 'rent' or 'sale'

    public function clearFilters(): void
    {
        $this->reset(['search', 'location_id', 'property_type', 'rent_range']);
        $this->resetPage();
    }

    public function updated($propertyName): void
    {
        if (in_array($propertyName, ['search', 'location_id', 'property_type', 'rent_range', 'listing_type'])) {
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
            ->when($this->location_id > 0, fn (Builder $q) => $q->where('location_id', $this->location_id));

        return $query->orderBy('created_at', 'desc')->paginate(6);
    }

    public function getLocationsProperty()
    {
        return Location::orderBy('name')->get();
    }

    public function getLocationsWithCountsProperty()
    {
        return Location::withCount(['apartments' => function (Builder $q) {
            $q->where('status', 'available')
                ->orWhereHas('tenants', fn (Builder $q) => $q->where('status', 'inactive'));
        }])
        ->having('apartments_count', '>', 0)
        ->orderBy('apartments_count', 'desc')
        ->limit(6)
        ->get();
    }

    public function getLayout(): string
    {
        return 'layouts.guest';
    }
};
?>

<div class="min-h-screen bg-white dark:bg-base-200">
    {{-- Top Info Bar --}}
    <div class="bg-slate-800 dark:bg-slate-900 text-white py-2">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-col md:flex-row items-center justify-between gap-2 text-sm">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <x-icon name="o-envelope" class="w-4 h-4" />
                        <span>support@realtor.com</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-icon name="o-phone" class="w-4 h-4" />
                        <span>+0 000 000 00</span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-3">
                        <a href="#" class="hover:text-blue-400 transition-colors" title="Facebook">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="#" class="hover:text-blue-400 transition-colors" title="Twitter">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                        </a>
                        <a href="#" class="hover:text-blue-400 transition-colors" title="LinkedIn">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                        <a href="#" class="hover:text-blue-400 transition-colors" title="Instagram">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </a>
                        <a href="#" class="hover:text-blue-400 transition-colors" title="YouTube">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        </a>
                    </div>
                    <a href="#contact" class="hover:text-blue-400 transition-colors">Contact Us</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Navigation --}}
    <header class="sticky top-0 z-50 bg-white/95 dark:bg-base-100/95 backdrop-blur-md shadow-sm">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between py-4">
                {{-- Logo --}}
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-blue-600 dark:bg-blue-500 rounded-lg flex items-center justify-center">
                        <x-icon name="o-home-modern" class="w-8 h-8 text-white" />
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-slate-900 dark:text-white">REIS</div>
                        <div class="text-xs text-slate-600 dark:text-slate-400">Real Estate Listing</div>
                    </div>
                </div>

                {{-- Navigation Links --}}
                <nav class="hidden lg:flex items-center gap-6">
                    <a href="#" class="text-slate-900 dark:text-white font-medium hover:text-blue-600 dark:hover:text-blue-400 transition-colors">HOME</a>
                    <a href="#" class="text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">ABOUT US</a>
                    <a href="#" class="text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">SERVICES</a>
                    <a href="#" class="text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">PROPERTIES</a>
                    <a href="#" class="text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">GALLERY</a>
                    <a href="#" class="text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">BLOG</a>
                    <a href="#" class="text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">CONTACT US</a>
                    <a href="#" class="text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">STORE</a>
                </nav>

                {{-- Add Listing Button --}}
                <a href="/apartments/create" wire:navigate class="bg-blue-600 dark:bg-blue-500 hover:bg-blue-700 dark:hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                    Add Listing
                </a>
            </div>
        </div>
    </header>

    {{-- Hero Section with Search Panel --}}
    <div class="relative">
        <section class="relative h-[700px] md:h-[700px] flex items-center justify-center overflow-hidden">
            {{-- Background Image with Overlay --}}
            <div class="absolute inset-0 overflow-hidden">
                <div class="w-full h-full bg-linear-to-br from-blue-900/80 to-slate-900/80 dark:from-blue-950/90 dark:to-slate-950/90"></div>
                <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1568605114967-8130f3a36994?w=1920')] bg-cover bg-center bg-no-repeat opacity-30"></div>
            </div>

            {{-- Hero Content --}}
            <div class="relative z-10 max-w-4xl mx-auto px-4 text-center text-white">
                <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold mb-6">
                    Find Your Dream Home
                </h1>
                <p class="text-lg md:text-xl text-white/90 mb-8 max-w-2xl mx-auto">
                    Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae. Proin sodales ultrices nulla blandit volutpat.
                </p>

                {{-- Rent/Sale Toggle --}}
                <div class="flex items-center justify-center gap-4 mb-8">
                    <button 
                        wire:click="$set('listing_type', 'rent')"
                        class="px-8 py-3 rounded-lg font-medium transition-all {{ $listing_type === 'rent' ? 'bg-blue-600 text-white shadow-lg' : 'bg-white/20 text-white hover:bg-white/30' }}"
                    >
                        Rent
                    </button>
                    <button 
                        wire:click="$set('listing_type', 'sale')"
                        class="px-8 py-3 rounded-lg font-medium transition-all {{ $listing_type === 'sale' ? 'bg-blue-600 text-white shadow-lg' : 'bg-white/20 text-white hover:bg-white/30' }}"
                    >
                        Sale
                    </button>
                </div>
            </div>
        </section>

        {{-- Search Panel (Overlapping) --}}
        <div class="absolute bottom-0 left-0 right-0 z-999 transform translate-y-1/2 pointer-events-auto">
            <div class="max-w-6xl mx-auto px-4 relative">
                <div class="bg-white dark:bg-base-100 rounded-xl shadow-2xl p-6 border border-gray-400/60 dark:border-gray-200 relative z-9999">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Location</label>
                            <select wire:model.live="location_id" class="select select-bordered w-full">
                                <option value="0">Select your city</option>
                                @foreach($this->locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Property Type</label>
                            <select wire:model.live="property_type" class="select select-bordered w-full">
                                <option value="">Select property type</option>
                                <option value="apartment">Apartment</option>
                                <option value="house">House</option>
                                <option value="condo">Condo</option>
                                <option value="villa">Villa</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Rent Range</label>
                            <select wire:model.live="rent_range" class="select select-bordered w-full">
                                <option value="">Select rent range</option>
                                <option value="0-50000">₱0 - ₱50,000</option>
                                <option value="50000-100000">₱50,000 - ₱100,000</option>
                                <option value="100000-200000">₱100,000 - ₱200,000</option>
                                <option value="200000+">₱200,000+</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button wire:click="$refresh" class="btn btn-primary w-full py-3">
                                <x-icon name="o-magnifying-glass" class="w-5 h-5" />
                                Search
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Properties by Area Section --}}
    <section class="py-20 mt-32 relative z-10">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-slate-900 dark:text-white mb-4">Properties by Area</h2>
                <p class="text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
                    Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae. Proin sodales ultrices nulla blandit volutpat.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($this->locationsWithCounts as $location)
                    <div class="relative h-64 rounded-lg overflow-hidden group cursor-pointer">
                        <div class="absolute inset-0 bg-linear-to-br from-blue-600/80 to-slate-800/80"></div>
                        <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1568605114967-8130f3a36994?w=800')] bg-cover bg-center opacity-50 group-hover:scale-110 transition-transform duration-300"></div>
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-white p-6">
                            <h3 class="text-2xl font-bold mb-2">{{ $location->name }}</h3>
                            <p class="text-lg">{{ $location->apartments_count }} listings</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Services Section --}}
    <section class="py-20 bg-slate-50 dark:bg-slate-900">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-slate-900 dark:text-white mb-4">Our Services</h2>
                <p class="text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
                    Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae. Proin sodales ultrices nulla blandit volutpat.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach([
                    ['title' => 'Sell your home', 'icon' => 'o-home-modern', 'description' => 'Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae.'],
                    ['title' => 'Rent your home', 'icon' => 'o-key', 'description' => 'Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae.'],
                    ['title' => 'Buy a home', 'icon' => 'o-shopping-bag', 'description' => 'Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae.'],
                    ['title' => 'Free marketing', 'icon' => 'o-megaphone', 'description' => 'Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae.'],
                ] as $service)
                    <div class="bg-white dark:bg-base-100 rounded-lg p-6 shadow-md hover:shadow-xl transition-shadow {{ $loop->first ? 'shadow-lg' : '' }}">
                        <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mb-4">
                            <x-icon name="{{ $service['icon'] }}" class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">{{ $service['title'] }}</h3>
                        <p class="text-slate-600 dark:text-slate-400 mb-4 text-sm">{{ $service['description'] }}</p>
                        <a href="#" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">Read More</a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Latest Properties Grid Section --}}
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-slate-900 dark:text-white mb-4">Latest Properties for Rent</h2>
                <p class="text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
                    Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae. Proin sodales ultrices nulla blandit volutpat.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                @foreach($this->listings as $apt)
                    <div class="bg-white dark:bg-base-100 rounded-lg shadow-md hover:shadow-xl transition-shadow overflow-hidden">
                        {{-- Image --}}
                        <div class="relative h-64">
                            @if($apt->images && count($apt->images) > 0)
                                <img src="{{ apartment_image_url($apt->images[0]) }}" alt="{{ $apt->name }}" class="w-full h-full object-cover" />
                            @else
                                <div class="w-full h-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center">
                                    <x-icon name="o-building-office" class="w-16 h-16 text-slate-400" />
                                </div>
                            @endif
                            {{-- Tag --}}
                            <div class="absolute top-4 left-4">
                                <span class="bg-blue-600 text-white px-3 py-1 rounded text-sm font-medium">FOR RENT</span>
                            </div>
                            {{-- Action Icons --}}
                            <div class="absolute top-4 right-4 flex gap-2">
                                <button class="bg-white/90 hover:bg-white rounded-full p-2 shadow-md">
                                    <x-icon name="o-share" class="w-4 h-4 text-slate-700" />
                                </button>
                                <button class="bg-white/90 hover:bg-white rounded-full p-2 shadow-md">
                                    <x-icon name="o-heart" class="w-4 h-4 text-slate-700" />
                                </button>
                                <button class="bg-white/90 hover:bg-white rounded-full p-2 shadow-md">
                                    <x-icon name="o-arrows-right-left" class="w-4 h-4 text-slate-700" />
                                </button>
                            </div>
                        </div>

                        {{-- Content --}}
                        <div class="p-6">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2 line-clamp-1">
                                {{ $apt->address ?? $apt->name }}
                            </h3>
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mb-4">
                                {{ currency_symbol($apt->currency ?? $apt->owner?->ownerSetting?->currency ?? 'PHP') }}{{ number_format($apt->monthly_rent, 0) }}
                            </p>
                            <div class="flex items-center gap-4 text-slate-600 dark:text-slate-400 mb-4">
                                @if($apt->bedrooms)
                                    <div class="flex items-center gap-1">
                                        <x-icon name="o-home" class="w-4 h-4" />
                                        <span>{{ $apt->bedrooms }} beds</span>
                                    </div>
                                @endif
                                @if($apt->bathrooms)
                                    <div class="flex items-center gap-1">
                                        <x-icon name="o-wrench-screwdriver" class="w-4 h-4" />
                                        <span>{{ $apt->bathrooms }} baths</span>
                                    </div>
                                @endif
                                @if($apt->square_meters)
                                    <div class="flex items-center gap-1">
                                        <x-icon name="o-squares-plus" class="w-4 h-4" />
                                        <span>{{ $apt->square_meters }} sqft</span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex items-center gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                                <div class="w-10 h-10 bg-slate-300 dark:bg-slate-600 rounded-full flex items-center justify-center">
                                    <x-icon name="o-user" class="w-5 h-5 text-slate-600 dark:text-slate-400" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">Agent Name</p>
                                    <p class="text-xs text-slate-600 dark:text-slate-400">Real Estate Agent</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="text-center">
                <button class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-medium transition-colors">
                    Load more listing
                </button>
            </div>
        </div>
    </section>

    {{-- Latest Properties Carousel Section --}}
    <section class="py-20 bg-slate-50 dark:bg-slate-900">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-slate-900 dark:text-white mb-4">Latest Properties for Rent</h2>
                <p class="text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
                    Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae. Proin sodales ultrices nulla blandit volutpat.
                </p>
            </div>

            {{-- Carousel --}}
            <div class="relative">
                <div class="carousel carousel-center space-x-4 rounded-box">
                    @foreach($this->listings->take(5) as $apt)
                        <div class="carousel-item relative w-full md:w-1/2 lg:w-1/3">
                            <div class="relative h-96 rounded-lg overflow-hidden">
                                @if($apt->images && count($apt->images) > 0)
                                    <img src="{{ apartment_image_url($apt->images[0]) }}" alt="{{ $apt->name }}" class="w-full h-full object-cover" />
                                @else
                                    <div class="w-full h-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center">
                                        <x-icon name="o-building-office" class="w-16 h-16 text-slate-400" />
                                    </div>
                                @endif
                                <div class="absolute inset-0 bg-linear-to-t from-black/70 to-transparent">
                                    <div class="absolute bottom-0 left-0 right-0 p-6 text-white">
                                        <span class="bg-blue-600 text-white px-3 py-1 rounded text-sm font-medium mb-2 inline-block">FOR RENT</span>
                                        <h3 class="text-xl font-bold mb-1">{{ $apt->location->name ?? 'Location' }}</h3>
                                        <p class="text-lg">{{ currency_symbol($apt->currency ?? $apt->owner?->ownerSetting?->currency ?? 'PHP') }}{{ number_format($apt->monthly_rent, 0) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                {{-- Navigation Arrows --}}
                <button class="absolute left-0 top-1/2 -translate-y-1/2 btn btn-circle bg-white/90 hover:bg-white shadow-lg">
                    <x-icon name="o-chevron-left" class="w-6 h-6" />
                </button>
                <button class="absolute right-0 top-1/2 -translate-y-1/2 btn btn-circle bg-white/90 hover:bg-white shadow-lg">
                    <x-icon name="o-chevron-right" class="w-6 h-6" />
                </button>
            </div>

            {{-- Pagination Dots --}}
            <div class="flex justify-center gap-2 mt-6">
                <div class="w-3 h-3 bg-blue-600 rounded-full"></div>
                <div class="w-3 h-3 bg-slate-300 dark:bg-slate-600 rounded-full"></div>
            </div>
        </div>
    </section>

    {{-- CTA Banner --}}
    <section class="relative h-96 flex items-center justify-center overflow-hidden">
        <div class="absolute inset-0">
            <div class="w-full h-full bg-linear-to-br from-blue-900/80 to-slate-900/80 dark:from-blue-950/90 dark:to-slate-950/90"></div>
            <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=1920')] bg-cover bg-center opacity-30"></div>
        </div>
        <div class="relative z-10 max-w-4xl mx-auto px-4 text-center text-white">
            <h2 class="text-4xl md:text-5xl font-bold mb-4">Find Best Place For Living</h2>
            <p class="text-lg md:text-xl text-white/90 mb-8 max-w-2xl mx-auto">
                Socios vacations in best hostels and resorts find the great place of your choice, using different searching options
            </p>
            <button class="bg-white text-blue-600 hover:bg-slate-100 px-8 py-3 rounded-lg font-medium transition-colors">
                Contact Us
            </button>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="bg-slate-900 dark:bg-slate-950 text-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
                {{-- Column 1: Logo & Contact --}}
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                            <x-icon name="o-home-modern" class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <div class="text-xl font-bold">REIS</div>
                            <div class="text-xs text-slate-400">Real Estate Listing</div>
                        </div>
                    </div>
                    <h3 class="font-bold mb-4">Contact Us</h3>
                    <div class="space-y-2 text-slate-400 text-sm">
                        <p>Phone: +0 000 000 00</p>
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                        <p>Email: lorem@ipsum.com</p>
                    </div>
                    <div class="flex items-center gap-3 mt-4">
                        <a href="#" class="hover:text-blue-400 transition-colors" title="Facebook">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="#" class="hover:text-blue-400 transition-colors" title="Twitter">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                        </a>
                        <a href="#" class="hover:text-blue-400 transition-colors" title="LinkedIn">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                        <a href="#" class="hover:text-blue-400 transition-colors" title="Instagram">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </a>
                        <a href="#" class="hover:text-blue-400 transition-colors" title="YouTube">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        </a>
                    </div>
                </div>

                {{-- Column 2: Features --}}
                <div>
                    <h3 class="font-bold mb-4">Features</h3>
                    <ul class="space-y-2 text-slate-400 text-sm">
                        <li><a href="#" class="hover:text-white transition-colors">Home</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Become a Host</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Pricing</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Blog</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Contact</a></li>
                    </ul>
                </div>

                {{-- Column 3: Company --}}
                <div>
                    <h3 class="font-bold mb-4">Company</h3>
                    <ul class="space-y-2 text-slate-400 text-sm">
                        <li><a href="#" class="hover:text-white transition-colors">About Us</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Press</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Contact</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Careers</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Blog</a></li>
                    </ul>
                </div>

                {{-- Column 4: Team and policies --}}
                <div>
                    <h3 class="font-bold mb-4">Team and policies</h3>
                    <ul class="space-y-2 text-slate-400 text-sm">
                        <li><a href="#" class="hover:text-white transition-colors">Terms of service</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Security</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-slate-800 pt-8 text-center text-slate-400 text-sm">
                <p>&copy; {{ date('Y') }} REIS Real Estate Listing. All rights reserved.</p>
            </div>
        </div>
    </footer>
</div>
