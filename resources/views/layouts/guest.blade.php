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
<body class="min-h-screen font-sans antialiased bg-base-200 relative" x-cloak x-data="{
        darkMode: (localStorage.getItem('darkMode') === 'true' || (localStorage.getItem('darkMode') === null && document.documentElement.classList.contains('dark'))),
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
            // Always read from localStorage to ensure consistency across navigation
            const stored = localStorage.getItem('darkMode');
            const shouldBeDark = stored === 'true' || (stored === null);
            this.darkMode = shouldBeDark;
            
            const html = document.documentElement;
            if (shouldBeDark) {
                html.classList.add('dark');
                html.setAttribute('data-theme', 'dark');
            } else {
                html.classList.remove('dark');
                html.setAttribute('data-theme', 'light');
            }
            
            // Listen for Livewire navigation events to re-apply dark mode
            if (window.Livewire) {
                document.addEventListener('livewire:navigated', () => {
                    const stored = localStorage.getItem('darkMode');
                    const shouldBeDark = stored === 'true' || (stored === null);
                    const html = document.documentElement;
                    if (shouldBeDark) {
                        html.classList.add('dark');
                        html.setAttribute('data-theme', 'dark');
                    } else {
                        html.classList.remove('dark');
                        html.setAttribute('data-theme', 'light');
                    }
                    this.darkMode = shouldBeDark;
                });
            }
        }
    }">
    {{ $slot }}

    {{-- Dark mode toggle (fixed top-right, after slot so it stays on top) --}}
    <div class="fixed top-4 right-4 z-9999">
        <button type="button" @click="toggleDarkMode()" 
            class="cursor-pointerhover:scale-105 transition-all duration-200" 
            title="Toggle dark mode" aria-label="Toggle dark mode">
            <x-icon x-cloak name="o-moon" x-show="!darkMode" class="w-5 h-5" />
            <x-icon x-cloak name="o-sun" x-show="darkMode" class="w-5 h-5" />
        </button>
    </div>
    
    {{-- TOAST area --}}
    <x-toast />
</body>
</html>
