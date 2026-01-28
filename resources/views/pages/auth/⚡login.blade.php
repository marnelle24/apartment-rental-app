<?php

use Livewire\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Rule;

new class extends Component
{
    use Toast;

    #[Rule('required|email')]
    public string $email = '';

    #[Rule('required|min:8')]
    public string $password = '';

    public bool $remember = false;

    // Redirect if already authenticated - handled by guest middleware
    public function mount(): void
    {
        // Guest middleware redirects authenticated users automatically
    }

    // Login the user
    public function login(): void
    {
        $credentials = $this->validate();

        if (Auth::attempt($credentials, $this->remember)) {
            session()->regenerate();
            
            $user = auth()->user();
            $redirectTo = '/';
            if ($user->isAdmin()) {
                $redirectTo = '/admin/dashboard';
            } elseif ($user->isOwner()) {
                $redirectTo = '/dashboard';
            }
            
            $this->success('Welcome back!', position: 'toast-bottom', redirectTo: $redirectTo);
        } else {
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
            <x-app-brand icon-width="w-10" text-size="text-4xl" />
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

        <x-card class="bg-base-100 border border-base-content/10">
            <x-form wire:submit="login" class="space-y-2">
                <x-input 
                    label="Email" 
                    wire:model="email" 
                    type="email" 
                    icon="o-envelope"
                    placeholder="your@email.com"
                    hint="Enter your email address"
                />

                <x-input 
                    label="Password" 
                    wire:model="password" 
                    type="password" 
                    icon="o-lock-closed"
                    placeholder="Enter your password"
                    hint="Minimum 8 characters"
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
                        class="btn-block bg-teal-600 dark:bg-teal-200 text-white dark:text-teal-900 hover:bg-teal-700 dark:hover:bg-teal-300 border-0" 
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
