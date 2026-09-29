<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ERP Invoices — agency-scoped multi-line invoices.
 *
 * Money columns are decimal(14,2) (same as the rest of the ERP). Every derived
 * amount (subtotal/tax_amount/discount_amount/total_amount) is written ONLY by
 * App\Services\InvoiceService, which does the math in integer cents.
 *
 * Numbering: INV-{agency_id}-{YYYY}-{0001}. number_year + number_seq are stored
 * separately and are UNIQUE per agency, so two concurrent creates can never share
 * a number (the service also locks the agency row while allocating).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();

            $table->string('invoice_number', 40);
            $table->unsignedSmallInteger('number_year');
            $table->unsignedInteger('number_seq');

            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            // Bill-to — optional link to an agency agent, plus free-text snapshot.
            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();
            $table->string('bill_to_name')->nullable();
            $table->string('bill_to_phone', 50)->nullable();
            $table->string('bill_to_address')->nullable();

            $table->string('status', 20)->default('draft');          // draft|pending|paid|cancelled
            $table->string('currency', 3)->default('BDT');

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->string('tax_type', 10)->default('none');         // none|percent|amount
            $table->decimal('tax_value', 14, 2)->default(0);         // % or fixed amount as entered
            $table->decimal('tax_amount', 14, 2)->default(0);        // computed
            $table->string('discount_type', 10)->default('none');    // none|percent|amount
            $table->decimal('discount_value', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);

            // Payment (full mark-as-paid; locks the invoice).
            $table->string('payment_method', 20)->nullable();        // cash|cheque|bank_transfer|bkash|nagad
            $table->date('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['agency_id', 'invoice_number']);
            $table->unique(['agency_id', 'number_year', 'number_seq']);
            $table->index(['agency_id', 'status']);
            $table->index(['agency_id', 'invoice_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
