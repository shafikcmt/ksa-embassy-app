<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('max_hr')->default(50);
            $table->unsignedInteger('max_users')->default(3);
            $table->unsignedInteger('max_agents')->default(10);
            $table->unsignedInteger('max_embassy_lists_monthly')->default(10);
            $table->unsignedInteger('max_pdf_monthly')->default(100);
            $table->unsignedInteger('storage_limit_mb')->default(512);
            $table->unsignedInteger('duration_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->json('features')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
