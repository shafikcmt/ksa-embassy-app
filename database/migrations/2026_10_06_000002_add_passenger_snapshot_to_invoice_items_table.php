<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invoice lines keep the passenger's name + passport as typed/picked at billing
 * time, so passengers that exist only in ERP modules (MOFA, Medical, Stamping…)
 * and not as an HR profile still show on the invoice and its PDF.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('passenger_name')->nullable()->after('hr_profile_id');
            $table->string('passport_no', 100)->nullable()->after('passenger_name');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['passenger_name', 'passport_no']);
        });
    }
};
