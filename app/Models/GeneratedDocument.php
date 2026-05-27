<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedDocument extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'agency_id', 'hr_profile_id', 'embassy_list_id',
        'document_type', 'action', 'generated_by', 'ip_address',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function hrProfile(): BelongsTo
    {
        return $this->belongsTo(HrProfile::class);
    }

    public function embassyList(): BelongsTo
    {
        return $this->belongsTo(EmbassyList::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public static function log(
        string $type,
        string $action,
        ?HrProfile $hr = null,
        ?EmbassyList $embassyList = null
    ): void {
        $user = auth()->user();
        static::create([
            'agency_id'       => $user?->agency_id,
            'hr_profile_id'   => $hr?->id,
            'embassy_list_id' => $embassyList?->id,
            'document_type'   => $type,
            'action'          => $action,
            'generated_by'    => $user?->id,
            'ip_address'      => request()->ip(),
        ]);
    }
}
