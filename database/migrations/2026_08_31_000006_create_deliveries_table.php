<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ERP Delivery (E2) — file delivery + payment collection.
 *
 * Money integrity: total_amount/paid_amount are decimal(14,2), never float.
 * paid_amount is a denormalized cache that is ONLY ever written by
 * ErpPaymentService (recomputed as the signed sum of payment_receipts inside a
 * locked transaction). Due = total_amount - paid_amount (computed, not stored).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->date('delivery_date');
            $table->string('full_name');
            $table->string('passport_no');
            $table->string('visa_serial')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->enum('status', ['pending', 'ready', 'delivered'])->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agency_id', 'delivery_date']);
            $table->index(['agency_id', 'passport_no']);
            $table->index(['agency_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
