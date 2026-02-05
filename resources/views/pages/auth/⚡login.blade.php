<?php

use Livewire\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Rule;

new class extends Component
{
    use Toast;

    #[Rule('required|email')]
    public string $email = '';

    #[Rule('required|min:8')]
    public string $password = '';

    public bool $remember = false;

    /** Message shown above the login card when rate limited (stays on page, no redirect). */
    public string $rateLimitMessage = '';

    // Redirect if already authenticated - handled by guest middleware
    public function mount(): void
    {
        // Guest middleware redirects authenticated users automatically
    }

    // Login the user (rate limited: 5 attempts per minute per IP on form submit only)
    public function login(): void
    {
        $key = 'login:' . request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->rateLimitMessage = 'Maximum attempt reached. Refresh the page and try again after 1 minute';
            return;
        }
        $this->rateLimitMessage = '';

        $credentials = $this->validate();

        if (Auth::attempt($credentials, $this->remember)) {
            RateLimiter::clear($key);
            session()->regenerate();
            
            $user = auth()->user();
            $redirectTo = '/';
            if ($user->isAdmin()) {
                $redirectTo = '/admin/dashboard';
            } elseif ($user->isOwner()) {
                $redirectTo = '/dashboard';
            } elseif ($user->isTenant()) {
                $redirectTo = '/portal';
            }
            
            $this->success('Welcome back!', position: 'toast-bottom', redirectTo: $redirectTo);
        } else {
            RateLimiter::hit($key, 60);
            $this->error('Invalid email or password.', position: 'toast-bottom');
        }
    }
    
    public function getLayout(): string
    {
        return 'layouts.guest';
    }
};
?>

<div class="min-h-screen flex items-center justify-center bg-base-200 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        {{-- add the AppBrand component here --}}
        <div class="flex justify-center">
            <x-app-brand icon-width="w-12" text-size="text-5xl" tagline-size="text-[0.68rem]" one-color-logo="" />
        </div>
        <div>
            <h2 class="mt-10 text-center text-xl font-extrabold text-base-content">
                Sign in to your account
            </h2>
            {{-- <p class="mt-2 text-center text-sm text-base-content/70">
                Or
                <a href="/register" class="font-medium text-primary hover:text-primary-focus">
                    create a new account
                </a>
            </p> --}}
        </div>

        @if($rateLimitMessage)
            <div
                x-data="{ show: true }"
                x-init="setTimeout(() => show = false, 3500)"
                x-show="show"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="rounded-lg bg-error/10 border border-error/30 text-error px-4 py-3 text-center text-xs"
                role="alert"
            >
                {{ $rateLimitMessage }}
            </div>
        @endif

        <x-card class="bg-base-100 border border-base-content/10 shadow-lg">
            <x-form wire:submit="login" class="space-y-2">
                <x-input 
                    label="Email" 
                    wire:model="email" 
                    type="email" 
                    icon="o-envelope"
                    placeholder="your@email.com"
                    class="text-teal-800 dark:text-teal-200 rounded-2xl py-6"
                />

                <x-input 
                    label="Password" 
                    wire:model="password" 
                    type="password" 
                    icon="o-lock-closed"
                    placeholder="Enter your password"
                    class="text-teal-800 dark:text-teal-200 rounded-2xl py-6"
                />

                <div class="flex items-center justify-between">
                    <x-checkbox label="Remember me" wire:model="remember" />
                    <a href="#" class="text-sm text-teal-600 dark:text-teal-200 hover:text-teal-700 dark:hover:text-teal-300">
                        Forgot password?
                    </a>
                </div>

                <x-slot:actions>
                    <x-button 
                        label="Sign in" 
                        icon="o-arrow-right-on-rectangle" 
                        spinner="login" 
                        type="submit" 
                        class="rounded-full py-6 px-4 text-base btn-block bg-teal-600 dark:bg-teal-200 text-white dark:text-teal-900 hover:bg-teal-700 dark:hover:bg-teal-300 border-0" 
                    />
                </x-slot:actions>
            </x-form>
        </x-card>

        <div class="text-center">
            <p class="text-sm text-base-content/70">
                Don't have an account?
                <a href="/register" class="font-medium text-teal-600 dark:text-teal-200 hover:text-teal-700 dark:hover:text-teal-300">
                    Register here
                </a>
            </p>
        </div>
    </div>
</div>
