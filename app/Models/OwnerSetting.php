<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerSetting extends Model
{
    protected $fillable = [
        'user_id',
        'business_name',
        'contact_person',
        'mobile_number',
        'office_tel',
        'contact_email',
        'whatsapp',
        'instagram',
        'facebook',
        'website',
        'currency',
    ];

    protected $attributes = [
        'currency' => 'PHP',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Supported currency codes for dropdown (code => label).
     */
    public static function currencyOptions(): array
    {
        return [
            'PHP' => 'PHP - Philippine Peso',
            'USD' => 'USD - US Dollar',
            'SGD' => 'SGD - Singapore Dollar',
            'JPY' => 'JPY - Japanese Yen',
            'EUR' => 'EUR - Euro',
            'GBP' => 'GBP - British Pound',
            'AUD' => 'AUD - Australian Dollar',
            'CAD' => 'CAD - Canadian Dollar',
            'HKD' => 'HKD - Hong Kong Dollar',
            'AED' => 'AED - UAE Dirham',
        ];
    }
}
