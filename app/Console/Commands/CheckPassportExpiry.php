<?php

namespace App\Console\Commands;

use App\Mail\PassportExpiryMail;
use App\Models\Agency;
use App\Models\Passport;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckPassportExpiry extends Command
{
    protected $signature = 'app:check-passport-expiry';
    protected $description = 'Send passport expiry notifications to agencies';

    public function handle(): void
    {
        $canSendMail = !in_array(config('mail.default'), ['log', 'array', null]);

        $expiring = Passport::with(['hrProfile.agency'])
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays(30)])
            ->whereHas('hrProfile', fn($q) => $q->where('status', 'active'))
            ->get();

        // Group by agency
        $byAgency = $expiring->groupBy(fn($p) => $p->hrProfile->agency_id);

        foreach ($byAgency as $agencyId => $passports) {
            $wantsNotify = Setting::get('notify_passport_expiry', $agencyId, '1');
            if ($wantsNotify === '0') {
                continue;
            }

            $agency = $passports->first()->hrProfile->agency;
            $this->line("  Agency: {$agency->name} — {$passports->count()} passport(s) expiring within 30 days");

            if (!$canSendMail || !$agency->email) {
                continue;
            }

            try {
                Mail::to($agency->email)->send(new PassportExpiryMail($agency, $passports));
            } catch (\Throwable $e) {
                $this->warn("Mail failed for {$agency->name}: {$e->getMessage()}");
            }
        }

        $this->info('Passport expiry check complete.');
    }
}
