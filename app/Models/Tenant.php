<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'apartment_id',
        'owner_id',
        'user_id',
        'name',
        'email',
        'phone',
        'emergency_contact',
        'emergency_phone',
        'move_in_date',
        'lease_start_date',
        'lease_end_date',
        'monthly_rent',
        'deposit_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'move_in_date' => 'date',
        'lease_start_date' => 'date',
        'lease_end_date' => 'date',
        'monthly_rent' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
    ];

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
