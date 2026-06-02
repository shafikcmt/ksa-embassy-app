<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the remaining HR form reference fields that did not yet have columns:
 *  - MOFA Application ID (new + old)
 *  - Sect
 *  - Home Address & Phone
 *  - Passport validity type (5 / 10 years)
 *  - Arrival / Departure date (Arabic text variants)
 *
 * All columns are nullable so existing records are untouched and never break.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('hr_profiles', 'mofa_new')) {
                $table->string('mofa_new', 50)->nullable()->after('previous_nationality');
            }
            if (! Schema::hasColumn('hr_profiles', 'mofa_old')) {
                $table->string('mofa_old', 50)->nullable()->after('mofa_new');
            }
            if (! Schema::hasColumn('hr_profiles', 'sect')) {
                $table->string('sect', 100)->nullable()->after('religion');
            }
            if (! Schema::hasColumn('hr_profiles', 'home_address')) {
                $table->text('home_address')->nullable()->after('phone');
            }
        });

        Schema::table('passports', function (Blueprint $table) {
            if (! Schema::hasColumn('passports', 'validity_years')) {
                $table->unsignedTinyInteger('validity_years')->nullable()->after('expiry_date');
            }
        });

        Schema::table('hr_other_infos', function (Blueprint $table) {
            if (! Schema::hasColumn('hr_other_infos', 'arrival_date_ar')) {
                $table->string('arrival_date_ar', 100)->nullable()->after('arrival_date');
            }
            if (! Schema::hasColumn('hr_other_infos', 'departure_date_ar')) {
                $table->string('departure_date_ar', 100)->nullable()->after('departure_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hr_profiles', function (Blueprint $table) {
            $table->dropColumn(['mofa_new', 'mofa_old', 'sect', 'home_address']);
        });

        Schema::table('passports', function (Blueprint $table) {
            $table->dropColumn('validity_years');
        });

        Schema::table('hr_other_infos', function (Blueprint $table) {
            $table->dropColumn(['arrival_date_ar', 'departure_date_ar']);
        });
    }
};
