<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_profiles', function (Blueprint $table) {
            $table->string('father_name', 150)->nullable()->after('full_name_ar');
            $table->string('mother_name', 150)->nullable()->after('father_name');
            $table->string('place_of_birth', 150)->nullable()->after('mother_name');
            $table->string('previous_nationality', 100)->nullable()->after('nationality');
        });
    }

    public function down(): void
    {
        Schema::table('hr_profiles', function (Blueprint $table) {
            $table->dropColumn(['father_name', 'mother_name', 'place_of_birth', 'previous_nationality']);
        });
    }
};
