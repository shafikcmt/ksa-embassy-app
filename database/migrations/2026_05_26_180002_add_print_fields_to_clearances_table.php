<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clearances', function (Blueprint $table) {
            $table->string('license_type', 100)->nullable()->after('clearance_country');
            $table->text('pc_qr_code')->nullable()->after('license_type');
            $table->string('fingerprint', 100)->nullable()->after('pc_qr_code');
        });
    }

    public function down(): void
    {
        Schema::table('clearances', function (Blueprint $table) {
            $table->dropColumn(['license_type', 'pc_qr_code', 'fingerprint']);
        });
    }
};
