<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - Adds a `currency` column to plans (default BDT) and converts the seeded
     *   USD demo plans to Bangladeshi Taka pricing.
     * - Backfills a unique system license number (LIC-AGY-####) for any agency
     *   that doesn't already have one, so existing agencies are not broken.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('plans', 'currency')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->string('currency', 10)->default('BDT')->after('price');
            });
        }

        // Convert known plans to BDT pricing; default everything else to BDT too.
        DB::table('plans')->update(['currency' => 'BDT']);
        $bdtPrices = ['trial' => 0, 'basic' => 2000, 'standard' => 4000, 'premium' => 7000];
        foreach ($bdtPrices as $slug => $price) {
            DB::table('plans')->where('slug', $slug)->update(['price' => $price]);
        }

        // Backfill missing system license numbers (LIC-AGY-0001, ...).
        $agencies = DB::table('agencies')
            ->where(fn ($q) => $q->whereNull('license_number')->orWhere('license_number', ''))
            ->orderBy('id')
            ->get(['id']);

        foreach ($agencies as $agency) {
            DB::table('agencies')->where('id', $agency->id)->update([
                'license_number' => 'LIC-AGY-' . str_pad((string) $agency->id, 4, '0', STR_PAD_LEFT),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('plans', 'currency')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }
    }
};
