<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- <title>{{ config('app.name', 'Rentory') }} - Find Your Next Rental</title> --}}
    <title>Rentory | Your Apartment Rental Management System Partner</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='%2314b8a6'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9'/%3E%3C/svg%3E">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function() {
            const stored = localStorage.getItem('darkMode');
            const html = document.documentElement;
            if (stored === null || stored === 'true') {
                html.classList.add('dark');
                html.setAttribute('data-theme', 'dark');
            } else {
                html.classList.remove('dark');
                html.setAttribute('data-theme', 'light');
            }
        })();
    </script>
</head>
<body class="min-h-screen font-sans antialiased bg-base-200" x-data="{
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
    }
}">
    
    @php
        $version = request()->query('version', '1');
    @endphp
    
    @if($version === '2')
        @livewire('pages::homepage-v2')
    @else
        @livewire('pages::index')
    @endif
    
    {{-- TOAST area --}}
    <x-toast />
</body>
</html>
