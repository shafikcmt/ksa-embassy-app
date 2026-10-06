<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * ERP MOFA application log entry (E1 operational tracker).
 *
 * payment_method is a categorical tag only (no amount / no money math).
 */
class MofaEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'hr_profile_id', 'father_name', 'mother_name', 'date_of_birth', 'issue_date', 'expiry_date', 'mofa_issue_date', 'mofa_expiry_date', 'remarks', 'passport_number', 'visa_number', 'reference',
        'agency_id', 'mofa_date', 'mofa_number', 'visa_serial', 'id_number',
        'full_name', 'passport_no', 'reference_name',
        'payment_method', 'whatsapp_number', 'payment_note',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'mofa_date' => 'date',
        'date_of_birth' => 'date', 'issue_date' => 'date', 'expiry_date' => 'date',
        'mofa_issue_date' => 'date', 'mofa_expiry_date' => 'date',
    ];

    /** A MOFA is valid for this many calendar days from its MOFA Date. */
    public const MOFA_VALIDITY_DAYS = 90;

    /** Passport validity choices (years) offered by the form; UI helper only, not stored. */
    public const PASSPORT_VALIDITY_YEARS = [10, 5];

    public const DEFAULT_PASSPORT_VALIDITY = 10;

    /** Left Day counts down to MOFA expiry by the Dhaka calendar day (the app itself stays UTC). */
    public const LEFT_DAY_TIMEZONE = 'Asia/Dhaka';

    /** MOFA Expiry for a MOFA Date (Y-m-d): exactly MOFA_VALIDITY_DAYS calendar days later. */
    public static function mofaExpiryFor(string $mofaDate): string
    {
        return Carbon::createFromFormat('!Y-m-d', $mofaDate)->addDays(self::MOFA_VALIDITY_DAYS)->format('Y-m-d');
    }

    /**
     * Display-only value for the list/CSV/print "M-Issu.Date" column: newer entries
     * have no MOFA Issue Date (the form dropped it), so fall back to MOFA Date.
     * Never persisted.
     */
    public function displayMofaIssueDate(): ?\DateTimeInterface
    {
        return $this->mofa_issue_date ?? $this->mofa_date;
    }

    /** Categorical payment tags (value => label). No monetary meaning. */
    public const PAYMENT_METHODS = [
        'company_account' => 'Company Account',
        'card_payment' => 'Card Payment',
        'no_payment' => 'No Payment',
    ];

    protected static function booted(): void
    {
        // Deleting a MOFA entry keeps its Double MOFA / Stamping / BMET / Delivery
        // rows (payments, history) and only drops their link back to it.
        static::deleted(fn (MofaEntry $entry) => app(\App\Services\MofaSyncService::class)->unlink($entry));
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
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

    /**
     * A passport may have several MOFA entries; "latest" is the newest MOFA Date,
     * then the newest id. Entries without a MOFA Date always sort last (explicit,
     * so MySQL and SQLite agree).
     */
    public function scopeLatestMofa($query)
    {
        return $query->orderByRaw('mofa_date IS NULL')->orderByDesc('mofa_date')->orderByDesc('id');
    }

    public function paymentMethodLabel(): ?string
    {
        return $this->payment_method
            ? (self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method)
            : null;
    }

    public function hrProfile(): BelongsTo
    {
        return $this->belongsTo(HrProfile::class);
    }

    public function getPassportNumberAttribute()
    {
        return $this->passport_no;
    }

    public function setPassportNumberAttribute($value): void
    {
        $this->attributes['passport_no'] = strtoupper(trim($value));
    }

    public function getVisaNumberAttribute()
    {
        return $this->visa_serial;
    }

    public function setVisaNumberAttribute($value): void
    {
        $this->attributes['visa_serial'] = $value;
    }

    public function getReferenceAttribute()
    {
        return $this->reference_name;
    }

    public function setReferenceAttribute($value): void
    {
        $this->attributes['reference_name'] = $value;
    }

    // Virtual values stay current without stale persisted age/status columns.
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? today()->year - $this->date_of_birth->year : null;
    }

    // Days from today's Dhaka calendar date to MOFA expiry; negative once expired.
    // Both sides are bare dates, so the difference is always whole days.
    public function getLeftDayAttribute(): ?int
    {
        if (! $this->mofa_expiry_date) {
            return null;
        }
        $today = Carbon::createFromFormat('!Y-m-d', Carbon::now(self::LEFT_DAY_TIMEZONE)->format('Y-m-d'));

        return (int) $today->diffInDays(Carbon::createFromFormat('!Y-m-d', $this->mofa_expiry_date->format('Y-m-d')), false);
    }

    public function getStatusAttribute(): string
    {
        if (! $this->mofa_expiry_date) {
            return 'processing';
        }
        if ($this->mofa_expiry_date->lt(today())) {
            return 'expired';
        }

        return $this->mofa_expiry_date->lt(today()->addDays(30)) ? 'expiring' : 'active';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status];
    }

    public const STATUSES = ['active' => 'Active', 'expiring' => 'Expiring Soon', 'expired' => 'Expired', 'processing' => 'Processing'];
}
