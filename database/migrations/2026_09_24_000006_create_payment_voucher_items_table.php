<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payment voucher lines. `amount` = quantity × unit_price, computed in integer
 * cents by App\Services\PaymentVoucherService (never taken from the request).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_voucher_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_voucher_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('amount', 14, 2);
            $table->text('remarks')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['payment_voucher_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_voucher_items');
    }
};
