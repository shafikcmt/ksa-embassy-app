<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ERP operational tracker (E1) — MOFA application log.
 *
 * A standalone, agency-scoped log. passport_no is a plain indexed string (NOT
 * an FK to hr_profiles) because ERP entries may reference passports outside the
 * HR pool. payment_method is a categorical tag only — there is no amount column
 * and no money math in this phase.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mofa_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->date('mofa_date');
            $table->string('mofa_number')->nullable();
            $table->string('visa_serial')->nullable();
            $table->string('full_name');
            $table->string('passport_no');
            $table->string('reference_name')->nullable();
            $table->enum('payment_method', ['company_account', 'card_payment', 'no_payment'])->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->text('payment_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agency_id', 'mofa_date']);
            $table->index(['agency_id', 'passport_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mofa_entries');
    }
};
