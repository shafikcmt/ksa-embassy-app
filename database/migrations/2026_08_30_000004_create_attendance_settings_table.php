<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-agency attendance configuration (one row per agency).
 *
 * Config-like singleton: office hours, grace/absent thresholds, timezone,
 * weekend days and owner email-alert toggles. No transactional data lives here
 * (check-in/out records land in a later phase).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();

            $table->time('office_start')->default('09:00');
            $table->time('office_end')->default('18:00');
            $table->unsignedSmallInteger('grace_minutes')->default(15);
            $table->time('auto_absent_time')->nullable();
            $table->unsignedSmallInteger('half_day_after_minutes')->nullable();
            $table->unsignedSmallInteger('overtime_after_minutes')->nullable();
            $table->string('timezone', 40)->default('Asia/Dhaka');
            $table->json('weekend_days')->nullable(); // array of weekday ints 0..6 (0=Sun)

            $table->boolean('alert_on_late')->default(false);
            $table->boolean('alert_on_absent')->default(false);
            $table->boolean('alert_on_checkin')->default(false);
            $table->boolean('alert_on_checkout')->default(false);

            $table->timestamps();

            // One settings row per agency.
            $table->unique('agency_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};
