<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links an Expense to the Payment Voucher that created it.
 *
 * When a voucher is marked paid, PaymentVoucherService creates exactly ONE
 * expense in the same transaction. UNIQUE(payment_voucher_id) makes that a
 * database guarantee — a voucher can never be counted twice in Expenses/P&L,
 * even under a double-submit. NULL for every manually entered / imported
 * expense (MySQL UNIQUE allows many NULLs), so existing rows are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('payment_voucher_id')->nullable()->after('agency_id')
                ->constrained('payment_vouchers')->restrictOnDelete();
            $table->unique('payment_voucher_id');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['payment_voucher_id']);
            $table->dropUnique(['payment_voucher_id']);
            $table->dropColumn('payment_voucher_id');
        });
    }
};
