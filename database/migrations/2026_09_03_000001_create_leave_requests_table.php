<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leave requests (H3c) — the staff-submit → admin approve/reject workflow.
 *
 * A request is a lightweight state machine: pending is the only non-terminal
 * state; approved/rejected/cancelled are terminal. An APPROVED request feeds the
 * attendance derivation seam — LeaveRequest::approvedDatesFor() expands its date
 * range into the per-day $isOnLeave bool that AttendanceCalculator::deriveDayStatus()
 * already consumes (added in H3b, empty until now). The calculator is untouched.
 *
 * NO soft-deletes (mirrors attendance_records): to reverse a mistaken approval an
 * admin HARD-deletes the row and the affected days cleanly revert to their derived
 * status. `days` is a working-day snapshot at submit time (span minus weekends and
 * holidays) used for display/balance; derivation itself keys off the actual date
 * range, so the two never drift into an incorrect day-status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            // leave_types soft-delete (the row never physically leaves), so restrict
            // is safe and blocks an accidental hard delete of a referenced type.
            $table->foreignId('leave_type_id')->constrained('leave_types')->restrictOnDelete();

            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('days'); // working-day snapshot (weekends/holidays excluded)

            $table->string('status', 12)->default('pending'); // pending|approved|rejected|cancelled
            $table->string('reason', 255)->nullable();         // staff-supplied
            $table->string('decision_note', 255)->nullable();  // admin note on approve/reject

            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Derivation lookup (approved rows for an employee in a range) + admin queue.
            $table->index(['agency_id', 'employee_id', 'status']);
            $table->index(['agency_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
