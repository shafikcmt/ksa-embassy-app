<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the Arabic sponsor-name snapshot to embassy_list_items so the embassy
 * list Page 1 "اسم الكفيل / Sponsor Name" column can render Arabic when the
 * source visa has sponsor_name_ar, mirroring snapshot_profession_ar.
 *
 * Nullable + guarded so existing rows and re-runs are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('embassy_list_items', function (Blueprint $table) {
            if (! Schema::hasColumn('embassy_list_items', 'snapshot_sponsor_name_ar')) {
                $table->string('snapshot_sponsor_name_ar', 150)->nullable()->after('snapshot_sponsor_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('embassy_list_items', function (Blueprint $table) {
            if (Schema::hasColumn('embassy_list_items', 'snapshot_sponsor_name_ar')) {
                $table->dropColumn('snapshot_sponsor_name_ar');
            }
        });
    }
};
