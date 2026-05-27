<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_other_infos', function (Blueprint $table) {
            $table->string('duration_stay_en', 100)->nullable()->after('contract_period');
            $table->string('duration_stay_ar', 100)->nullable()->after('duration_stay_en');
            $table->string('destination_city', 100)->nullable()->after('work_city');
            $table->string('relationship', 100)->nullable()->after('employer_phone');
            $table->string('carrier', 100)->nullable()->after('relationship');
            $table->string('payment_mode', 50)->nullable()->after('carrier');
        });
    }

    public function down(): void
    {
        Schema::table('hr_other_infos', function (Blueprint $table) {
            $table->dropColumn([
                'duration_stay_en', 'duration_stay_ar', 'destination_city',
                'relationship', 'carrier', 'payment_mode',
            ]);
        });
    }
};
