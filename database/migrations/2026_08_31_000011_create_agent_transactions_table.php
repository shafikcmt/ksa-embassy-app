<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ERP Agent Khata (E3, sub-phase 3) — append-only per-agent money ledger.
 *
 * Mirrors payment_receipts' integrity model: amount stored POSITIVE, `type`
 * gives the sign when summing (debit = +, credit = −). Rows are never updated
 * or deleted — a mistake is corrected with a reversal row whose `reverses_id`
 * points at the original (UNIQUE, so a row is reversible at most once).
 *
 * There is NO cached balance column: an agent's balance is live-computed as
 * agents.opening_balance + signed-sum(ledger), so there is nothing to drift.
 * agent_id is restrictOnDelete: the DB refuses to delete an agent that still
 * has ledger rows (app-level guard in AgentController@destroy mirrors this).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained()->restrictOnDelete();
            $table->date('txn_date');
            $table->enum('type', ['debit', 'credit']);
            $table->decimal('amount', 14, 2);
            $table->string('note')->nullable();
            // For reversals: the row this reverses. UNIQUE so a given transaction
            // can be reversed at most once (no double-reversal).
            $table->foreignId('reverses_id')->nullable()->unique()
                ->constrained('agent_transactions')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agency_id', 'agent_id']);
            $table->index(['agency_id', 'txn_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_transactions');
    }
};
