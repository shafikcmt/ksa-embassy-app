<?php

use App\Http\Controllers\Erp\DashboardController;
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
    });
