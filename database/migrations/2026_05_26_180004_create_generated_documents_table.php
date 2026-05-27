<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->nullable()->constrained('agencies')->nullOnDelete();
            $table->foreignId('hr_profile_id')->nullable()->constrained('hr_profiles')->cascadeOnDelete();
            $table->foreignId('embassy_list_id')->nullable()->constrained('embassy_lists')->cascadeOnDelete();
            $table->enum('document_type', [
                'application', 'forwarding_letter', 'employment_agreement',
                'checklist', 'full_file', 'embassy_list',
            ]);
            $table->enum('action', ['preview', 'download']);
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['agency_id', 'action']);
            $table->index(['agency_id', 'created_at']);
            $table->index('hr_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
    }
};
