<?php

use App\Http\Controllers\Erp\DashboardController;
use App\Http\Controllers\Erp\DeliveryController;
use App\Http\Controllers\Erp\DoubleMofaController;
use App\Http\Controllers\Erp\DueListController;
use App\Http\Controllers\Erp\ExpenseController;
use App\Http\Controllers\Erp\ManpowerController;
use App\Http\Controllers\Erp\MofaEntryController;
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

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

        // ── E1 operational trackers (non-money logs) ──────────────────────────
        // Reads are open within the module; adding entries (store) requires an
        // active subscription, matching the HR/Embassy/Agents modules.
        Route::get('/mofa', [MofaEntryController::class, 'index'])->name('mofa');
        Route::get('/stamping', [StampingController::class, 'index'])->name('stamping');
        Route::get('/manpower', [ManpowerController::class, 'index'])->name('manpower');

        Route::middleware(['active-subscription'])->group(function () {
            Route::post('/mofa', [MofaEntryController::class, 'store'])->name('mofa.store');
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
    });
