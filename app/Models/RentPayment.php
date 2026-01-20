<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class RentPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'apartment_id',
        'amount',
        'payment_date',
        'due_date',
        'status',
        'payment_method',
        'reference_number',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'due_date' => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();

        // Auto-update status when model is retrieved
        static::retrieved(function (RentPayment $payment) {
            $payment->updateStatusIfNeeded();
        });
    }

    /**
     * Update status to overdue if due date has passed and payment is not paid
     */
    public function updateStatusIfNeeded(): void
    {
        if ($this->status !== 'paid' && $this->due_date && $this->due_date->isPast()) {
            $this->status = 'overdue';
            $this->saveQuietly(); // Save without triggering events
        }
    }

    /**
     * Scope to get overdue payments
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', '!=', 'paid')
            ->where('due_date', '<', Carbon::now());
    }

    /**
     * Scope to get pending payments
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get paid payments
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }
}
