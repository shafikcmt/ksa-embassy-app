<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ERP Payment Vouchers — agency money-OUT documents (parties, manpower
 * providers, vendors, contractors). Mirrors the invoices table conventions.
 *
 * Money columns are decimal(14,2) and written ONLY by
 * App\Services\PaymentVoucherService (integer-cent math). Numbering:
 * VCH-{agency_id}-{YYYY}-{0001}; number_year + number_seq are UNIQUE per agency
 * and allocated under an agency-row lock.
 *
 * Enum-like columns are plain strings validated against model constants (same
 * as the rest of the ERP), so adding a value never needs an ALTER.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();

            $table->string('voucher_number', 40);
            $table->unsignedSmallInteger('number_year');
            $table->unsignedInteger('number_seq');

            $table->date('voucher_date');
            $table->date('payment_date')->nullable();

            $table->string('payee_type', 20)->default('party');        // party|individual|organization
            $table->string('payee_name');
            $table->string('payee_phone', 50)->nullable();
            $table->text('payee_address')->nullable();
            $table->string('payee_account', 100)->nullable();

            $table->string('payment_method', 20)->default('cash');     // cash|cheque|bank_transfer|mobile_banking
            $table->string('cheque_number', 50)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('reference_number', 100)->nullable();

            $table->text('description');

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->string('tax_type', 10)->default('none');           // none|percent|fixed
            $table->decimal('tax_value', 14, 2)->nullable();
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->string('discount_type', 10)->default('none');      // none|percent|fixed
            $table->decimal('discount_value', 14, 2)->nullable();
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);

            $table->string('status', 20)->default('draft');            // draft|approved|paid|cancelled
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['agency_id', 'voucher_number']);
            $table->unique(['agency_id', 'number_year', 'number_seq']);
            $table->index(['agency_id', 'status']);
            $table->index(['agency_id', 'voucher_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_vouchers');
    }
};
