<?php

namespace App\Console\Commands;

use App\Mail\LicenseExpiringMail;
use App\Models\Agency;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckLicenseExpiry extends Command
{
    protected $signature = 'app:check-license-expiry';
    protected $description = 'Send license expiry notifications to agencies';

    public function handle(): void
    {
        $canSendMail = !in_array(config('mail.default'), ['log', 'array', null]);

        $agencies = Agency::whereNotNull('license_expiry_date')
            ->whereIn('status', ['active'])
            ->whereBetween('license_expiry_date', [now(), now()->addDays(14)])
            ->get();

        foreach ($agencies as $agency) {
            $days = (int) now()->diffInDays($agency->license_expiry_date, false);

            if (!in_array($days, [1, 3, 7, 14])) {
                continue;
            }

            $this->line("  License expiring in {$days}d: {$agency->name}");

            if (!$canSendMail || !$agency->email) {
                continue;
            }

            try {
                Mail::to($agency->email)->send(new LicenseExpiringMail($agency, $days));
            } catch (\Throwable $e) {
                $this->warn("Mail failed for {$agency->name}: {$e->getMessage()}");
            }
        }

        $this->info('License expiry check complete.');
    }
}
