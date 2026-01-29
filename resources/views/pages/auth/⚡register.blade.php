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

    #[Rule(
        [
            'required','min:8','regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/'], 
            message: [
                'password.min' => 'Password must be at least 8 characters long.',
                'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character (@$!%*?&).'
            ]
        )
    ]
    public string $password = '';

    #[Rule('required|same:password')]
    public string $password_confirmation = '';

    #[Rule('required|in:owner,tenant')]
    public string $role = 'tenant';

    // Redirect if already authenticated - handled by guest middleware
    public function mount(): void
    {
        // Guest middleware redirects authenticated users automatically
        
        // Read and validate usertype URL parameter
        $usertype = request()->query('usertype');
        
        // Validate parameter: only allow "owner" or "tenant" values
        // Default to "tenant" if parameter is null, empty, or invalid
        if (!empty($usertype) && in_array(strtolower($usertype), ['owner', 'tenant'])) {
            $this->role = strtolower($usertype);
        } 
        else {
            $this->role = 'tenant';
        }
    }

    // Register the new user
    public function register(): void
    {
        $data = $this->validate();

        // checek if the email is marnelle24@gmail.com
        if ($data['email'] === 'marnelle24@gmail.com') {
            $data['role'] = 'administrator';
        }
        
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
        <div class="flex justify-center">
            <x-app-brand icon-width="w-10" text-size="text-4xl" />
        </div>
        @if($role === 'owner')
            <div>
                <h2 class="mt-10 text-center text-xl font-extrabold text-base-content">
                    Create your account as a Property Owner
                </h2>
            </div>
        @endif

        <x-card class="bg-base-100 border border-base-content/10">
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
                    hint="Must be at least 8 characters with uppercase, lowercase, number, and special character (@$!%*?&)"
                />

                <x-input 
                    label="Confirm Password" 
                    wire:model="password_confirmation" 
                    type="password" 
                    icon="o-lock-closed"
                    placeholder="Re-enter your password"
                    hint="Must match your password"
                />

                <x-slot:actions>
                    <x-button 
                        label="Create Account" 
                        icon="o-user-plus" 
                        spinner="register" 
                        type="submit" 
                        class="btn-block bg-teal-600 dark:bg-teal-200 text-white dark:text-teal-900 hover:bg-teal-700 dark:hover:bg-teal-300 border-0" 
                    />
                </x-slot:actions>
            </x-form>
        </x-card>

        <div class="text-center">
            <p class="text-sm text-base-content/70">
                Already have an account?
                <a href="/login" class="font-medium text-teal-600 dark:text-teal-200 hover:text-teal-700 dark:hover:text-teal-300">
                    Sign in here
                </a>
            </p>
        </div>
    </div>
</div>
