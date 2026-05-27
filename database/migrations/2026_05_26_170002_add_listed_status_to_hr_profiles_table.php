<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE hr_profiles MODIFY COLUMN status ENUM('active','inactive','blacklisted','listed') DEFAULT 'active'");
    }

    public function down(): void
    {
        // Revert listed back to active before removing the enum value
        DB::statement("UPDATE hr_profiles SET status = 'active' WHERE status = 'listed'");
        DB::statement("ALTER TABLE hr_profiles MODIFY COLUMN status ENUM('active','inactive','blacklisted') DEFAULT 'active'");
    }
};
