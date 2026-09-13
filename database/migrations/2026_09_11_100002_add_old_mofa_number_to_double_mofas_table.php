<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task 2 — add an optional "Old MOFA Number" to Double MOFA entries
 * (additive, nullable). The visa_serial column is retained (only the form
 * input is removed), so existing data and CSV import continue to work.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('double_mofas', function (Blueprint $table) {
            $table->string('old_mofa_number')->nullable()->after('visa_serial');
        });
    }

    public function down(): void
    {
        Schema::table('double_mofas', function (Blueprint $table) {
            $table->dropColumn('old_mofa_number');
        });
    }
};
