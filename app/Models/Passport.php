<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Passport extends Model
{
    protected $fillable = [
        'hr_profile_id', 'passport_number', 'passport_type',
        'issue_date', 'expiry_date', 'issue_place',
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
