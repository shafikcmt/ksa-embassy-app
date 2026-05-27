<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hr_profile_id')->constrained('hr_profiles')->cascadeOnDelete();
            $table->string('visa_number', 50)->nullable();
            $table->string('visa_type', 100)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('issue_place', 150)->nullable();
            $table->string('sponsor_name', 150)->nullable();
            $table->string('sponsor_id', 50)->nullable();
            $table->string('border_number', 50)->nullable();
            $table->timestamps();

            $table->unique('hr_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visas');
    }
};
