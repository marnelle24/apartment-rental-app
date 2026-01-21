<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>

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
<body class="min-h-screen font-sans antialiased bg-base-200" x-data="{
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
                <x-app-brand />
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
                    
                    @if($user->isAdmin())
                        {{-- Admin Menu --}}
                        <x-menu-item title="Dashboard" icon="o-chart-bar" link="/admin/dashboard" /> 
                        <x-menu-item title="Locations" icon="o-map-pin" link="/locations" /> 
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
