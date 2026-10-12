<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Last Renewal Date" on the agency License page. Set automatically when Super Admin
 * extends the license expiry date (see SuperAdmin\AgencyController@update); empty until
 * the first renewal, so existing agencies show "—" rather than an invented date.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            if (! Schema::hasColumn('agencies', 'license_renewed_at')) {
                $table->date('license_renewed_at')->nullable()->after('license_expiry_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            if (Schema::hasColumn('agencies', 'license_renewed_at')) {
                $table->dropColumn('license_renewed_at');
            }
        });
    }
};
