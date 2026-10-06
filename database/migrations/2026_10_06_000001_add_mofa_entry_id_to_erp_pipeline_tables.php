<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links the downstream ERP rows (Double MOFA, Visa Stamping, BMET, Delivery) to
 * the MOFA entry that created/feeds them, so MofaSyncService can keep the shared
 * candidate fields in step when the MOFA entry is edited.
 *
 * Nullable + nullOnDelete: manually-added rows stay unlinked, and removing a MOFA
 * entry only unlinks — the downstream row (and its payments/history) is kept.
 */
return new class extends Migration
{
    private const TABLES = ['double_mofas', 'stampings', 'manpower_completions', 'deliveries'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('mofa_entry_id')->nullable()->after('agency_id')->constrained('mofa_entries')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('mofa_entry_id');
            });
        }
    }
};
