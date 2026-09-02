<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attendance records (H3b) — the truthful event log. A row exists ONLY for a
 * real check-in or an explicit admin marker; absent/weekend/holiday/empty-day
 * on_leave are DERIVED at read time (decision 2A) and never stored.
 *
 * Instants are UTC (decision 1A); work_date is the AGENCY-TZ calendar date the
 * shift belongs to, so the unique key + all reporting group correctly across UTC
 * midnight and overnight shifts. shift_id is a snapshot of the shift used for the
 * day's expectations. NO soft-deletes: an admin correcting a mistaken entry hard-
 * deletes it and the day cleanly reverts to its derived status; a soft-deleted row
 * would wrongly keep occupying the unique (agency, employee, work_date) slot.
 *
 * late/overtime/worked minutes + status are written by the controller FROM the
 * AttendanceCalculator (never from raw request input) — the same "service is the
 * sole writer of computed columns" discipline as ErpPaymentService/paid_amount.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();

            $table->date('work_date'); // agency-tz calendar date
            $table->dateTime('check_in_at')->nullable();  // UTC
            $table->dateTime('check_out_at')->nullable(); // UTC

            $table->string('status', 16); // present|late|half_day|absent|excused|on_leave
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);
            $table->unsignedInteger('worked_minutes')->default(0);

            $table->string('source', 10)->default('self'); // self | admin
            $table->string('note', 255)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['agency_id', 'employee_id', 'work_date']);
            $table->index(['agency_id', 'work_date']);
            $table->index(['employee_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
