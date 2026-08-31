<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ERP Expenses (E3, sub-phase 1) — agency money-OUT log.
 *
 * Deliberately simple: unlike E2, there is no ledger split and no partial
 * payment. Each row IS the truth — amount is the full outflow. `category` is
 * validated against a fixed Expense::CATEGORIES set (there is intentionally NO
 * "Agent Commission" category; agent payouts flow through Agent Khata to avoid
 * double-counting in the E5 Profit/Loss). Feeds E4 dashboard + E5 P&L.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->date('expense_date');
            $table->string('category');
            $table->decimal('amount', 14, 2);
            $table->string('paid_via')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agency_id', 'expense_date']);
            $table->index(['agency_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
