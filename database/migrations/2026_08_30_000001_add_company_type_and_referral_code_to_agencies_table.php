<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds two editable Agency Profile fields used by the agency Settings page:
     *  - company_type  : 'recruiting' | 'consultancy' (nullable; matches the live
     *                    site's "Company Type" dropdown).
     *  - referral_code : free-form referral code (nullable, no uniqueness).
     * Both nullable so existing rows are unaffected.
     */
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            if (! Schema::hasColumn('agencies', 'company_type')) {
                $table->string('company_type', 20)->nullable()->after('owner_name');
            }
            if (! Schema::hasColumn('agencies', 'referral_code')) {
                $table->string('referral_code', 50)->nullable()->after('company_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            foreach (['company_type', 'referral_code'] as $column) {
                if (Schema::hasColumn('agencies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
