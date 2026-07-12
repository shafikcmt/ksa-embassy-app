<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Visa Date is entered/printed exactly as written on the visa sticker
     * (Hijri, Gregorian, any format), so store it as a freeform string
     * instead of a real date. Existing date values convert to strings.
     */
    public function up(): void
    {
        Schema::table('visas', function (Blueprint $table) {
            $table->string('issue_date', 30)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('visas', function (Blueprint $table) {
            $table->date('issue_date')->nullable()->change();
        });
    }
};
