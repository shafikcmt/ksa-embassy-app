<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ERP operational tracker (E1) — manpower completion log.
 *
 * Standalone, agency-scoped. passport_no is a plain indexed string (not FK to
 * hr_profiles). agent_id is a nullable FK to agents so the E3 Agent Khata
 * ledger can aggregate per agent later. No money columns / no math here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manpower_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->date('completed_date');
            $table->string('customer_name');
            $table->string('passport_no');
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agency_id', 'completed_date']);
            $table->index(['agency_id', 'passport_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manpower_completions');
    }
};
