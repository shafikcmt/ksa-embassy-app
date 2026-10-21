<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseHead extends Model
{
    protected $fillable = ['agency_id', 'name', 'code', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean', 'is_system' => 'boolean', 'sort_order' => 'integer'];

    /** Code of the default head whose expenses also record a dollar amount. */
    public const DOLLAR_CODE = 'enjaz_dollar';

    // Stable legacy keys retain CSV compatibility. Agent payouts belong to Agent Khata.
    public const DEFAULTS = [
        'manpower' => 'Manpower Cost',
        'salary' => 'Salary',
        'staff_advance' => 'Staff Advance',
        'office_rent' => 'Office Rent',
        'utilities' => 'Utility Bill',
        'travel' => 'Transport / Conveyance',
        'food_entertainment' => 'Food / Entertainment',
        'medical' => 'Medical Expense',
        'embassy_visa' => 'Embassy / Visa Processing',
        'government_fees' => 'BMET / Government Fee',
        'air_ticket' => 'Ticket / Travel Expense',
        'supplier_payment' => 'Supplier Payment',
        'office_supplies' => 'Office Supplies',
        'maintenance' => 'Maintenance / Repair',
        'printing_stationery' => 'Printing / Stationery',
        'marketing' => 'Marketing',
        'bank_charge' => 'Bank Charge',
        'enjaz_dollar' => 'Enjaz Dollar',
        'other' => 'Miscellaneous / Others',
    ];

    public static function createDefaults(int $agencyId): void
    {
        foreach (self::DEFAULTS as $code => $name) {
            self::firstOrCreate(['agency_id' => $agencyId, 'code' => $code], [
                'name' => $name, 'is_active' => true,
                'sort_order' => (array_search($code, array_keys(self::DEFAULTS), true) + 1) * 10,
            ]);
        }
        $archive = self::firstOrNew(['agency_id' => $agencyId, 'code' => 'payment_voucher']);
        if (! $archive->exists) {
            $archive->fill(['name' => 'Payment Voucher', 'is_active' => false, 'sort_order' => 1000]);
            $archive->is_system = true;
            $archive->save();
        }
    }

    /** Heads that buy dollars (Enjaz Dollar, or a custom head with "dollar" in its name) ask for the dollar amount too. */
    public function tracksDollar(): bool
    {
        return $this->code === self::DOLLAR_CODE || stripos((string) $this->name, 'dollar') !== false;
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function paymentVouchers(): HasMany
    {
        return $this->hasMany(PaymentVoucher::class)->withTrashed();
    }

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name')->orderBy('id');
    }
}
