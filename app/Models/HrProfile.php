<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HrProfile extends Model
{
    protected $fillable = [
        'agency_id', 'agent_id', 'file_number', 'full_name_en', 'full_name_ar',
        'father_name', 'mother_name', 'place_of_birth',
        'nationality', 'previous_nationality', 'date_of_birth', 'gender',
        'religion', 'marital_status', 'occupation', 'phone', 'email',
        'status', 'notes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function passport(): HasOne
    {
        return $this->hasOne(Passport::class);
    }

    public function visa(): HasOne
    {
        return $this->hasOne(Visa::class);
    }

    public function clearance(): HasOne
    {
        return $this->hasOne(Clearance::class);
    }

    public function otherInfo(): HasOne
    {
        return $this->hasOne(HrOtherInfo::class);
    }

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function embassyListItems(): HasMany
    {
        return $this->hasMany(EmbassyListItem::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
