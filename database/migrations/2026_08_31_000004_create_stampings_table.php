<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ERP operational tracker (E1) — visa stamping log.
 *
 * Standalone, agency-scoped. passport_no is a plain indexed string (not FK to
 * hr_profiles). status is a workflow enum. No money columns / no math here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stampings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->date('stamp_date');
            $table->string('visa_serial')->nullable();
            $table->string('full_name');
            $table->string('passport_no');
            $table->string('visa_number')->nullable();
            $table->string('id_number')->nullable();
            $table->string('reference')->nullable();
            $table->enum('status', ['pending', 'processing', 'stamped'])->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agency_id', 'stamp_date']);
            $table->index(['agency_id', 'passport_no']);
            $table->index(['agency_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stampings');
    }
};
