<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsurePlUnlocked;
use App\Models\ErpSetting;
use App\Services\PdfGeneratorService;
use App\Services\ProfitLossService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ERP Profit / Loss (E5) — the single most sensitive screen. OWNER-ONLY.
 *
 * Every action is admin-only (abort_unless isAgencyAdmin) — "owner" maps to the
 * agency admin, the only owner concept in the schema. On top of that, the data
 * route + exports sit behind the `pl-unlocked` middleware (session unlock with a
 * 15-min absolute TTL). The unlock/lock actions manage that session flag.
 *
 * All numbers come from ProfitLossService (read-only, cash basis); this
 * controller performs no money math.
 */
class ProfitLossController extends Controller
{
    /** GET — the code-entry screen (reachable while locked; not behind pl-unlocked). */
    public function unlockForm(Request $request)
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);

        $settings = ErpSetting::forAgency(auth()->user()->agency_id)->first();

        // Already accessible (no code / visible-to-all / still unlocked) → skip the form.
        if (EnsurePlUnlocked::isAccessible($settings, $request)) {
            return redirect()->route('erp.profit-loss');
        }

        return view('erp.profit-loss.unlock');
    }

    /** POST — verify the code (throttled at the route) and open a session unlock. */
    public function unlock(Request $request)
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);

        $request->validate(['code' => ['required', 'string']]);

        $settings = ErpSetting::forAgency(auth()->user()->agency_id)->first();

        if (! $settings || ! $settings->verifyCode($request->input('code'))) {
            return back()->with('error', 'Incorrect security code.');
        }

        $request->session()->put(
            EnsurePlUnlocked::SESSION_KEY,
            now()->addSeconds(EnsurePlUnlocked::TTL_SECONDS)->timestamp
        );

        return redirect()->route('erp.profit-loss')->with('success', 'Profit/Loss unlocked.');
    }

    /** POST — end the unlock immediately. */
    public function lock(Request $request)
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);

        $request->session()->forget(EnsurePlUnlocked::SESSION_KEY);

        return redirect()->route('erp.profit-loss.unlock')->with('success', 'Profit/Loss locked.');
    }

    /** GET — the P/L figures (behind pl-unlocked). */
    public function index(Request $request, ProfitLossService $pl)
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);

        [$from, $to, $period] = $this->period($request);
        $summary = $pl->summary(auth()->user()->agency_id, $from, $to);

        return view('erp.profit-loss.index', [
            'summary' => $summary,
            'period'  => $period,
            'from'    => $from,
            'to'      => $to,
            'unlockedUntil' => (int) $request->session()->get(EnsurePlUnlocked::SESSION_KEY, 0),
        ]);
    }

    public function exportPdf(Request $request, ProfitLossService $pl, PdfGeneratorService $pdf)
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);

        [$from, $to, $period] = $this->period($request);
        $summary = $pl->summary(auth()->user()->agency_id, $from, $to);

        return $pdf->generateFromView('erp.profit-loss.pdf', [
            'summary'   => $summary,
            'period'    => $period,
            'agency'    => auth()->user()->agency,
            'generated' => now(),
        ], 'profit-loss-' . now()->format('Y-m-d'));
    }

    public function exportCsv(Request $request, ProfitLossService $pl): StreamedResponse
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);

        [$from, $to, $period] = $this->period($request);
        $s = $pl->summary(auth()->user()->agency_id, $from, $to);

        $filename = 'profit-loss-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($s, $period) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Profit / Loss (cash basis)']);
            fputcsv($out, ['Period', $period === 'custom' ? (($s['from'] ?: 'begin') . ' to ' . ($s['to'] ?: 'today')) : ucfirst($period)]);
            fputcsv($out, []);
            fputcsv($out, ['Revenue (collected)', number_format($s['revenue'], 2, '.', '')]);
            fputcsv($out, ['  Cost — Expenses', number_format($s['expenseCost'], 2, '.', '')]);
            fputcsv($out, ['  Cost — Agent payouts', number_format($s['agentPayoutCost'], 2, '.', '')]);
            fputcsv($out, ['Total Cost', number_format($s['totalCost'], 2, '.', '')]);
            fputcsv($out, ['PROFIT', number_format($s['profit'], 2, '.', '')]);
            fputcsv($out, []);
            fputcsv($out, ['Opening Balance (context, not in profit)', number_format($s['openingBalance'], 2, '.', '')]);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Resolve the reporting period. Presets: all (default), month, custom.
     *
     * @return array{0: ?string, 1: ?string, 2: string}
     */
    private function period(Request $request): array
    {
        $period = in_array($request->query('period'), ['all', 'month', 'custom'], true)
            ? $request->query('period') : 'all';

        if ($period === 'month') {
            return [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString(), 'month'];
        }

        if ($period === 'custom') {
            return [$request->query('from') ?: null, $request->query('to') ?: null, 'custom'];
        }

        return [null, null, 'all'];
    }
}
