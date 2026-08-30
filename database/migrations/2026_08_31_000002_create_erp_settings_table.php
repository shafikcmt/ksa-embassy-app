<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-agency ERP configuration (one row per agency).
 *
 *  - opening_balance      historical starting balance carried into ERP totals.
 *                         Stored here in E0; no accounting math consumes it yet.
 *  - pl_security_code     owner secret that gates the Profit/Loss page. Stored
 *                         HASHED (bcrypt) via the ErpSetting model mutator; NULL
 *                         means no protection.
 *  - pl_visible_to_all    when true, income/P&L/balance are visible to all staff;
 *                         otherwise owner-only (enforced in a later ERP phase).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->string('opening_balance_note')->nullable();
            $table->string('pl_security_code')->nullable();
            $table->boolean('pl_visible_to_all')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_settings');
    }
};
