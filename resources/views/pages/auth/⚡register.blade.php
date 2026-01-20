<?php

use Livewire\Component;
use Mary\Traits\Toast;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Rule;

new class extends Component
{
    use Toast;

    #[Rule('required|min:2|max:255')]
    public string $name = '';

    #[Rule('required|email|unique:users,email')]
    public string $email = '';

    #[Rule('required|min:8')]
    public string $password = '';

    #[Rule('required|same:password')]
    public string $password_confirmation = '';

    #[Rule('required|in:admin,owner,tenant')]
    public string $role = 'owner';

    // Redirect if already authenticated - handled by guest middleware
    public function mount(): void
    {
        // Guest middleware redirects authenticated users automatically
    }

    // Register the new user
    public function register(): void
    {
        $data = $this->validate();
        
        // Hash the password
        $data['password'] = Hash::make($data['password']);
        
        // Remove password_confirmation as it's not needed in the database
        unset($data['password_confirmation']);

        // Create the user
        $user = User::create($data);

        // Log the user in
        Auth::login($user);

        session()->regenerate();
        
        $redirectTo = '/';
        if ($user->isAdmin()) {
            $redirectTo = '/admin/dashboard';
        } elseif ($user->isOwner()) {
            $redirectTo = '/dashboard';
        }
        
        $this->success('Account created successfully!', position: 'toast-bottom', redirectTo: $redirectTo);
    }
    
    public function getLayout(): string
    {
        return 'layouts.guest';
    }
};
?>

<div class="min-h-screen flex items-center justify-center bg-base-200 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-base-content">
                Create your account
            </h2>
            <p class="mt-2 text-center text-sm text-base-content/70">
                Or
                <a href="/login" class="font-medium text-primary hover:text-primary-focus">
                    sign in to your existing account
                </a>
            </p>
        </div>

        <x-card class="bg-base-100">
            <x-form wire:submit="register">
                <x-input 
                    label="Full Name" 
                    wire:model="name" 
                    icon="o-user"
                    placeholder="John Doe"
                    hint="Enter your full name"
                />

                <x-input 
                    label="Email" 
                    wire:model="email" 
                    type="email" 
                    icon="o-envelope"
                    placeholder="your@email.com"
                    hint="We'll never share your email"
                />

                <x-input 
                    label="Password" 
                    wire:model="password" 
                    type="password" 
                    icon="o-lock-closed"
                    placeholder="Minimum 8 characters"
                    hint="Must be at least 8 characters"
                />

                <x-input 
                    label="Confirm Password" 
                    wire:model="password_confirmation" 
                    type="password" 
                    icon="o-lock-closed"
                    placeholder="Re-enter your password"
                    hint="Must match your password"
                />

                <x-select 
                    label="Account Type" 
                    wire:model="role" 
                    :options="[
                        ['id' => 'owner', 'name' => 'Property Owner'],
                        ['id' => 'tenant', 'name' => 'Tenant'],
                        ['id' => 'admin', 'name' => 'Administrator'],
                    ]" 
                    icon="o-user-circle"
                    hint="Select your account type"
                />

                <x-slot:actions>
                    <x-button 
                        label="Create Account" 
                        icon="o-user-plus" 
                        spinner="register" 
                        type="submit" 
                        class="btn-primary btn-block" 
                    />
                </x-slot:actions>
            </x-form>
        </x-card>

        <div class="text-center">
            <p class="text-sm text-base-content/70">
                Already have an account?
                <a href="/login" class="font-medium text-primary hover:text-primary-focus">
                    Sign in here
                </a>
            </p>
        </div>
    </div>
</div>
