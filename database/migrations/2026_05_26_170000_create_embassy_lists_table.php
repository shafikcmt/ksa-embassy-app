<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('embassy_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('list_no', 50);
            $table->string('title', 200)->nullable();
            $table->date('list_date');
            $table->enum('status', ['draft', 'finalized', 'printed', 'cancelled'])->default('draft');
            $table->unsignedSmallInteger('total_new')->default(0);
            $table->unsignedSmallInteger('total_restamping')->default(0);
            $table->unsignedSmallInteger('total_cancellation')->default(0);
            $table->unsignedSmallInteger('total_items')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['agency_id', 'list_no']);
            $table->index(['agency_id', 'status']);
            $table->index(['agency_id', 'list_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('embassy_lists');
    }
};
