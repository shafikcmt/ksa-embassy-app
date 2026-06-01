<?php

use App\Http\Controllers\Agency\AgentController;
use App\Http\Controllers\Agency\DashboardController;
use App\Http\Controllers\Agency\DocumentController;
use App\Http\Controllers\Agency\EmbassyListController;
use App\Http\Controllers\Agency\HrProfileController;
use App\Http\Controllers\Agency\SettingsController;
use App\Http\Controllers\Agency\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'agency-access'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Subscription expired / renewal
    Route::get('/subscription/expired', [SubscriptionController::class, 'expired'])->name('subscription.expired');
    Route::post('/subscription/renew-request', [SubscriptionController::class, 'renewRequest'])->name('subscription.renew-request');

    // Agency suspended notice
    Route::get('/suspended', fn() => view('agency.suspended'))->name('agency.suspended');

    // Agents — create/store require active subscription (must be BEFORE /{agent} wildcard)
    Route::middleware(['active-subscription'])->group(function () {
        Route::get('/agents/create', [AgentController::class, 'create'])->name('agents.create');
        Route::post('/agents', [AgentController::class, 'store'])->name('agents.store');
    });

    // Agents — list, view, edit, delete (no subscription gate needed)
    Route::get('/agents', [AgentController::class, 'index'])->name('agents.index');
    Route::get('/agents/{agent}', [AgentController::class, 'show'])->name('agents.show');
    Route::get('/agents/{agent}/edit', [AgentController::class, 'edit'])->name('agents.edit');
    Route::put('/agents/{agent}', [AgentController::class, 'update'])->name('agents.update');
    Route::delete('/agents/{agent}', [AgentController::class, 'destroy'])->name('agents.destroy');

    // HR/Candidates — create/store require active subscription (must be BEFORE /{hr} wildcard)
    Route::middleware(['active-subscription'])->group(function () {
        Route::get('/hr/create', [HrProfileController::class, 'create'])->name('hr.create');
        Route::post('/hr', [HrProfileController::class, 'store'])->name('hr.store');
    });

    // HR passport lookup for embassy list quick-add (no subscription gate)
    Route::post('/hr/lookup-by-passport', [HrProfileController::class, 'lookupByPassport'])->name('hr.lookup-by-passport');

    // HR/Candidates — list, view, edit, delete (no subscription gate needed)
    Route::get('/hr', [HrProfileController::class, 'index'])->name('hr.index');
    Route::get('/hr/{hr}', [HrProfileController::class, 'show'])->name('hr.show');
    Route::get('/hr/{hr}/edit', [HrProfileController::class, 'edit'])->name('hr.edit');
    Route::put('/hr/{hr}', [HrProfileController::class, 'update'])->name('hr.update');
    Route::delete('/hr/{hr}', [HrProfileController::class, 'destroy'])->name('hr.destroy');

    // HR Documents — previews (must be before /{hr} wildcard, already satisfied)
    Route::get('/hr/{hr}/documents', [DocumentController::class, 'hrDocuments'])->name('hr.documents');
    Route::get('/hr/{hr}/print/application', [DocumentController::class, 'previewApplication'])->name('hr.print.application');
    Route::get('/hr/{hr}/print/forwarding-letter', [DocumentController::class, 'previewForwardingLetter'])->name('hr.print.forwarding-letter');
    Route::get('/hr/{hr}/print/employment-agreement', [DocumentController::class, 'previewEmploymentAgreement'])->name('hr.print.employment-agreement');
    Route::get('/hr/{hr}/print/checklist', [DocumentController::class, 'previewChecklist'])->name('hr.print.checklist');
    Route::get('/hr/{hr}/print/full-file', [DocumentController::class, 'previewFullFile'])->name('hr.print.full-file');
    Route::get('/hr/{hr}/download/application', [DocumentController::class, 'downloadApplication'])->name('hr.download.application');
    Route::get('/hr/{hr}/download/forwarding-letter', [DocumentController::class, 'downloadForwardingLetter'])->name('hr.download.forwarding-letter');
    Route::get('/hr/{hr}/download/employment-agreement', [DocumentController::class, 'downloadEmploymentAgreement'])->name('hr.download.employment-agreement');
    Route::get('/hr/{hr}/download/checklist', [DocumentController::class, 'downloadChecklist'])->name('hr.download.checklist');
    Route::get('/hr/{hr}/download/full-file', [DocumentController::class, 'downloadFullFile'])->name('hr.download.full-file');

    // Embassy Lists — JSON search endpoint (no subscription gate)
    Route::get('/api/embassy-lists/available-hr', [EmbassyListController::class, 'availableHr'])->name('embassy-lists.available-hr');

    // Embassy Lists — create/store require active subscription (must be BEFORE wildcard)
    Route::middleware(['active-subscription'])->group(function () {
        Route::get('/embassy-lists/create', [EmbassyListController::class, 'create'])->name('embassy-lists.create');
        Route::post('/embassy-lists', [EmbassyListController::class, 'store'])->name('embassy-lists.store');
    });

    // Embassy Lists — remaining routes (no subscription gate)
    Route::get('/embassy-lists', [EmbassyListController::class, 'index'])->name('embassy-lists.index');
    Route::get('/embassy-lists/{embassyList}', [EmbassyListController::class, 'show'])->name('embassy-lists.show');
    Route::get('/embassy-lists/{embassyList}/edit', [EmbassyListController::class, 'edit'])->name('embassy-lists.edit');
    Route::put('/embassy-lists/{embassyList}', [EmbassyListController::class, 'update'])->name('embassy-lists.update');
    Route::delete('/embassy-lists/{embassyList}', [EmbassyListController::class, 'destroy'])->name('embassy-lists.destroy');
    Route::post('/embassy-lists/{embassyList}/finalize', [EmbassyListController::class, 'finalize'])->name('embassy-lists.finalize');
    Route::post('/embassy-lists/{embassyList}/cancel', [EmbassyListController::class, 'cancel'])->name('embassy-lists.cancel');
    Route::get('/embassy-lists/{embassyList}/print', [EmbassyListController::class, 'print'])->name('embassy-lists.print');
    Route::get('/embassy-lists/{embassyList}/download-pdf', [DocumentController::class, 'downloadEmbassyList'])->name('embassy-lists.download-pdf');
});
