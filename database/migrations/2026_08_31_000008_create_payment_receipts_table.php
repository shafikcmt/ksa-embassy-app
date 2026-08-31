<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ERP payment ledger (E2) — the append-only money source of truth.
 *
 * One row per "Receive Payment" or "Reverse" event, polymorphically attached to
 * a Delivery or DoubleMofa. amount is always stored POSITIVE; `type` decides the
 * sign when summing (payment = +amount, reversal = -amount). Rows are never
 * updated or deleted — a mistake is corrected with a reversal row. `note` is
 * required for reversals (captured/enforced in the controller/service). This
 * table is the who/when/how-much/why audit trail for all money received.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->string('payable_type');
            $table->unsignedBigInteger('payable_id');
            $table->enum('type', ['payment', 'reversal'])->default('payment');
            $table->decimal('amount', 14, 2);
            $table->string('note')->nullable();
            // For reversals: the payment row this reverses. Enforced unique so a
            // given payment can be reversed at most once (no double-reversal).
            $table->foreignId('reverses_id')->nullable()->unique()
                ->constrained('payment_receipts')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamps();

            $table->index(['agency_id', 'payable_type', 'payable_id']);
            $table->index(['agency_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_receipts');
    }
};
