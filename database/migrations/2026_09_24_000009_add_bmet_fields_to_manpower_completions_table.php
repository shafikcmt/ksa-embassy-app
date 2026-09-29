<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // BMET extends the existing manpower register; reports keep the same IDs.
        Schema::table('manpower_completions', function (Blueprint $table) {
            $table->foreignId('hr_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('father_name', 100)->nullable();
            $table->string('visa_number', 100)->nullable();
            $table->string('id_number', 100)->nullable();
            $table->string('reference')->nullable();
            $table->text('remarks')->nullable();
            $table->enum('status', ['pending', 'cleared', 'expired', 'hold'])->default('pending');
            $table->date('ec_expiry_date')->nullable();
            $table->softDeletes();
            $table->index(['agency_id', 'status', 'ec_expiry_date'], 'manpower_bmet_status_index');
        });

        DB::table('manpower_completions')->orderBy('id')->chunkById(200, function ($entries) {
            foreach ($entries as $entry) {
                DB::table('manpower_completions')->where('id', $entry->id)->update([
                    'status' => filled($entry->ec_number) ? 'cleared' : 'pending',
                    'ec_expiry_date' => Carbon::parse($entry->completed_date)->addYearNoOverflow()->toDateString(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('manpower_completions', function (Blueprint $table) {
            $table->dropForeign(['hr_profile_id']);
            $table->dropIndex('manpower_bmet_status_index');
            $table->dropColumn(['hr_profile_id', 'father_name', 'visa_number', 'id_number', 'reference', 'remarks', 'status', 'ec_expiry_date', 'deleted_at']);
        });
    }
};
