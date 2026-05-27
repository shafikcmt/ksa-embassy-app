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
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('license_number')->nullable();
            $table->string('rl_number')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('logo')->nullable();
            $table->date('license_expiry_date')->nullable();
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agencies');
    }
};
