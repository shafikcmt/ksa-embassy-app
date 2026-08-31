<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ERP Double MOFA (E2) — passports with MOFA done more than once, tracked for
 * extra billing.
 *
 * billing_amount is SNAPSHOTTED from erp_settings.double_mofa_rate at creation
 * time so later rate changes never rewrite historical bills. paid_amount is
 * written only by ErpPaymentService. Unpaid = billing_amount - paid_amount.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('double_mofas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->date('mofa_date');
            $table->string('full_name');
            $table->string('passport_no');
            $table->string('visa_serial')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('billing_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agency_id', 'mofa_date']);
            $table->index(['agency_id', 'passport_no']);
            $table->index(['agency_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('double_mofas');
    }
};
