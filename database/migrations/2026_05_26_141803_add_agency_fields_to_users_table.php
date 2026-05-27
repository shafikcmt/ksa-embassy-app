<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('agency_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->boolean('is_super_admin')->default(false)->after('agency_id');
            $table->boolean('is_active')->default(true)->after('is_super_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['agency_id']);
            $table->dropColumn(['agency_id', 'is_super_admin', 'is_active']);
        });
    }
};
