<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds deleted_at to embassy_lists so agency users can delete Draft/Cancelled
 * lists recoverably. Items are kept as-is (they hide via the parent's scope),
 * and list_no numbering keeps counting trashed rows so numbers never repeat.
 *
 * Nullable + guarded so existing rows and re-runs are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('embassy_lists', function (Blueprint $table) {
            if (! Schema::hasColumn('embassy_lists', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('embassy_lists', function (Blueprint $table) {
            if (Schema::hasColumn('embassy_lists', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
