<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds editable Agency Profile fields used by the agency Settings page:
     *  - owner_name  : contact person / agency owner name (distinct from the
     *                  licensed company name stored in `name`).
     *  - print_logo  : whether the agency logo should be shown on printed /
     *                  exported PDF documents. Defaults to true.
     */
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            if (! Schema::hasColumn('agencies', 'owner_name')) {
                $table->string('owner_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('agencies', 'print_logo')) {
                $table->boolean('print_logo')->default(true)->after('logo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            foreach (['owner_name', 'print_logo'] as $column) {
                if (Schema::hasColumn('agencies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
