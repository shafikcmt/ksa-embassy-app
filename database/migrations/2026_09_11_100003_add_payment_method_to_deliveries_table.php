<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task 5 — add an optional Payment Method (cash/bank/online) to deliveries as a
 * SEPARATE field from the workflow `status` enum (which stays pending/ready/
 * delivered). Additive + nullable, so the existing status logic is untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
