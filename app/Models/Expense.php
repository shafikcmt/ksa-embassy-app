<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ERP Expense (E3). Agency money-OUT log — simple, no ledger/cache.
 *
 * `category` stores a key from CATEGORIES; `paid_via` a key from PAID_VIA.
 * There is intentionally no "Agent Commission" category (agent payouts belong
 * to Agent Khata) so the same outflow is never counted twice in P&L.
 */
class Expense extends Model
{
    protected $fillable = [
        'agency_id', 'expense_date', 'category', 'amount',
        'paid_via', 'note', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    public const CATEGORIES = [
        'office_rent'     => 'Office Rent',
        'salary'          => 'Salary',
        'utilities'       => 'Utilities',
        'government_fees' => 'Government Fees',
        'travel'          => 'Travel / Transport',
        'marketing'       => 'Marketing',
        'office_supplies' => 'Office Supplies',
        'bank_charge'     => 'Bank Charge',
        'other'           => 'Other',
    ];

    public const PAID_VIA = [
        'cash'  => 'Cash',
        'bank'  => 'Bank',
        'bkash' => 'bKash',
        'nagad' => 'Nagad',
        'card'  => 'Card',
    ];

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

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst((string) $this->category);
    }

    public function paidViaLabel(): ?string
    {
        return $this->paid_via ? (self::PAID_VIA[$this->paid_via] ?? ucfirst((string) $this->paid_via)) : null;
    }
}
