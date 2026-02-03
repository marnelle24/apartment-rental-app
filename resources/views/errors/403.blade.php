@php
    $isAuthenticated = auth()->check();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>403 - Unauthorized Access - {{ config('app.name') }}</title>

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
                    <div class="absolute inset-0 bg-warning/20 rounded-full blur-2xl animate-pulse"></div>
                    <div class="relative bg-warning/10 rounded-full p-8">
                        <svg class="w-24 h-24 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Error Code -->
            <h1 class="text-9xl font-bold mb-4 text-teal-600">403</h1>
            
            <!-- Error Title -->
            <h2 class="text-3xl md:text-4xl font-bold text-teal-600 mb-4">
                Unauthorized Access
            </h2>
            
            <!-- Error Message -->
            <p class="text-lg text-teal-600/70 mb-8 max-w-md mx-auto">
                @if($isAuthenticated)
                    You don't have permission to access this page. This area is restricted to users with the appropriate role.
                @else
                    You need to be logged in to access this page. Please sign in to continue.
                @endif
            </p>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                @if($isAuthenticated)
                    @php
                        $user = auth()->user();
                        $dashboardUrl = $user->isAdmin() ? '/admin/dashboard' : ($user->isTenant() ? '/portal' : '/dashboard');
                    @endphp
                    <a href="{{ $dashboardUrl }}" class="btn rounded-full text-white bg-teal-500">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Go to Dashboard
                    </a>
                    <button onclick="window.history.back()" class="btn btn-outline rounded-full text-teal-500">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Go Back
                    </button>
                @else
                    <a href="/login" class="btn rounded-full text-white bg-teal-500">
                        Sign In
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                    <a href="/" class="btn btn-outline rounded-full text-teal-500">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Go to Home
                    </a>
                @endif
            </div>

            <!-- Helpful Information -->
            <div class="mt-12 pt-8 border-t border-teal-600/10">
                <p class="text-sm text-teal-600/60 mb-4">
                    @if($isAuthenticated)
                        @php
                            $user = auth()->user();
                        @endphp
                        @if($user->isAdmin())
                            <span class="font-semibold">You are logged in as an Administrator.</span> You have access to admin features.
                        @elseif($user->isOwner())
                            <span class="font-semibold">You are logged in as a Property Owner.</span> You have access to owner features.
                        @else
                            <span class="font-semibold">Your account doesn't have access to this area.</span> Please contact an administrator if you believe this is an error.
                        @endif
                    @else
                        <span class="font-semibold">You are not logged in.</span> Please sign in to access protected areas.
                    @endif
                </p>
                
                <div class="flex flex-wrap justify-center gap-4 mt-4">
                    @if($isAuthenticated)
                        @php
                            $user = auth()->user();
                        @endphp
                        @if($user->isAdmin())
                            <a href="/admin/dashboard" class="text-sm text-teal-600 hover:underline">Admin Dashboard</a>
                            <a href="/admin/apartments" class="text-sm text-teal-600 hover:underline">Apartments</a>
                            <a href="/admin/tenants" class="text-sm text-teal-600 hover:underline">Tenants</a>
                        @elseif($user->isOwner())
                            <a href="/dashboard" class="text-sm text-teal-600 hover:underline">Dashboard</a>
                            <a href="/apartments" class="text-sm text-teal-600 hover:underline">My Apartments</a>
                            <a href="/tenants" class="text-sm text-teal-600 hover:underline">Tenants</a>
                        @elseif($user->isTenant())
                            <a href="/portal" class="text-sm text-teal-600 hover:underline">Portal</a>
                            <a href="/portal/apartments" class="text-sm text-teal-600 hover:underline">My Apartments</a>
                            <a href="/portal/profile" class="text-sm text-teal-600 hover:underline">Profile</a>
                        @endif
                    @else
                        <a href="/" class="text-sm text-teal-600 hover:underline">Home</a>
                        <a href="/login" class="text-sm text-teal-600 hover:underline">Login</a>
                        <a href="/register" class="text-sm text-teal-600 hover:underline">Register</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body> 
</html>
