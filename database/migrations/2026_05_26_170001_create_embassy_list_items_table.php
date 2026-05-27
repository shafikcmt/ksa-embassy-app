<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('embassy_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('embassy_list_id')->constrained('embassy_lists')->cascadeOnDelete();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('hr_profile_id')->constrained('hr_profiles')->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->enum('category', ['new', 'restamping', 'cancellation']);
            $table->unsignedSmallInteger('serial_no')->default(0);
            $table->string('snapshot_agent_name', 150)->nullable();
            $table->string('snapshot_candidate_name', 150);
            $table->string('snapshot_candidate_name_ar', 150)->nullable();
            $table->string('snapshot_passport_no', 50)->nullable();
            $table->string('snapshot_visa_no', 50)->nullable();
            $table->string('snapshot_profession_en', 100)->nullable();
            $table->string('snapshot_profession_ar', 100)->nullable();
            $table->string('snapshot_sponsor_name', 150)->nullable();
            $table->string('snapshot_sponsor_id', 50)->nullable();
            $table->string('snapshot_nationality', 100)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['embassy_list_id', 'hr_profile_id']);
            $table->index(['embassy_list_id', 'category']);
            $table->index('agency_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('embassy_list_items');
    }
};
