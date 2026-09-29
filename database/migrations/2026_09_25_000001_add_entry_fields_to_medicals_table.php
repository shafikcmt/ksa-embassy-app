<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Medical Entry upgrade — extends the existing ERP `medicals` log (no new
 * table) with the Medical Summary reference columns: D.O.B + stored Age,
 * Country, Mobile, Reference, Remarks, an optional HR-profile link, soft
 * deletes, and an 'expired' status.
 *
 * Every new column is nullable so rows created before this migration (and old
 * CSV imports) stay valid; "required" is enforced by MedicalEntryRequest.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicals', function (Blueprint $table) {
            $table->foreignId('hr_profile_id')->nullable()->after('agency_id')
                ->constrained('hr_profiles')->nullOnDelete();
            $table->date('date_of_birth')->nullable()->after('passport_no');
            $table->unsignedTinyInteger('age')->nullable()->after('date_of_birth');
            $table->string('country', 100)->nullable()->after('medical_center_name');
            $table->string('mobile_no', 30)->nullable()->after('medical_status');
            $table->string('reference')->nullable()->after('mobile_no');
            $table->text('remarks')->nullable()->after('reference');
            $table->softDeletes();
        });

        Schema::table('medicals', function (Blueprint $table) {
            $table->enum('medical_status', ['pending', 'process', 'under_review', 'fit', 'unfit', 'expired'])
                ->default('pending')->change();
        });
    }

    public function down(): void
    {
        // Rows marked 'expired' must fall back to a value the old enum accepts.
        DB::table('medicals')->where('medical_status', 'expired')->update(['medical_status' => 'unfit']);

        Schema::table('medicals', function (Blueprint $table) {
            $table->enum('medical_status', ['pending', 'process', 'under_review', 'fit', 'unfit'])
                ->default('pending')->change();
        });

        Schema::table('medicals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hr_profile_id');
            $table->dropColumn(['date_of_birth', 'age', 'country', 'mobile_no', 'reference', 'remarks']);
            $table->dropSoftDeletes();
        });
    }
};
