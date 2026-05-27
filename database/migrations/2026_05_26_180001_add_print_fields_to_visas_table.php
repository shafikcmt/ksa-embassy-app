<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visas', function (Blueprint $table) {
            $table->string('issue_place_ar', 150)->nullable()->after('issue_place');
            $table->string('profession_en', 100)->nullable()->after('issue_place_ar');
            $table->string('profession_ar', 100)->nullable()->after('profession_en');
            $table->string('qualification_en', 100)->nullable()->after('profession_ar');
            $table->string('qualification_ar', 100)->nullable()->after('qualification_en');
            $table->string('travel_purpose', 100)->nullable()->after('qualification_ar');
            $table->string('musaned_no', 50)->nullable()->after('travel_purpose');
            $table->string('wakala_no', 50)->nullable()->after('musaned_no');
        });
    }

    public function down(): void
    {
        Schema::table('visas', function (Blueprint $table) {
            $table->dropColumn([
                'issue_place_ar', 'profession_en', 'profession_ar',
                'qualification_en', 'qualification_ar', 'travel_purpose',
                'musaned_no', 'wakala_no',
            ]);
        });
    }
};
