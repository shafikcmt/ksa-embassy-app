<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Unchanged: the exact statement production already ran.
            DB::statement("ALTER TABLE hr_profiles MODIFY COLUMN status ENUM('active','inactive','blacklisted','listed') DEFAULT 'active'");

            return;
        }

        // Other drivers (SQLite in tests): same allowed values via the portable
        // schema builder, so 'listed' passes SQLite's enum CHECK constraint.
        Schema::table('hr_profiles', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive', 'blacklisted', 'listed'])->default('active')->change();
        });
    }

    public function down(): void
    {
        // Revert listed back to active before removing the enum value
        DB::statement("UPDATE hr_profiles SET status = 'active' WHERE status = 'listed'");

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE hr_profiles MODIFY COLUMN status ENUM('active','inactive','blacklisted') DEFAULT 'active'");

            return;
        }

        Schema::table('hr_profiles', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive', 'blacklisted'])->default('active')->change();
        });
    }
};
