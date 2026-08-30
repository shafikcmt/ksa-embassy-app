<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agency work shifts (company default + alternates).
 *
 * Config-like table. SoftDeletes so a shift can be retired without breaking any
 * future record that referenced it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();

            $table->string('name', 80);
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_default')->default(false); // one default per agency (enforced in controller)

            $table->softDeletes();
            $table->timestamps();

            $table->index(['agency_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
