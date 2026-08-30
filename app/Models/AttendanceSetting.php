<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSetting extends Model
{
    protected $fillable = [
        'agency_id',
        'office_start', 'office_end', 'grace_minutes', 'auto_absent_time',
        'half_day_after_minutes', 'overtime_after_minutes',
        'timezone', 'weekend_days',
        'alert_on_late', 'alert_on_absent', 'alert_on_checkin', 'alert_on_checkout',
    ];

    protected $casts = [
        'weekend_days'      => 'array',
        'alert_on_late'     => 'boolean',
        'alert_on_absent'   => 'boolean',
        'alert_on_checkin'  => 'boolean',
        'alert_on_checkout' => 'boolean',
    ];

    /** Supported timezones (label => IANA identifier) for the settings form. */
    public const TIMEZONES = [
        'Asia/Dhaka'   => 'Dhaka (BST)',
        'Asia/Riyadh'  => 'Riyadh (AST)',
        'Asia/Dubai'   => 'Dubai (GST)',
        'Asia/Qatar'   => 'Qatar (AST)',
        'Asia/Kuwait'  => 'Kuwait (AST)',
        'UTC'          => 'UTC',
    ];

    /** Weekday map (int => label), 0 = Sunday, matching Carbon dayOfWeek. */
    public const WEEKDAYS = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function timezoneLabel(): string
    {
        return self::TIMEZONES[$this->timezone] ?? (string) $this->timezone;
    }
}
