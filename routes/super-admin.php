<?php

use App\Http\Controllers\SuperAdmin\AgencyController;
use App\Http\Controllers\SuperAdmin\AgentController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\DocumentController;
use App\Http\Controllers\SuperAdmin\EmbassyListController;
use App\Http\Controllers\SuperAdmin\HrProfileController;
use App\Http\Controllers\SuperAdmin\PlanController;
use App\Http\Controllers\SuperAdmin\SettingsController;
use App\Http\Controllers\SuperAdmin\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('super-admin')
    ->middleware(['auth', 'super-admin'])
    ->name('super-admin.')
    ->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

        // Agencies
        Route::resource('agencies', AgencyController::class);
        Route::patch('agencies/{agency}/toggle-status', [AgencyController::class, 'toggleStatus'])
            ->name('agencies.toggle-status');

        // Plans
        Route::resource('plans', PlanController::class)->except(['show']);

        // Subscriptions
        Route::resource('subscriptions', SubscriptionController::class)->except(['show']);
        Route::patch('subscriptions/{subscription}/approve', [SubscriptionController::class, 'approvePayment'])
            ->name('subscriptions.approve');

        // Agents (read + delete only for super admin)
        Route::get('agents', [AgentController::class, 'index'])->name('agents.index');
        Route::get('agents/{agent}', [AgentController::class, 'show'])->name('agents.show');
        Route::delete('agents/{agent}', [AgentController::class, 'destroy'])->name('agents.destroy');

        // HR Profiles (read-only for super admin)
        Route::get('hr', [HrProfileController::class, 'index'])->name('hr.index');
        Route::get('hr/{hr}', [HrProfileController::class, 'show'])->name('hr.show');
        Route::get('hr/{hr}/documents', [DocumentController::class, 'hrDocuments'])->name('hr.documents');

        // Embassy Lists (read-only for super admin)
        Route::get('embassy-lists', [EmbassyListController::class, 'index'])->name('embassy-lists.index');
        Route::get('embassy-lists/{embassyList}', [EmbassyListController::class, 'show'])->name('embassy-lists.show');
        Route::get('embassy-lists/{embassyList}/download-pdf', [DocumentController::class, 'downloadEmbassyList'])->name('embassy-lists.download-pdf');
    });
