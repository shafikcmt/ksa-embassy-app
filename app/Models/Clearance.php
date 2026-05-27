<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Clearance extends Model
{
    protected $fillable = [
        'hr_profile_id', 'police_clearance_number',
        'clearance_issue_date', 'clearance_expiry_date', 'clearance_country',
        'license_type', 'pc_qr_code', 'fingerprint',
        'medical_fit', 'medical_date', 'medical_center',
    ];

    protected $casts = [
        'clearance_issue_date'   => 'date',
        'clearance_expiry_date'  => 'date',
        'medical_date'           => 'date',
        'medical_fit'            => 'boolean',
    ];

    public function hrProfile(): BelongsTo
    {
        return $this->belongsTo(HrProfile::class);
    }
}
