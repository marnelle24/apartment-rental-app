<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title> --}}
    <title>Rentory | Your Apartment Rental Management System Partner</title>
    
    {{-- Favicon using cube icon from app-brand --}}
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='%2314b8a6'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9'/%3E%3C/svg%3E">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Initialize dark mode before Alpine loads to prevent flash
        // Default to dark mode if no preference is stored
        (function() {
            const stored = localStorage.getItem('darkMode');
            const html = document.documentElement;
            // Default to dark mode (true) if no preference is stored
            if (stored === null || stored === 'true') {
                html.classList.add('dark');
                html.setAttribute('data-theme', 'dark');
                if (stored === null) {
                    localStorage.setItem('darkMode', 'true');
                }
            } else {
                html.classList.remove('dark');
                html.setAttribute('data-theme', 'light');
            }
        })();
    </script>
</head>
<body class="min-h-screen font-sans antialiased bg-base-200" x-cloak x-data="{
        darkMode: (() => {
            // Read from localStorage first to ensure consistency
            const stored = localStorage.getItem('darkMode');
            const isDark = stored === null || stored === 'true';
            // Ensure DOM matches localStorage
            const html = document.documentElement;
            if (isDark) {
                html.classList.add('dark');
                html.setAttribute('data-theme', 'dark');
            } else {
                html.classList.remove('dark');
                html.setAttribute('data-theme', 'light');
            }
            return isDark;
        })(),
        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            const html = document.documentElement;
            if (this.darkMode) {
                html.classList.add('dark');
                html.setAttribute('data-theme', 'dark');
                localStorage.setItem('darkMode', 'true');
            } else {
                html.classList.remove('dark');
                html.setAttribute('data-theme', 'light');
                localStorage.setItem('darkMode', 'false');
            }
            // Force a repaint to ensure styles are applied
            void html.offsetHeight;
        },
        init() {
            // Double-check and sync on init to ensure consistency
            const stored = localStorage.getItem('darkMode');
            const shouldBeDark = stored === null || stored === 'true';
            this.darkMode = shouldBeDark;
            const html = document.documentElement;
            if (shouldBeDark) {
                html.classList.add('dark');
                html.setAttribute('data-theme', 'dark');
            } else {
                html.classList.remove('dark');
                html.setAttribute('data-theme', 'light');
            }
        }
    }">

    @auth
        {{-- NAVBAR mobile only -- Only show when authenticated --}}
        <x-nav sticky class="">
            <x-slot:brand>
                <x-app-brand icon-width="w-8" text-size="text-2xl" tagline-size="text-[0.46rem]" one-color-logo="" />
            </x-slot:brand>
            <x-slot:actions>
                <button 
                    @click="toggleDarkMode()"
                    class="btn btn-ghost btn-circle me-2"
                    title="Toggle dark mode"
                    type="button"
                >
                    <x-icon name="o-moon" x-show="!darkMode" class="w-5 h-5" />
                    <x-icon name="o-sun" x-show="darkMode" class="w-5 h-5" />
                </button>
                <form method="POST" action="/logout" class="inline">
                    @csrf
                    <button 
                        type="submit"
                        class="btn btn-ghost btn-circle"
                        title="Logout"
                    >
                        <x-icon name="o-power" class="w-5 h-5" />
                    </button>
                </form>
                <label for="main-drawer" class="lg:hidden me-3">
                    <x-icon name="o-bars-3" class="cursor-pointer" />
                </label>
            </x-slot:actions>
        </x-nav>
    @endauth

    {{-- MAIN --}}
    <x-main>
        @auth
            {{-- SIDEBAR -- Only show when authenticated --}}
            <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100 lg:bg-inherit">

                {{-- BRAND --}}
                {{-- <x-app-brand class="px-5 pt-4" /> --}}

                {{-- MENU --}}
                <x-menu activate-by-route>
                    @php
                        $user = auth()->user();
                    @endphp

                    {{-- User Info --}}
                    {{-- <x-menu-separator /> --}}
                    <x-list-item :item="$user" value="name" sub-value="email" no-separator no-hover class="mx-2 rounded">
                    </x-list-item>
                    
                    @if($user->isOwner())
                        @php
                            $currentPlan = $user->getEffectivePlan();
                        @endphp
                        @if($currentPlan)
                            <div class="px-4 py-1.5 mb-2">
                                {{-- <span class="text-sm text-base-content/80">Plan: </span> --}}
                                <span class="badge badge-md badge-success text-md text-white dropshadow-md">{{ $currentPlan->name . ' Plan' }}</span>
                            </div>
                        @endif
                    @endif

                    @if($user->isAdmin())
                        <div class="px-4 py-1.5 mb-2">
                            <span class="badge badge-md badge-success text-md text-white dropshadow-md">Admnistrator</span>
                        </div>
                    @endif
                    
                    @if($user->isAdmin())
                        {{-- Admin Menu --}}
                        <x-menu-item title="Dashboard" icon="o-chart-bar" link="/admin/dashboard" /> 
                        <x-menu-item title="Owner Monitoring" icon="o-user-group" link="/admin/owners" /> 
                        <x-menu-item title="Tenant Monitoring" icon="o-users" link="/admin/tenants" /> 
                        <x-menu-item title="Apartment Monitoring" icon="o-building-office" link="/admin/apartments" /> 
                        <x-menu-item title="Locations" icon="o-map-pin" link="/locations" /> 
                        <x-menu-item title="Plans" icon="o-rectangle-stack" link="/admin/plans" />
                        <x-menu-item title="Users" icon="o-users" link="/users" /> 
                    @elseif($user->isOwner())
                        {{-- Owner Menu --}}
                        <x-menu-item title="Dashboard" icon="o-chart-bar" link="/dashboard" /> 
                        <x-menu-item title="My Apartments" icon="o-building-office" link="/apartments" /> 
                        <x-menu-item title="Tenants" icon="o-users" link="/tenants" /> 
                        <x-menu-item title="Rent Payments" icon="o-banknotes" link="/rent-payments" /> 
                        
                        {{-- Reports Submenu --}}
                        <x-menu-sub title="Reports" icon="o-document-chart-bar">
                            <x-menu-item title="Overview" icon="o-document-text" link="/reports" /> 
                            <x-menu-item title="Revenue" icon="o-currency-dollar" link="/reports/revenue" /> 
                            <x-menu-item title="Occupancy" icon="o-building-office-2" link="/reports/occupancy" /> 
                            <x-menu-item title="Tenant Turnover" icon="o-arrow-path" link="/reports/tenant-turnover" /> 
                        </x-menu-sub>

                        <x-menu-separator />

                        <x-menu-item title="Settings" icon="o-cog-6-tooth" link="/settings" />

                        {{-- Subscription / Billing --}}
                        <x-menu-item title="Subscription" icon="o-credit-card" link="/subscription/pricing" />
                        <x-menu-item title="Invoices" icon="o-document-text" link="/subscription/invoices" />
                    @endif
                </x-menu>
            </x-slot:sidebar>
        @endauth

        {{-- The `$slot` goes here --}}
        <x-slot:content>
            {{ $slot }}
        </x-slot:content>
    </x-main>

    {{--  TOAST area --}}
    <x-toast />
</body>
</html>
