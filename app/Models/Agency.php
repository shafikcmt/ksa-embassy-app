<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Agency extends Model
{
    protected $fillable = [
        'name', 'owner_name', 'slug', 'license_number', 'rl_number',
        'address', 'phone', 'email', 'logo', 'print_logo',
        'license_expiry_date', 'status', 'notes',
    ];

    protected $casts = [
        'license_expiry_date' => 'date',
        'print_logo' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Agency $agency) {
            if (empty($agency->slug)) {
                $agency->slug = Str::slug($agency->name) . '-' . Str::random(5);
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * The agency's primary admin user (login account). Falls back to the first
     * user if no agency_admin role is assigned. Used by the Super Admin agency
     * profile screens to view/edit the agency's login email & password.
     */
    public function adminUser(): ?User
    {
        return $this->users()->whereHas('roles', fn ($q) => $q->where('name', 'agency_admin'))->first()
            ?? $this->users()->first();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', ['active', 'trial'])
            ->latest();
    }

    public function notices(): HasMany
    {
        return $this->hasMany(Notice::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    public function hrProfiles(): HasMany
    {
        return $this->hasMany(HrProfile::class);
    }

    public function embassyLists(): HasMany
    {
        return $this->hasMany(EmbassyList::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    public function isLicenseExpiringSoon(int $days = 30): bool
    {
        return $this->license_expiry_date
            && $this->license_expiry_date->diffInDays(now()) <= $days
            && $this->license_expiry_date->isFuture();
    }
}
