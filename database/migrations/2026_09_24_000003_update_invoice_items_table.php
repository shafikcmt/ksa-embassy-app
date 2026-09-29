<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Converts invoice_items from a generic description/qty/unit-price model to the
 * KSA recruitment reference format: per-passenger processing_fee + mofa_fee →
 * total_amount (auto), paid_amount (optional), due_amount (auto), remarks.
 *
 * total_amount and due_amount are derived columns set by InvoiceItem::booted().
 */
return new class extends Migration
{
    public function up(): void
    {
        // MySQL uses the composite (invoice_id, sort_order) index to back the FK on
        // invoice_id because no single-column invoice_id index exists. We must add
        // a plain one FIRST so MySQL has a valid FK-backing index when we drop the
        // composite index in the next statement.
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->index('invoice_id', 'invoice_items_invoice_id_plain');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex('invoice_items_invoice_id_sort_order_index');

            // Remove old generic columns.
            $table->dropColumn(['description', 'quantity', 'unit_price', 'amount', 'sort_order']);

            // Add recruitment-format columns.
            $table->decimal('processing_fee', 14, 2)->default(0)->after('hr_profile_id');
            $table->decimal('mofa_fee', 14, 2)->default(0)->after('processing_fee');
            $table->decimal('total_amount', 14, 2)->default(0)->after('mofa_fee');
            $table->decimal('paid_amount', 14, 2)->nullable()->after('total_amount');
            $table->decimal('due_amount', 14, 2)->default(0)->after('paid_amount');
            $table->text('remarks')->nullable()->after('due_amount');

        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex(['invoice_id']);
            $table->dropColumn(['processing_fee', 'mofa_fee', 'total_amount', 'paid_amount', 'due_amount', 'remarks']);

            $table->string('description')->after('hr_profile_id');
            $table->unsignedInteger('quantity')->default(1)->after('description');
            $table->decimal('unit_price', 14, 2)->after('quantity');
            $table->decimal('amount', 14, 2)->after('unit_price');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('amount');
            $table->index(['invoice_id', 'sort_order']);
        });
    }
};
