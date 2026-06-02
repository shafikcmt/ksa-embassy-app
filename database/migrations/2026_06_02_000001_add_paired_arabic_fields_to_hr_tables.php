<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds remaining English/Arabic paired fields needed by the reference documents:
 *  - Sponsor / Company name (Arabic)  → forwarding letter & employment agreement header
 *  - Business address (EN/AR)          → application form "Business address & phone No."
 *  - Name/address in the Kingdom (EN/AR)→ application form kingdom block
 *
 * All nullable so existing records are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visas', function (Blueprint $table) {
            if (! Schema::hasColumn('visas', 'sponsor_name_ar')) {
                $table->string('sponsor_name_ar', 150)->nullable()->after('sponsor_name');
            }
        });

        Schema::table('hr_other_infos', function (Blueprint $table) {
            if (! Schema::hasColumn('hr_other_infos', 'business_address_en')) {
                $table->string('business_address_en', 255)->nullable()->after('remarks');
            }
            if (! Schema::hasColumn('hr_other_infos', 'business_address_ar')) {
                $table->string('business_address_ar', 255)->nullable()->after('business_address_en');
            }
            if (! Schema::hasColumn('hr_other_infos', 'kingdom_address_en')) {
                $table->string('kingdom_address_en', 255)->nullable()->after('business_address_ar');
            }
            if (! Schema::hasColumn('hr_other_infos', 'kingdom_address_ar')) {
                $table->string('kingdom_address_ar', 255)->nullable()->after('kingdom_address_en');
            }
        });
    }

    public function down(): void
    {
        Schema::table('visas', function (Blueprint $table) {
            $table->dropColumn('sponsor_name_ar');
        });

        Schema::table('hr_other_infos', function (Blueprint $table) {
            $table->dropColumn(['business_address_en', 'business_address_ar', 'kingdom_address_en', 'kingdom_address_ar']);
        });
    }
};
