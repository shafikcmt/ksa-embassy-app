<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_other_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hr_profile_id')->constrained('hr_profiles')->cascadeOnDelete();
            $table->string('contract_period', 50)->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->string('work_city', 100)->nullable();
            $table->string('employer_name', 150)->nullable();
            $table->string('employer_phone', 30)->nullable();
            $table->date('arrival_date')->nullable();
            $table->date('departure_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique('hr_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_other_infos');
    }
};
