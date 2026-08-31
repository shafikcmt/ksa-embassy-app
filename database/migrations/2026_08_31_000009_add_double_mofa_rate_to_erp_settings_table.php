<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add the configurable Double MOFA billing rate to erp_settings (E2).
 * Default 3000 (BDT per person). Snapshotted into double_mofas.billing_amount
 * at creation time, so changing this later does not rewrite historical bills.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_settings', function (Blueprint $table) {
            $table->decimal('double_mofa_rate', 10, 2)->default(3000)->after('pl_visible_to_all');
        });
    }

    public function down(): void
    {
        Schema::table('erp_settings', function (Blueprint $table) {
            $table->dropColumn('double_mofa_rate');
        });
    }
};
