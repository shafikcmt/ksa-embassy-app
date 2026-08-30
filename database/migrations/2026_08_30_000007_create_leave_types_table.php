<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agency leave types (Paid, Sick, Unpaid, ...). Config-like table.
 *
 * Leave *requests* (transactional) are intentionally NOT part of this phase —
 * they arrive with the attendance transactional core in a later phase.
 * SoftDeletes so a type can be retired safely.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();

            $table->string('name', 80);
            $table->boolean('is_paid')->default(true);
            $table->unsignedSmallInteger('default_days')->nullable(); // annual allotment
            $table->string('color', 20)->nullable();                  // UI badge color

            $table->softDeletes();
            $table->timestamps();

            $table->index(['agency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
