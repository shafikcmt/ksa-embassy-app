<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-agent Khata opening balance (E3). The starting balance carried into the
 * agent's ledger; the live balance = opening_balance + signed-sum(agent_transactions).
 * Nullable with default 0 so this is a safe additive change to the agents table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->decimal('opening_balance', 14, 2)->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn('opening_balance');
        });
    }
};
