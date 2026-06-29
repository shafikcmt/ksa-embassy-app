<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'price', 'currency', 'max_hr', 'max_users', 'max_agents',
        'max_embassy_lists_monthly', 'max_pdf_monthly', 'storage_limit_mb',
        'duration_days', 'is_active', 'features', 'description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'features' => 'array',
    ];

    /** Map of currency code → display symbol. */
    public const CURRENCY_SYMBOLS = ['BDT' => '৳', 'USD' => '$', 'SAR' => 'SAR '];

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Display symbol for this plan's currency (defaults to Taka). */
    public function currencySymbol(): string
    {
        return self::CURRENCY_SYMBOLS[$this->currency] ?? ($this->currency ? $this->currency . ' ' : '৳');
    }

    /**
     * Human-friendly price label, e.g. "৳2,000/mo" or "Free".
     * Pass a suffix such as "/mo" or "" (none).
     */
    public function priceLabel(string $suffix = '/mo'): string
    {
        if ((float) $this->price <= 0) {
            return 'Free';
        }

        return $this->currencySymbol() . number_format($this->price, 0) . $suffix;
    }
}
