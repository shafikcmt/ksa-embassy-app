<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add an optional EC Number to manpower completions (additive, nullable).
 * The date column keeps its name (completed_date); only its display label
 * changes to "BMET Date". No data backfill needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manpower_completions', function (Blueprint $table) {
            $table->string('ec_number')->nullable()->after('passport_no');
        });
    }

    public function down(): void
    {
        Schema::table('manpower_completions', function (Blueprint $table) {
            $table->dropColumn('ec_number');
        });
    }
};
