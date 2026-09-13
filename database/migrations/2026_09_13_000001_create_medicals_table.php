<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ERP operational tracker (E1) — medical-check log.
 *
 * Standalone, agency-scoped. passport_no is a plain indexed string (not FK to
 * hr_profiles). medical_status is a workflow enum. No money columns / no math
 * here. Mirrors the stampings table pattern; medical_issue_date is nullable
 * (so, unlike Stamping, there are no date-based display serials).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('father_name');
            $table->string('passport_no');
            $table->string('medical_center_name')->nullable();
            $table->string('medical_code')->nullable();
            $table->date('medical_issue_date')->nullable();
            $table->date('medical_expire_date')->nullable();
            $table->enum('medical_status', ['pending', 'process', 'under_review', 'fit', 'unfit'])->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agency_id', 'passport_no']);
            $table->index(['agency_id', 'medical_status']);
            $table->index(['agency_id', 'medical_expire_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicals');
    }
};
