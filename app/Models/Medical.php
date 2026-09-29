<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ERP medical-check log entry (E1 operational tracker, extended into the full
 * Medical Entry / Medical Summary record). Workflow status only — no money math.
 *
 * `age` is never mass-assigned: the saving hook derives it from date_of_birth
 * with the reference sheet's formula (current year − birth year).
 */
class Medical extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agency_id', 'hr_profile_id', 'full_name', 'father_name', 'passport_no',
        'date_of_birth', 'medical_center_name', 'country', 'medical_code',
        'medical_issue_date', 'medical_expire_date', 'medical_status',
        'mobile_no', 'reference', 'remarks',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'date_of_birth'       => 'date',
        'age'                 => 'integer',
        'medical_issue_date'  => 'date',
        'medical_expire_date' => 'date',
    ];

    public const MEDICAL_STATUSES = [
        'pending'      => 'Pending',
        'process'      => 'Process',
        'under_review' => 'Under Review',
        'fit'          => 'Fit',
        'unfit'        => 'Unfit',
        'expired'      => 'Expired',
    ];

    /** Screen badge colours (Tailwind). PDF colours live in App\Support\ErpPrintTheme. */
    public const STATUS_TONES = [
        'pending'      => 'bg-amber-100 text-amber-800',
        'process'      => 'bg-blue-100 text-blue-800',
        'under_review' => 'bg-purple-100 text-purple-800',
        'fit'          => 'bg-emerald-100 text-emerald-800',
        'unfit'        => 'bg-red-100 text-red-800',
        'expired'      => 'bg-gray-100 text-gray-700',
    ];

    /** Icon per status so the badge never relies on colour alone. */
    public const STATUS_ICONS = [
        'pending'      => 'bi-hourglass-split',
        'process'      => 'bi-arrow-repeat',
        'under_review' => 'bi-search',
        'fit'          => 'bi-check-circle-fill',
        'unfit'        => 'bi-x-circle-fill',
        'expired'      => 'bi-calendar-x',
    ];

    protected static function booted(): void
    {
        static::saving(function (Medical $medical) {
            $medical->age = self::ageFromDob($medical->date_of_birth);
        });
    }

    /** Reference-sheet age: YEAR(today) − YEAR(date_of_birth). */
    public static function ageFromDob(?CarbonInterface $dob): ?int
    {
        return $dob ? max(0, now()->year - $dob->year) : null;
    }

    /** Age as of today, so an entry saved last year still prints the right age. */
    public function currentAge(): ?int
    {
        return self::ageFromDob($this->date_of_birth) ?? $this->age;
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function hrProfile(): BelongsTo
    {
        return $this->belongsTo(HrProfile::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function statusLabel(): string
    {
        return self::MEDICAL_STATUSES[$this->medical_status] ?? ucfirst((string) $this->medical_status);
    }

    public function statusTone(): string
    {
        return self::STATUS_TONES[$this->medical_status] ?? 'bg-slate-100 text-slate-600';
    }

    public function statusIcon(): string
    {
        return self::STATUS_ICONS[$this->medical_status] ?? 'bi-circle';
    }
}
