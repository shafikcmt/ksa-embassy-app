<?php

use App\Http\Controllers\Erp\AgentKhataController;
use App\Http\Controllers\Erp\DashboardController;
use App\Http\Controllers\Erp\DeliveryController;
use App\Http\Controllers\Erp\DoubleMofaController;
use App\Http\Controllers\Erp\DueListController;
use App\Http\Controllers\Erp\ExpenseController;
use App\Http\Controllers\Erp\ManpowerController;
use App\Http\Controllers\Erp\MofaEntryController;
use App\Http\Controllers\Erp\ProfitLossController;
use App\Http\Controllers\Erp\ReportController;
use App\Http\Controllers\Erp\SettingsController;
use App\Http\Controllers\Erp\StampingController;
use Illuminate\Support\Facades\Route;

/*
 * ERP Suite routes — E0 (shell + settings).
 *
 * Prefixed /erp, named erp.*, and gated behind the same stack every agency
 * module uses: auth + agency-access (tenancy) + page-access:erp (per-staff
 * module gating). Later ERP phases (MOFA, Stamping, Delivery, ledgers, …) add
 * their routes inside this same group.
 */
Route::middleware(['auth', 'agency-access', 'page-access:erp'])
    ->prefix('erp')
    ->name('erp.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // ── E6b: Dashboard summary exports ────────────────────────────────────
        // Daily Summary PDF carries NO profit → admin-only (money-action
        // invariant) but not pl-gated. Monthly Summary PDF + Backup CSV carry the
        // owner-only profit + Starting/Ending balance, so they are admin-only AND
        // sit behind `pl-unlocked` — the SAME gate the P/L screen uses (reused,
        // not modified). Admin checks are also enforced in the controller.
        Route::get('/summary/daily/pdf', [DashboardController::class, 'dailySummaryPdf'])->name('summary.daily.pdf');
        Route::middleware('pl-unlocked')->group(function () {
            Route::get('/summary/monthly/pdf', [DashboardController::class, 'monthlySummaryPdf'])->name('summary.monthly.pdf');
            Route::get('/backup/csv', [DashboardController::class, 'backupCsv'])->name('backup.csv');
        });

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

        // ── E1 operational trackers (non-money logs) ──────────────────────────
        // Reads are open within the module; adding entries (store) requires an
        // active subscription, matching the HR/Embassy/Agents modules.
        Route::get('/mofa', [MofaEntryController::class, 'index'])->name('mofa');
        Route::get('/stamping', [StampingController::class, 'index'])->name('stamping');
        Route::get('/manpower', [ManpowerController::class, 'index'])->name('manpower');

        // ── E7a: per-module Print (full list PDF, read-only, staff-visible) ───
        // No admin guard and no active-subscription: printing shows only what the
        // staff member already sees on the module screen. Reuses PdfGeneratorService.
        Route::get('/mofa/print', [MofaEntryController::class, 'printPdf'])->name('mofa.print');

        // ── E7b: MOFA CSV export (staff-visible) + import (admin-only) ─────────
        // Export is read-only (matches Print). Import is admin-only (enforced in
        // the controller); the commit additionally requires an active subscription
        // (it creates records, like store). Preview is a dry run that writes nothing.
        Route::get('/mofa/export', [MofaEntryController::class, 'exportCsv'])->name('mofa.export');
        Route::get('/mofa/import', [MofaEntryController::class, 'importForm'])->name('mofa.import.form');
        Route::get('/mofa/import/template', [MofaEntryController::class, 'importTemplate'])->name('mofa.import.template');
        Route::post('/mofa/import/preview', [MofaEntryController::class, 'importPreview'])->name('mofa.import.preview');
        Route::get('/stamping/print', [StampingController::class, 'printPdf'])->name('stamping.print');
        Route::get('/manpower/print', [ManpowerController::class, 'printPdf'])->name('manpower.print');
        Route::get('/delivery/print', [DeliveryController::class, 'printPdf'])->name('delivery.print');
        Route::get('/double-mofa/print', [DoubleMofaController::class, 'printPdf'])->name('double-mofa.print');
        Route::get('/expenses/print', [ExpenseController::class, 'printPdf'])->name('expenses.print');
        Route::get('/agent-khata/print', [AgentKhataController::class, 'printPdf'])->name('agent-khata.print');

        Route::middleware(['active-subscription'])->group(function () {
            Route::post('/mofa', [MofaEntryController::class, 'store'])->name('mofa.store');
            Route::post('/mofa/import', [MofaEntryController::class, 'import'])->name('mofa.import'); // E7b commit
            Route::post('/stamping', [StampingController::class, 'store'])->name('stamping.store');
            Route::post('/manpower', [ManpowerController::class, 'store'])->name('manpower.store');
        });

        Route::put('/mofa/{mofa}', [MofaEntryController::class, 'update'])->name('mofa.update');
        Route::delete('/mofa/{mofa}', [MofaEntryController::class, 'destroy'])->name('mofa.destroy');

        Route::put('/stamping/{stamping}', [StampingController::class, 'update'])->name('stamping.update');
        Route::delete('/stamping/{stamping}', [StampingController::class, 'destroy'])->name('stamping.destroy');

        Route::put('/manpower/{manpower}', [ManpowerController::class, 'update'])->name('manpower.update');
        Route::delete('/manpower/{manpower}', [ManpowerController::class, 'destroy'])->name('manpower.destroy');

        // ── E2 money modules: Delivery + Double MOFA ──────────────────────────
        // Reads are open within the module. Adding rows and taking money in
        // (store + payment) requires an active subscription, matching E1.
        // Reversals/edits/deletes are corrections and stay available regardless,
        // so an agency whose plan lapsed can still fix its own money records.
        Route::get('/delivery', [DeliveryController::class, 'index'])->name('delivery');
        Route::get('/double-mofa', [DoubleMofaController::class, 'index'])->name('double-mofa');

        Route::middleware(['active-subscription'])->group(function () {
            Route::post('/delivery', [DeliveryController::class, 'store'])->name('delivery.store');
            Route::post('/delivery/{delivery}/payment', [DeliveryController::class, 'receivePayment'])->name('delivery.payment');
            Route::post('/double-mofa', [DoubleMofaController::class, 'store'])->name('double-mofa.store');
            Route::post('/double-mofa/{doubleMofa}/payment', [DoubleMofaController::class, 'receivePayment'])->name('double-mofa.payment');
        });

        Route::put('/delivery/{delivery}', [DeliveryController::class, 'update'])->name('delivery.update');
        Route::delete('/delivery/{delivery}', [DeliveryController::class, 'destroy'])->name('delivery.destroy');
        Route::post('/delivery/receipt/{receipt}/reverse', [DeliveryController::class, 'reverse'])->name('delivery.reverse');

        Route::put('/double-mofa/{doubleMofa}', [DoubleMofaController::class, 'update'])->name('double-mofa.update');
        Route::delete('/double-mofa/{doubleMofa}', [DoubleMofaController::class, 'destroy'])->name('double-mofa.destroy');
        Route::post('/double-mofa/receipt/{receipt}/reverse', [DoubleMofaController::class, 'reverse'])->name('double-mofa.reverse');

        // ── E3 sub-phase 1: Expenses (money-out log; no ledger) ───────────────
        // Reads open within the module; adding an expense requires an active
        // subscription, matching every other "store" in the suite.
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses');
        Route::middleware(['active-subscription'])->group(function () {
            Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        });
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

        // ── E3 sub-phase 2: Due List (read-only cross-module report) ──────────
        // No money routes of its own — inline "Receive Payment" posts to the
        // existing erp.delivery.payment / erp.double-mofa.payment endpoints.
        Route::get('/due-list', [DueListController::class, 'index'])->name('due-list');

        // ── E3 sub-phase 3: Agent Khata (per-agent ledger) ────────────────────
        // Viewing (index/show) is open to any access_erp staff. Money-moving
        // actions (store a debit/credit, reverse one) are admin-only, enforced
        // in the controller (abort_unless isAgencyAdmin) — same invariant as the
        // Delivery/DoubleMofa payment actions and Expenses.
        Route::get('/agent-khata', [AgentKhataController::class, 'index'])->name('agent-khata');
        Route::get('/agent-khata/{agent}', [AgentKhataController::class, 'show'])->name('agent-khata.show');
        Route::middleware(['active-subscription'])->group(function () {
            Route::post('/agent-khata/{agent}/transactions', [AgentKhataController::class, 'store'])->name('agent-khata.store');
        });
        Route::post('/agent-khata/transactions/{transaction}/reverse', [AgentKhataController::class, 'reverse'])->name('agent-khata.reverse');

        // ── E4: Dashboard + Reports (read-only aggregation) ───────────────────
        // Viewing the report is open to any access_erp staff (same visibility as
        // Due List / Agent Khata index). Exports return a bulk financial file, so
        // they are admin-only — enforced in the controller (abort_unless
        // isAgencyAdmin), matching every money-moving action in the suite.
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
        Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
        Route::get('/reports/export/csv', [ReportController::class, 'exportCsv'])->name('reports.export.csv');

        // ── E5: Profit / Loss (owner-only, security-code gated) ───────────────
        // Every action is admin-only (enforced in the controller). The unlock
        // SCREEN + verify/lock actions stay reachable while locked; the DATA view
        // and both exports sit behind `pl-unlocked` (session unlock, 15-min
        // absolute TTL) so P/L numbers can never render — or export — without a
        // live unlock. Unlock attempts are throttled to resist brute force.
        Route::get('/profit-loss/unlock', [ProfitLossController::class, 'unlockForm'])->name('profit-loss.unlock');
        Route::post('/profit-loss/unlock', [ProfitLossController::class, 'unlock'])->middleware('throttle:5,1')->name('profit-loss.unlock.submit');
        Route::post('/profit-loss/lock', [ProfitLossController::class, 'lock'])->name('profit-loss.lock');

        Route::middleware('pl-unlocked')->group(function () {
            Route::get('/profit-loss', [ProfitLossController::class, 'index'])->name('profit-loss');
            Route::get('/profit-loss/export/pdf', [ProfitLossController::class, 'exportPdf'])->name('profit-loss.export.pdf');
            Route::get('/profit-loss/export/csv', [ProfitLossController::class, 'exportCsv'])->name('profit-loss.export.csv');
        });
    });
