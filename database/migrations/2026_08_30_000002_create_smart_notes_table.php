<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smart_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 200);
            $table->text('body')->nullable();
            $table->string('category', 20)->default('general');   // general/important/office/accounts/embassy/mofa/agent/client/personal
            $table->string('priority', 10)->default('medium');    // urgent/high/medium/low
            $table->string('status', 10)->default('pending');     // pending/completed
            $table->boolean('is_private')->default(false);
            $table->boolean('pinned')->default(false);
            $table->dateTime('reminder_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['agency_id', 'status']);
            $table->index(['agency_id', 'pinned']);
            $table->index(['agency_id', 'reminder_at']);
            $table->index(['agency_id', 'archived_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_notes');
    }
};
