<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Visa Stamping — extends the existing E1 `stampings` log (no duplicate table)
 * with the Visa Stamping Summary reference columns: Father/Mother name, D.O.B,
 * MOFA No/Date, Issued Visa No, Issue/Expiry date, Remarks, optional HR-profile
 * and agent links, soft deletes, and the completed/expired/rejected statuses.
 *
 * `stamp_date` keeps serving as the Stamping Date. Age and Left Day are NOT
 * stored — VisaStamping computes them on read so they never go stale (same
 * approach as MofaEntry). Every new column is nullable so existing rows and the
 * old CSV import stay valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stampings', function (Blueprint $table) {
            $table->foreignId('hr_profile_id')->nullable()->after('agency_id')->constrained('hr_profiles')->nullOnDelete();
            $table->foreignId('agent_id')->nullable()->after('hr_profile_id')->constrained('agents')->nullOnDelete();
            $table->string('father_name', 100)->nullable()->after('full_name');
            $table->string('mother_name', 100)->nullable()->after('father_name');
            $table->date('date_of_birth')->nullable()->after('passport_no');
            $table->string('mofa_number', 100)->nullable()->after('id_number');
            $table->date('mofa_date')->nullable()->after('mofa_number');
            $table->string('issued_visa_number', 100)->nullable()->after('mofa_date');
            $table->date('issued_date')->nullable()->after('issued_visa_number');
            $table->date('expiry_date')->nullable()->after('issued_date');
            $table->text('remarks')->nullable()->after('reference');
            $table->softDeletes();

            $table->index(['agency_id', 'expiry_date']);
        });

        Schema::table('stampings', function (Blueprint $table) {
            $table->enum('status', ['pending', 'processing', 'completed', 'stamped', 'expired', 'rejected'])
                ->default('pending')->change();
        });
    }

    public function down(): void
    {
        // Statuses the old enum doesn't know fall back to the nearest old value.
        DB::table('stampings')->where('status', 'completed')->update(['status' => 'stamped']);
        DB::table('stampings')->whereIn('status', ['expired', 'rejected'])->update(['status' => 'pending']);

        Schema::table('stampings', function (Blueprint $table) {
            $table->enum('status', ['pending', 'processing', 'stamped'])->default('pending')->change();
        });

        Schema::table('stampings', function (Blueprint $table) {
            $table->dropIndex(['agency_id', 'expiry_date']);
            $table->dropConstrainedForeignId('hr_profile_id');
            $table->dropConstrainedForeignId('agent_id');
            $table->dropColumn([
                'father_name', 'mother_name', 'date_of_birth', 'mofa_number', 'mofa_date',
                'issued_visa_number', 'issued_date', 'expiry_date', 'remarks',
            ]);
            $table->dropSoftDeletes();
        });
    }
};
