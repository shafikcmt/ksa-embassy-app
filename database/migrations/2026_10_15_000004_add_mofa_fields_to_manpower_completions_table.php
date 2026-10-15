<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * BMET entries show the passenger's MOFA Number / MOFA Date (Visa Details section).
 * Nullable; filled from the passport's MOFA record (form auto-fill, MOFA sync, and a one-time backfill).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manpower_completions', function (Blueprint $table) {
            $table->string('mofa_number', 100)->nullable()->after('id_number');
            $table->date('mofa_date')->nullable()->after('mofa_number');
        });

        try {
            Artisan::call('erp:backfill-blanks', ['--module' => 'bmet']);
        } catch (\Throwable $e) {
            Log::warning('erp:backfill-blanks --module=bmet failed (run it manually): '.$e->getMessage());
        }
    }

    public function down(): void
    {
        Schema::table('manpower_completions', function (Blueprint $table) {
            $table->dropColumn(['mofa_number', 'mofa_date']);
        });
    }
};
