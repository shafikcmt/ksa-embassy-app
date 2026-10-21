<?php

use App\Support\UsdNote;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enjaz Dollar expenses also carry the dollar amount bought (usd_amount; taka stays in `amount`).
 * Existing Enjaz Dollar rows keep the dollar figure only inside their note ("... Top Up $10.55*1 Card"):
 * read it out into the new column (note text is left untouched). Rows with no "$" in the note stay empty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('usd_amount', 14, 2)->nullable()->after('amount');
        });

        DB::table('expenses')->where('category', 'enjaz_dollar')->whereNull('usd_amount')->whereNotNull('note')->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    if ($usd = UsdNote::parse($row->note)) {
                        DB::table('expenses')->where('id', $row->id)->update(['usd_amount' => $usd]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('usd_amount');
        });
    }
};
