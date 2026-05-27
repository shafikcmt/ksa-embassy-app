<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'price', 'max_hr', 'max_users', 'max_agents',
        'max_embassy_lists_monthly', 'max_pdf_monthly', 'storage_limit_mb',
        'duration_days', 'is_active', 'features', 'description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'features' => 'array',
    ];

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
