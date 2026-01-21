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
    {{ $slot }}
    
    {{-- TOAST area --}}
    <x-toast />
</body>
</html>
