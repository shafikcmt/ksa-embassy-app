<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task 1 — add an optional ID Number to MOFA entries (additive, nullable).
 * Mirrors the existing id_number on stampings. No data backfill needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mofa_entries', function (Blueprint $table) {
            $table->string('id_number')->nullable()->after('visa_serial');
        });
    }

    public function down(): void
    {
        Schema::table('mofa_entries', function (Blueprint $table) {
            $table->dropColumn('id_number');
        });
    }
};
