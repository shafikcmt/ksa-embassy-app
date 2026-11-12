<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Visa Stamping interface over the existing `stampings` log (no duplicate
 * records — same approach as BmetEntry over manpower_completions).
 *
 * Aliases: passport_number ↔ passport_no, stamping_date ↔ stamp_date.
 * Age and Left Day are computed on read (never stored, never stale):
 *   age      = YEAR(today) − YEAR(date_of_birth)   (reference-sheet formula)
 *   left_day = days from TODAY (Dhaka) to expiry_date — counts down daily, negative once expired
 */
class VisaStamping extends Stamping
{
    protected $table = 'stampings';

    protected $fillable = [
        'agency_id', 'mofa_entry_id', 'hr_profile_id', 'agent_id',
        'full_name', 'father_name', 'mother_name', 'passport_number', 'date_of_birth',
        'visa_number', 'id_number', 'mofa_number', 'mofa_date',
        'issued_visa_number', 'issued_date', 'expiry_date',
        'stamping_date', 'status', 'reference', 'remarks',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'stamp_date'    => 'date',
        'date_of_birth' => 'date',
        'mofa_date'     => 'date',
        'issued_date'   => 'date',
        'expiry_date'   => 'date',
    ];

    /** Left Day at or below this is flagged red. */
    /** A stamped visa is valid for this many days after its issue date (Expiry Date defaults to Issued + this). */
    public const VISA_VALID_DAYS = 90;

    public const LEFT_DAY_WARNING = 30;

    /** Badge colours (Tailwind) — Stamped green, Pending amber, Completed blue, Expired red, Rejected gray. */
    public const STATUS_TONES = [
        'pending'    => 'bg-amber-100 text-amber-800',
        'processing' => 'bg-purple-100 text-purple-800',
        'completed'  => 'bg-blue-100 text-blue-800',
        'stamped'    => 'bg-emerald-100 text-emerald-800',
        'expired'    => 'bg-red-100 text-red-800',
        'rejected'   => 'bg-gray-100 text-gray-700',
    ];

    public const STATUS_ICONS = [
        'pending'    => 'bi-hourglass-split',
        'processing' => 'bi-arrow-repeat',
        'completed'  => 'bi-check2-all',
        'stamped'    => 'bi-patch-check-fill',
        'expired'    => 'bi-calendar-x',
        'rejected'   => 'bi-x-octagon',
    ];

    protected static function booted(): void
    {
        // Keep passport numbers comparable (uniqueness, MOFA/HR matching).
        static::saving(function (VisaStamping $s) {
            if ($s->passport_no !== null) {
                $s->passport_no = strtoupper(trim($s->passport_no));
            }
        });
    }

    public function hrProfile(): BelongsTo
    {
        return $this->belongsTo(HrProfile::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    // ── Column aliases ────────────────────────────────────────────────────

    public function getPassportNumberAttribute(): ?string
    {
        return $this->passport_no;
    }

    public function setPassportNumberAttribute($value): void
    {
        $this->attributes['passport_no'] = strtoupper(trim((string) $value));
    }

    public function getStampingDateAttribute()
    {
        return $this->stamp_date;
    }

    public function setStampingDateAttribute($value): void
    {
        $this->setAttribute('stamp_date', $value);
    }

    // ── Computed values ───────────────────────────────────────────────────

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? max(0, today()->year - $this->date_of_birth->year) : null;
    }

    /**
     * Days left on the stamped visa, counted from today's Dhaka calendar date to the
     * expiry date (Issued + 90 days when no expiry is stored). Decreases by one each
     * day; negative once expired. Same rule as MofaEntry::left_day.
     */
    public function getLeftDayAttribute(): ?int
    {
        $expiry = $this->expiry_date ?? $this->issued_date?->copy()->addDays(self::VISA_VALID_DAYS);
        if (! $expiry) {
            return null;
        }
        $today = Carbon::createFromFormat('!Y-m-d', Carbon::now(MofaEntry::LEFT_DAY_TIMEZONE)->format('Y-m-d'));

        return (int) $today->diffInDays(Carbon::createFromFormat('!Y-m-d', $expiry->format('Y-m-d')), false);
    }

    public function leftDayIsLow(): bool
    {
        return $this->left_day !== null && $this->left_day < self::LEFT_DAY_WARNING;
    }

    public function statusTone(): string
    {
        return self::STATUS_TONES[$this->status] ?? 'bg-gray-100 text-gray-700';
    }

    public function statusIcon(): string
    {
        return self::STATUS_ICONS[$this->status] ?? 'bi-circle';
    }
}
