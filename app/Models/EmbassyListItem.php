<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmbassyListItem extends Model
{
    protected $fillable = [
        'embassy_list_id', 'agency_id', 'hr_profile_id', 'agent_id',
        'category', 'serial_no', 'sort_order',
        'snapshot_agent_name', 'snapshot_candidate_name', 'snapshot_candidate_name_ar',
        'snapshot_passport_no', 'snapshot_visa_no',
        'snapshot_profession_en', 'snapshot_profession_ar',
        'snapshot_sponsor_name', 'snapshot_sponsor_id', 'snapshot_nationality',
    ];

    public function embassyList(): BelongsTo
    {
        return $this->belongsTo(EmbassyList::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function hrProfile(): BelongsTo
    {
        return $this->belongsTo(HrProfile::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            'new'          => 'New',
            'restamping'   => 'Re-stamping',
            'cancellation' => 'Cancellation',
            default        => ucfirst($this->category),
        };
    }
}
