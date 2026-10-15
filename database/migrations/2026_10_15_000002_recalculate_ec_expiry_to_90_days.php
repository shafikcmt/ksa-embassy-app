<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * EC / BMET validity is now 90 days from the EC date (was one year). Recompute the stored
 * ec_expiry_date of existing rows from their EC (completed) date so lists, statuses and the
 * "expiring soon" flag agree with the form. Deterministic and idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('manpower_completions')->whereNotNull('completed_date')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('manpower_completions')->where('id', $row->id)->update([
                    'ec_expiry_date' => Carbon::parse($row->completed_date)->addDays(90)->toDateString(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Not reversible without the original dates; the EC date is untouched.
    }
};
