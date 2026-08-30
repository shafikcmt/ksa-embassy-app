<?php

use App\Http\Controllers\Erp\DashboardController;
use App\Http\Controllers\Erp\SettingsController;
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
    });
