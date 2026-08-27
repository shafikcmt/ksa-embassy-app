<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Make clearances.medical_fit nullable and drop its default(false).
 *
 * WHY: the column was created as boolean default(false), so every record stores
 * `false` even when the user never answered — the profile view then showed a
 * misleading "No". Making it nullable lets us distinguish three real states:
 *   null  -> Not provided (never answered)
 *   true  -> Yes
 *   false -> No
 *
 * FOLLOW-UP NEEDED for full null semantics (not part of this migration):
 *   1. HrProfileController create/update currently uses $request->boolean('medical_fit'),
 *      which coerces an absent field to false. Change to store null when the field
 *      is absent, e.g. $request->has('medical_fit') ? $request->boolean('medical_fit') : null.
 *   2. Add a visible Medical Fit control to resources/views/agency/hr/_form.blade.php
 *      (currently it is only a hidden carry-over input, so users can't answer it).
 *   3. In resources/views/agency/hr/show.blade.php, add the false -> "No" branch back
 *      to the Medical Fit row (it currently renders false as "Not provided" because a
 *      stored false is a default artifact until the above two changes exist).
 *
 * This migration is intentionally schema-only and safe to run on its own: existing
 * rows keep their current values; nothing breaks if the follow-up is done later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clearances', function (Blueprint $table) {
            $table->boolean('medical_fit')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        // Backfill any nulls to false first so the non-nullable restore cannot fail.
        DB::table('clearances')->whereNull('medical_fit')->update(['medical_fit' => false]);

        Schema::table('clearances', function (Blueprint $table) {
            $table->boolean('medical_fit')->nullable(false)->default(false)->change();
        });
    }
};
