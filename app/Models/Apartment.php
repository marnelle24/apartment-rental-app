<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Apartment extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'location_id',
        'name',
        'address',
        'unit_number',
        'monthly_rent',
        'bedrooms',
        'bathrooms',
        'square_meters',
        'status',
        'description',
        'images',
        'amenities',
    ];

    protected $casts = [
        'images' => 'array',
        'amenities' => 'array',
        'monthly_rent' => 'decimal:2',
        'square_meters' => 'decimal:2',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function rentPayments(): HasMany
    {
        return $this->hasMany(RentPayment::class);
    }

    public function utilityBills(): HasMany
    {
        return $this->hasMany(UtilityBill::class);
    }
}
