<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visa extends Model
{
    protected $fillable = [
        'hr_profile_id', 'visa_number', 'visa_type',
        'issue_date', 'expiry_date', 'issue_place', 'issue_place_ar',
        'sponsor_name', 'sponsor_name_ar', 'sponsor_id', 'border_number',
        'profession_en', 'profession_ar', 'qualification_en', 'qualification_ar',
        'travel_purpose', 'musaned_no', 'wakala_no',
    ];

    protected $casts = [
        'issue_date'  => 'date',
        'expiry_date' => 'date',
    ];

    public function hrProfile(): BelongsTo
    {
        return $this->belongsTo(HrProfile::class);
    }
}
