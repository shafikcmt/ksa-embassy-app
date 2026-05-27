<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->string('file_number', 50)->nullable();
            $table->string('full_name_en', 150);
            $table->string('full_name_ar', 150)->nullable();
            $table->string('nationality', 100);
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female']);
            $table->string('religion', 100)->nullable();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->string('occupation', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->enum('status', ['active', 'inactive', 'blacklisted'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['agency_id', 'file_number']);
            $table->unique(['agency_id', 'email']);
            $table->index(['agency_id', 'status']);
            $table->index(['agency_id', 'agent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_profiles');
    }
};
