@php
    $isAuthenticated = auth()->check();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>404 - Page Not Found - {{ config('app.name') }}</title>

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
    <div class="min-h-screen flex items-center justify-center bg-base-200 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full text-center">
            <!-- Error Icon -->
            <div class="flex justify-center mb-8">
                <div class="relative">
                    <div class="absolute inset-0 bg-error/20 rounded-full blur-2xl animate-pulse"></div>
                    <div class="relative bg-error/10 rounded-full p-8">
                        <svg class="w-24 h-24 text-error" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Error Code -->
            <h1 class="text-9xl font-bold text-error mb-4">404</h1>
            
            <!-- Error Title -->
            <h2 class="text-3xl md:text-4xl font-bold text-base-content mb-4">
                Page Not Found
            </h2>
            
            <!-- Error Message -->
            <p class="text-lg text-base-content/70 mb-8 max-w-md mx-auto">
                Oops! The page you're looking for doesn't exist or has been moved. 
                It might have been deleted or the URL might be incorrect.
            </p>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                @if($isAuthenticated)
                    @php
                        $user = auth()->user();
                        $dashboardUrl = $user->isAdmin() ? '/admin/dashboard' : '/dashboard';
                    @endphp
                    <a href="{{ $dashboardUrl }}" class="btn btn-primary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Go to Dashboard
                    </a>
                @else
                    <a href="/" class="btn btn-primary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Go to Home
                    </a>
                    <a href="/login" class="btn btn-outline">
                        Sign In
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                @endif
                
                <button onclick="window.history.back()" class="btn btn-ghost">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Go Back
                </button>
            </div>

            <!-- Helpful Links -->
            <div class="mt-12 pt-8 border-t border-base-content/10">
                <p class="text-sm text-base-content/60 mb-4">You might be looking for:</p>
                <div class="flex flex-wrap justify-center gap-4">
                    @if($isAuthenticated)
                        @php
                            $user = auth()->user();
                        @endphp
                        @if($user->isAdmin())
                            <a href="/admin/dashboard" class="text-sm text-primary hover:underline">Admin Dashboard</a>
                            <a href="/admin/apartments" class="text-sm text-primary hover:underline">Apartments</a>
                            <a href="/admin/tenants" class="text-sm text-primary hover:underline">Tenants</a>
                            <a href="/admin/owners" class="text-sm text-primary hover:underline">Owners</a>
                        @elseif($user->isOwner())
                            <a href="/dashboard" class="text-sm text-primary hover:underline">Dashboard</a>
                            <a href="/apartments" class="text-sm text-primary hover:underline">My Apartments</a>
                            <a href="/tenants" class="text-sm text-primary hover:underline">Tenants</a>
                            <a href="/rent-payments" class="text-sm text-primary hover:underline">Rent Payments</a>
                        @endif
                    @else
                        <a href="/" class="text-sm text-primary hover:underline">Home</a>
                        <a href="/login" class="text-sm text-primary hover:underline">Login</a>
                        <a href="/register" class="text-sm text-primary hover:underline">Register</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>
