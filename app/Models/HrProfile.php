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
        'nationality', 'previous_nationality', 'mofa_new', 'mofa_old',
        'date_of_birth', 'gender', 'sect',
        'religion', 'marital_status', 'occupation', 'phone', 'home_address', 'email',
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

    public function documentReadiness(): array
    {
        $missing = [];

        if (empty($this->full_name_en))   $missing[] = 'Full Name (EN)';
        if (empty($this->date_of_birth))  $missing[] = 'Date of Birth';
        if (empty($this->nationality))    $missing[] = 'Nationality';
        if (empty($this->gender))         $missing[] = 'Gender';
        if (empty($this->religion))       $missing[] = 'Religion';

        if (!$this->passport || empty($this->passport->passport_number)) $missing[] = 'Passport Number';
        if (!$this->passport || empty($this->passport->issue_place))     $missing[] = 'Passport Issue Place';
        if (!$this->passport || empty($this->passport->issue_date))      $missing[] = 'Passport Issue Date';
        if (!$this->passport || empty($this->passport->expiry_date))     $missing[] = 'Passport Expiry Date';

        if (!$this->visa || empty($this->visa->visa_number))    $missing[] = 'Visa Number';
        if (!$this->visa || empty($this->visa->sponsor_name))   $missing[] = 'Sponsor Name';
        if (!$this->visa || empty($this->visa->sponsor_id))     $missing[] = 'Sponsor ID';
        if (!$this->visa || (empty($this->visa->profession_en) && empty($this->occupation))) $missing[] = 'Profession';
        if (!$this->visa || empty($this->visa->travel_purpose)) $missing[] = 'Travel Purpose';

        return ['ready' => empty($missing), 'missing' => $missing];
    }
}
