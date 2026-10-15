<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * One-time data backfill: fill BLANK candidate fields (father/mother name, DOB, passport dates,
 * visa/ID no.) on existing ERP rows from the same passport's other ERP/HR records.
 * Only blanks are touched, per agency; runs once with the normal deploy migration.
 * A failure here must never block a deploy, so it is logged and swallowed.
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            Artisan::call('erp:backfill-blanks');
            Log::info('erp:backfill-blanks: '.trim(Artisan::output()));
        } catch (\Throwable $e) {
            Log::warning('erp:backfill-blanks failed (run it manually): '.$e->getMessage());
        }
    }

    public function down(): void
    {
        // Data backfill — nothing to undo.
    }
};
