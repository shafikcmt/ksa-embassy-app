<?php

use App\Http\Controllers\Agency\AgentController;
use App\Http\Controllers\Agency\AttendanceController;
use App\Http\Controllers\Agency\DashboardController;
use App\Http\Controllers\Agency\DocumentController;
use App\Http\Controllers\Agency\EmbassyListController;
use App\Http\Controllers\Agency\HrProfileController;
use App\Http\Controllers\Agency\LicenseController;
use App\Http\Controllers\Agency\SmartNoteController;
use App\Http\Controllers\Agency\SettingsController;
use App\Http\Controllers\Agency\StaffController;
use App\Http\Controllers\Agency\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'agency-access'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Settings (admin-only surface; controller/nav already gate non-admins)
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Staff Accounts — agency admin manages staff logins + per-module access.
    // Admin-only enforcement lives in StaffController (aborts 403 for staff).
    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
    Route::put('/staff/{user}', [StaffController::class, 'update'])->name('staff.update');
    Route::delete('/staff/{user}', [StaffController::class, 'destroy'])->name('staff.destroy');

    // License (read-only view of the agency's own license)
    Route::middleware('page-access:license')->group(function () {
        Route::get('/license', [LicenseController::class, 'index'])->name('license.index');
    });

    // Smart Notes (agency-scoped notes / reminders)
    Route::middleware('page-access:notes')->group(function () {
        Route::get('/notes', [SmartNoteController::class, 'index'])->name('notes.index');
        Route::post('/notes', [SmartNoteController::class, 'store'])->name('notes.store');
        Route::put('/notes/{note}', [SmartNoteController::class, 'update'])->name('notes.update');
        Route::delete('/notes/{note}', [SmartNoteController::class, 'destroy'])->name('notes.destroy');
        Route::post('/notes/{note}/restore', [SmartNoteController::class, 'restore'])->name('notes.restore');
        Route::post('/notes/{note}/pin', [SmartNoteController::class, 'togglePin'])->name('notes.pin');
        Route::post('/notes/{note}/complete', [SmartNoteController::class, 'toggleComplete'])->name('notes.complete');
        Route::post('/notes/{note}/archive', [SmartNoteController::class, 'toggleArchive'])->name('notes.archive');
        Route::delete('/notes/{note}/force', [SmartNoteController::class, 'forceDelete'])->name('notes.force-delete');
    });

    // Attendance (config phase: settings, shifts, holidays, leave types)
    // + H3a: Employees (admin-only management enforced in-controller).
    Route::middleware('page-access:attendance')->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');

        Route::put('/attendance/settings', [AttendanceController::class, 'updateSettings'])->name('attendance.settings.update');

        Route::post('/attendance/employees', [AttendanceController::class, 'storeEmployee'])->name('attendance.employees.store');
        Route::put('/attendance/employees/{employee}', [AttendanceController::class, 'updateEmployee'])->name('attendance.employees.update');
        Route::delete('/attendance/employees/{employee}', [AttendanceController::class, 'destroyEmployee'])->name('attendance.employees.destroy');

        // H3b: self check-in/out (any linked active employee, own record only) +
        // admin manual records (create/edit/hard-delete). No active-subscription gate.
        Route::post('/attendance/check', [AttendanceController::class, 'check'])->name('attendance.check');
        Route::post('/attendance/records', [AttendanceController::class, 'storeRecord'])->name('attendance.records.store');
        Route::put('/attendance/records/{record}', [AttendanceController::class, 'updateRecord'])->name('attendance.records.update');
        Route::delete('/attendance/records/{record}', [AttendanceController::class, 'destroyRecord'])->name('attendance.records.destroy');

        Route::post('/attendance/shifts', [AttendanceController::class, 'storeShift'])->name('attendance.shifts.store');
        Route::put('/attendance/shifts/{shift}', [AttendanceController::class, 'updateShift'])->name('attendance.shifts.update');
        Route::delete('/attendance/shifts/{shift}', [AttendanceController::class, 'destroyShift'])->name('attendance.shifts.destroy');

        Route::post('/attendance/holidays', [AttendanceController::class, 'storeHoliday'])->name('attendance.holidays.store');
        Route::put('/attendance/holidays/{holiday}', [AttendanceController::class, 'updateHoliday'])->name('attendance.holidays.update');
        Route::delete('/attendance/holidays/{holiday}', [AttendanceController::class, 'destroyHoliday'])->name('attendance.holidays.destroy');

        Route::post('/attendance/leave-types', [AttendanceController::class, 'storeLeaveType'])->name('attendance.leave-types.store');
        Route::put('/attendance/leave-types/{leaveType}', [AttendanceController::class, 'updateLeaveType'])->name('attendance.leave-types.update');
        Route::delete('/attendance/leave-types/{leaveType}', [AttendanceController::class, 'destroyLeaveType'])->name('attendance.leave-types.destroy');
    });

    // Subscription expired / renewal
    Route::get('/subscription/expired', [SubscriptionController::class, 'expired'])->name('subscription.expired');
    Route::post('/subscription/renew-request', [SubscriptionController::class, 'renewRequest'])->name('subscription.renew-request');

    // Agency suspended notice
    Route::get('/suspended', fn() => view('agency.suspended'))->name('agency.suspended');

    // ── Agents ──────────────────────────────────────────────────────────────
    Route::middleware('page-access:agents')->group(function () {
        // create/store require active subscription (must be BEFORE /{agent} wildcard)
        Route::middleware(['active-subscription'])->group(function () {
            Route::get('/agents/create', [AgentController::class, 'create'])->name('agents.create');
            Route::post('/agents', [AgentController::class, 'store'])->name('agents.store');
        });

        Route::get('/agents', [AgentController::class, 'index'])->name('agents.index');
        Route::get('/agents/{agent}', [AgentController::class, 'show'])->name('agents.show');
        Route::get('/agents/{agent}/edit', [AgentController::class, 'edit'])->name('agents.edit');
        Route::put('/agents/{agent}', [AgentController::class, 'update'])->name('agents.update');
        Route::delete('/agents/{agent}', [AgentController::class, 'destroy'])->name('agents.destroy');
    });

    // ── HR / Candidates + HR Documents ──────────────────────────────────────
    Route::middleware('page-access:hr')->group(function () {
        // create/store require active subscription (must be BEFORE /{hr} wildcard)
        Route::middleware(['active-subscription'])->group(function () {
            Route::get('/hr/create', [HrProfileController::class, 'create'])->name('hr.create');
            Route::post('/hr', [HrProfileController::class, 'store'])->name('hr.store');
        });

        // HR passport lookup for embassy list quick-add (no subscription gate)
        Route::post('/hr/lookup-by-passport', [HrProfileController::class, 'lookupByPassport'])->name('hr.lookup-by-passport');

        Route::get('/hr', [HrProfileController::class, 'index'])->name('hr.index');
        Route::get('/hr/{hr}', [HrProfileController::class, 'show'])->name('hr.show');
        Route::get('/hr/{hr}/edit', [HrProfileController::class, 'edit'])->name('hr.edit');
        Route::put('/hr/{hr}', [HrProfileController::class, 'update'])->name('hr.update');
        Route::delete('/hr/{hr}', [HrProfileController::class, 'destroy'])->name('hr.destroy');

        // HR Documents — previews + downloads
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
    });

    // ── Embassy Lists ───────────────────────────────────────────────────────
    Route::middleware('page-access:embassy_list')->group(function () {
        // JSON search endpoint (no subscription gate)
        Route::get('/api/embassy-lists/available-hr', [EmbassyListController::class, 'availableHr'])->name('embassy-lists.available-hr');

        // create/store require active subscription (must be BEFORE wildcard)
        Route::middleware(['active-subscription'])->group(function () {
            Route::get('/embassy-lists/create', [EmbassyListController::class, 'create'])->name('embassy-lists.create');
            Route::post('/embassy-lists', [EmbassyListController::class, 'store'])->name('embassy-lists.store');
        });

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
});
