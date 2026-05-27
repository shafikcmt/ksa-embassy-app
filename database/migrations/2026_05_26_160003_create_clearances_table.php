<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hr_profile_id')->constrained('hr_profiles')->cascadeOnDelete();
            $table->string('police_clearance_number', 100)->nullable();
            $table->date('clearance_issue_date')->nullable();
            $table->date('clearance_expiry_date')->nullable();
            $table->string('clearance_country', 100)->nullable();
            $table->boolean('medical_fit')->default(false);
            $table->date('medical_date')->nullable();
            $table->string('medical_center', 150)->nullable();
            $table->timestamps();

            $table->unique('hr_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearances');
    }
};
