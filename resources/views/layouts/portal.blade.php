<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Rentory | Tenant Portal</title>

    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='%2314b8a6'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9'/%3E%3C/svg%3E">
    {{-- add the google fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function() {
            const stored = localStorage.getItem('darkMode');
            const html = document.documentElement;
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
<body class="min-h-screen antialiased bg-base-200" style="font-family: 'Montserrat', sans-serif;" x-cloak x-data="{
        darkMode: (() => {
            const stored = localStorage.getItem('darkMode');
            const isDark = stored === null || stored === 'true';
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
            void html.offsetHeight;
        }
    }">

    {{-- Minimal top bar --}}
    <header class="sticky top-0 z-30 flex items-center justify-between px-4 py-3 bg-base-100 border-b border-base-content/10">
        <x-app-brand icon-width="w-7" text-size="text-xl" />
        <div class="flex items-center gap-2">
            <button type="button" @click="toggleDarkMode()" class="btn btn-ghost btn-sm btn-circle" title="Toggle dark mode">
                <x-icon name="o-moon" x-show="!darkMode" class="w-5 h-5" />
                <x-icon name="o-sun" x-show="darkMode" class="w-5 h-5" />
            </button>
            <form method="POST" action="/logout" class="inline">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm btn-circle" title="Logout">
                    <x-icon name="o-power" class="w-5 h-5" />
                </button>
            </form>
        </div>
    </header>

    {{-- Main content with bottom padding for fixed nav --}}
    <main class="min-h-[calc(100vh-8rem)] pb-24 px-4 pt-4">
        {{ $slot }}
    </main>

    {{-- Bottom navigation (mobile-first) --}}
    <nav class="fixed bottom-0 left-0 right-0 z-40 bg-base-100 border-t border-base-content/10 safe-area-pb">
        <div class="flex items-center justify-around h-16 max-w-lg mx-auto">
            <a href="/portal" wire:navigate class="flex flex-col items-center justify-center flex-1 py-2 text-xs {{ in_array(request()->path(), ['portal', 'portal/dashboard']) ? 'text-teal-500' : 'text-base-content/70' }}">
                <x-icon name="o-chart-bar" class="w-6 h-6 mb-0.5" />
                <span>Dashboard</span>
            </a>
            <a href="/portal/apartments" wire:navigate class="flex flex-col items-center justify-center flex-1 py-2 text-xs {{ request()->is('portal/apartments') ? 'text-teal-500' : 'text-base-content/70' }}">
                <x-icon name="o-building-office" class="w-6 h-6 mb-0.5" />
                <span>My Apartments</span>
            </a>
            <a href="/portal/notifications" wire:navigate class="flex flex-col items-center justify-center flex-1 py-2 text-xs {{ request()->is('portal/notifications') ? 'text-teal-500' : 'text-base-content/70' }}">
                <x-icon name="o-bell" class="w-6 h-6 mb-0.5" />
                <span>Notifications</span>
            </a>
            <a href="/portal/profile" wire:navigate class="flex flex-col items-center justify-center flex-1 py-2 text-xs {{ request()->is('portal/profile') ? 'text-teal-500' : 'text-base-content/70' }}">
                <x-icon name="o-user-circle" class="w-6 h-6 mb-0.5" />
                <span>Profile</span>
            </a>
        </div>
    </nav>

    <x-toast />
</body>
</html>
