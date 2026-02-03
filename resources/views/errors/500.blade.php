@php
    $isAuthenticated = auth()->check();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>500 - Server Error - {{ config('app.name') }}</title>

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
            <h1 class="text-9xl font-bold mb-4 text-teal-600">500</h1>
            
            <!-- Error Title -->
            <h2 class="text-3xl md:text-4xl font-bold text-teal-600 mb-4">
                Server Error
            </h2>
            
            <!-- Error Message -->
            <p class="text-lg text-teal-600/70 mb-8 max-w-md mx-auto">
                Something went wrong on our end. We're sorry for the inconvenience. 
                Our team has been notified and is working to fix the issue.
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
                @else
                    <a href="/" class="btn rounded-full text-white bg-teal-500">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Go to Home
                    </a>
                @endif
                
                <button onclick="window.location.reload()" class="btn btn-outline rounded-full text-teal-500">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Try Again
                </button>
            </div>

            <!-- Helpful Information -->
            <div class="mt-12 pt-8 border-t border-teal-600/10">
                <p class="text-sm text-teal-600/60 mb-4">
                    If this problem persists, please contact support or try again in a few moments.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
