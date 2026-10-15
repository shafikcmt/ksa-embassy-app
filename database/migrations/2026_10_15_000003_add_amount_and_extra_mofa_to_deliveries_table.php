<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Delivery billing is split into Amount + Extra MOFA; total_amount (what reports, dues and
 * payments already use) stays the single billed figure = amount + extra_mofa.
 * Existing rows keep their total: amount := total_amount, extra_mofa := 0.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->decimal('amount', 14, 2)->default(0)->after('reference');
            $table->decimal('extra_mofa', 14, 2)->default(0)->after('amount');
        });

        DB::table('deliveries')->update(['amount' => DB::raw('total_amount')]);
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn(['amount', 'extra_mofa']);
        });
    }
};
