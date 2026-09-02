<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attendance employees (H3a) — the office workforce that checks in.
 *
 * Distinct from hr_profiles (which is the visa-candidate pool): an employee is
 * an agency's own staff member, optionally linked to a login (user_id) so they
 * can self-check-in later. user_id is NULLABLE so a login-less employee (e.g.
 * support staff whose attendance is tracked but who never sign in) is fully
 * supported and managed by the admin.
 *
 * On-delete: agency cascade (tenant teardown); user_id/shift_id nullOnDelete so
 * removing a login or retiring a shift only UNLINKS — it never destroys the
 * attendance identity that future attendance_records (H3b) will reference.
 * SoftDeletes for the same reason: a retired employee must not orphan its records.
 *
 * Unique (agency_id, user_id): at most one employee per login (MySQL allows many
 * NULLs, so unlimited login-less employees coexist). Because soft-deleted rows
 * keep their user_id and would still occupy that unique slot, destroyEmployee()
 * nulls user_id BEFORE soft-deleting, freeing the login to be linked elsewhere.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();

            $table->string('name', 120);
            $table->string('designation', 120)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email', 255)->nullable();
            $table->date('join_date')->nullable();
            $table->string('status', 16)->default('active'); // active | inactive (enforced in app)

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['agency_id', 'status']);
            $table->unique(['agency_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
