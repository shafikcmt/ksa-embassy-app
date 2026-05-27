<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['agency_id', 'key', 'value'];

    public function agency(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public static function get(string $key, ?int $agencyId = null, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->where('agency_id', $agencyId)->first();
        return $setting ? $setting->value : $default;
    }

    public static function set(string $key, mixed $value, ?int $agencyId = null): void
    {
        static::updateOrCreate(
            ['key' => $key, 'agency_id' => $agencyId],
            ['value' => $value]
        );
    }
}
