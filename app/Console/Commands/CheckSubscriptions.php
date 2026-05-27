<?php

namespace App\Console\Commands;

use App\Mail\PdfLimitWarningMail;
use App\Mail\SubscriptionExpiredMail;
use App\Mail\SubscriptionExpiringMail;
use App\Models\AuditLog;
use App\Models\GeneratedDocument;
use App\Models\Setting;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckSubscriptions extends Command
{
    protected $signature = 'app:check-subscriptions';
    protected $description = 'Mark expired subscriptions and send expiry notifications';

    private bool $canSendMail;

    public function handle(): void
    {
        $this->canSendMail = !in_array(config('mail.default'), ['log', 'array', null]);

        $this->markExpired();
        $this->notifyExpiring();
        $this->warnPdfLimit();

        $this->info('Subscription check complete.');
    }

    private function markExpired(): void
    {
        $expired = Subscription::whereIn('status', ['active', 'trial'])
            ->where('end_date', '<', now())
            ->with('agency.users')
            ->get();

        foreach ($expired as $sub) {
            $sub->update(['status' => 'expired']);

            AuditLog::create([
                'agency_id'      => $sub->agency_id,
                'user_id'        => null,
                'action'         => 'subscription_expired',
                'auditable_type' => Subscription::class,
                'auditable_id'   => $sub->id,
                'old_values'     => json_encode(['status' => 'active']),
                'new_values'     => json_encode(['status' => 'expired']),
                'ip_address'     => null,
            ]);

            $this->sendExpiredMail($sub);
            $this->line("  Expired: {$sub->agency->name} (sub #{$sub->id})");
        }
    }

    private function notifyExpiring(): void
    {
        $soon = Subscription::whereIn('status', ['active', 'trial'])
            ->whereBetween('end_date', [now(), now()->addDays(7)])
            ->with(['agency', 'plan'])
            ->get();

        foreach ($soon as $sub) {
            $days = $sub->daysRemaining();
            if (!in_array($days, [1, 3, 7])) {
                continue;
            }

            $wantsNotify = Setting::get('notify_subscription_expiry', $sub->agency_id, '1');
            if ($wantsNotify === '0') {
                continue;
            }

            $this->sendExpiringMail($sub, $days);
            $this->line("  Expiring in {$days}d: {$sub->agency->name}");
        }
    }

    private function warnPdfLimit(): void
    {
        $active = Subscription::whereIn('status', ['active', 'trial'])
            ->where('end_date', '>=', now())
            ->with(['agency', 'plan'])
            ->get();

        foreach ($active as $sub) {
            $maxPdf = $sub->plan->max_pdf_monthly ?? 0;
            if ($maxPdf >= 9999 || $maxPdf === 0) {
                continue;
            }

            $used = GeneratedDocument::where('agency_id', $sub->agency_id)
                ->where('action', 'download')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            $percent = (int) round(($used / $maxPdf) * 100);

            if ($percent >= 80) {
                $this->sendPdfWarningMail($sub, $percent, $used, $maxPdf);
                $this->line("  PDF warning {$percent}%: {$sub->agency->name}");
            }
        }
    }

    private function sendExpiredMail(Subscription $sub): void
    {
        if (!$this->canSendMail || !$sub->agency?->email) {
            return;
        }
        try {
            Mail::to($sub->agency->email)->send(new SubscriptionExpiredMail($sub->agency, $sub));
        } catch (\Throwable $e) {
            $this->warn("Mail failed for {$sub->agency->name}: {$e->getMessage()}");
        }
    }

    private function sendExpiringMail(Subscription $sub, int $days): void
    {
        if (!$this->canSendMail || !$sub->agency?->email) {
            return;
        }
        try {
            Mail::to($sub->agency->email)->send(new SubscriptionExpiringMail($sub->agency, $sub, $days));
        } catch (\Throwable $e) {
            $this->warn("Mail failed for {$sub->agency->name}: {$e->getMessage()}");
        }
    }

    private function sendPdfWarningMail(Subscription $sub, int $percent, int $used, int $limit): void
    {
        if (!$this->canSendMail || !$sub->agency?->email) {
            return;
        }
        try {
            Mail::to($sub->agency->email)->send(new PdfLimitWarningMail($sub->agency, $sub, $percent, $used, $limit));
        } catch (\Throwable $e) {
            $this->warn("Mail failed for {$sub->agency->name}: {$e->getMessage()}");
        }
    }
}
