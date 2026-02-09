<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'stripe_price_id',
        'stripe_annual_price_id',
        'price',
        'annual_price',
        'apartment_limit',
        'tenant_limit',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'annual_price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Users subscribed to this plan.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if this plan has unlimited apartments (0 = unlimited).
     */
    public function hasUnlimitedApartments(): bool
    {
        return $this->apartment_limit === 0;
    }

    /**
     * Check if this plan has unlimited tenants (0 = unlimited).
     */
    public function hasUnlimitedTenants(): bool
    {
        return $this->tenant_limit === 0;
    }

    /**
     * Check if this is a free plan.
     */
    public function isFree(): bool
    {
        return $this->slug === 'free';
    }

    /**
     * Get the active plans ordered by sort_order.
     */
    public static function activePlans()
    {
        return static::where('is_active', true)->orderBy('sort_order')->get();
    }

    /**
     * Get the active paid plans (excludes free tier).
     */
    public static function activePaidPlans()
    {
        return static::where('is_active', true)
            ->where('slug', '!=', 'free')
            ->orderBy('sort_order')
            ->get();
    }
}
