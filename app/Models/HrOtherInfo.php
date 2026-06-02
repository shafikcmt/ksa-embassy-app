<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrOtherInfo extends Model
{
    protected $fillable = [
        'hr_profile_id', 'contract_period', 'duration_stay_en', 'duration_stay_ar',
        'salary', 'work_city', 'destination_city',
        'employer_name', 'employer_phone',
        'relationship', 'carrier', 'payment_mode',
        'arrival_date', 'arrival_date_ar', 'departure_date', 'departure_date_ar', 'remarks',
        'business_address_en', 'business_address_ar', 'kingdom_address_en', 'kingdom_address_ar',
    ];

    protected $casts = [
        'salary'         => 'decimal:2',
        'arrival_date'   => 'date',
        'departure_date' => 'date',
    ];

    public function hrProfile(): BelongsTo
    {
        return $this->belongsTo(HrProfile::class);
    }
}
