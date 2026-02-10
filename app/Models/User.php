<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, Billable;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class);
    }

    // Apartment rental relationships
    public function apartments(): HasMany
    {
        return $this->hasMany(Apartment::class, 'owner_id');
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'owner_id');
    }

    /**
     * Tenant records linked to this user (when role is tenant).
     */
    public function tenantRecords(): HasMany
    {
        return $this->hasMany(Tenant::class, 'user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'owner_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadNotifications(): HasMany
    {
        return $this->hasMany(Notification::class)->unread();
    }

    public function taskComments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function ownerSetting(): HasOne
    {
        return $this->hasOne(OwnerSetting::class);
    }

    // Plan / Subscription helpers

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the effective plan for this owner.
     * Falls back to the free plan if no plan is assigned.
     */
    public function getEffectivePlan(): ?Plan
    {
        if ($this->plan_id && $this->plan) {
            return $this->plan;
        }

        return Plan::where('slug', 'free')->first();
    }

    /**
     * Check if this owner can add more apartments based on their plan limit.
     */
    public function canAddApartment(): bool
    {
        $plan = $this->getEffectivePlan();

        if (! $plan) {
            return true; // No plan configured, allow by default
        }

        if ($plan->hasUnlimitedApartments()) {
            return true;
        }

        return $this->apartments()->count() < $plan->apartment_limit;
    }

    /**
     * Check if this owner can add more tenants based on their plan limit.
     */
    public function canAddTenant(): bool
    {
        $plan = $this->getEffectivePlan();

        if (! $plan) {
            return true;
        }

        if ($plan->hasUnlimitedTenants()) {
            return true;
        }

        return $this->tenants()->count() < $plan->tenant_limit;
    }

    /**
     * Get the remaining apartment slots for this owner.
     */
    public function remainingApartmentSlots(): ?int
    {
        $plan = $this->getEffectivePlan();

        if (! $plan || $plan->hasUnlimitedApartments()) {
            return null; // Unlimited
        }

        return max(0, $plan->apartment_limit - $this->apartments()->count());
    }

    /**
     * Get the remaining tenant slots for this owner.
     */
    public function remainingTenantSlots(): ?int
    {
        $plan = $this->getEffectivePlan();

        if (! $plan || $plan->hasUnlimitedTenants()) {
            return null; // Unlimited
        }

        return max(0, $plan->tenant_limit - $this->tenants()->count());
    }

    // Role helpers
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isTenant(): bool
    {
        return $this->role === 'tenant';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
