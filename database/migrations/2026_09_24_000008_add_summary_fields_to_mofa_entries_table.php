<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mofa_entries', function (Blueprint $table) {
            $table->foreignId('hr_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('father_name', 100)->nullable();
            $table->string('mother_name', 100)->nullable();
            foreach (['date_of_birth', 'issue_date', 'expiry_date', 'mofa_issue_date', 'mofa_expiry_date'] as $field) {
                $table->date($field)->nullable();
            }
            $table->text('remarks')->nullable();
            $table->softDeletes();
            $table->index(['agency_id', 'mofa_expiry_date']);
            $table->date('mofa_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('mofa_entries', function (Blueprint $table) {
            $table->dropForeign(['hr_profile_id']);
            $table->dropIndex(['agency_id', 'mofa_expiry_date']);
            $table->dropColumn(['hr_profile_id', 'father_name', 'mother_name', 'date_of_birth', 'issue_date', 'expiry_date', 'mofa_issue_date', 'mofa_expiry_date', 'remarks', 'deleted_at']);
        });
    }
};
