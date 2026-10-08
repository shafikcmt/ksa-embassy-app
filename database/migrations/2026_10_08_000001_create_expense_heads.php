<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_heads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('code', 80);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['agency_id', 'code']);
            $table->unique(['agency_id', 'name']);
        });
        foreach (['expenses', 'payment_vouchers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('expense_head_id')->nullable()->constrained('expense_heads')->restrictOnDelete();
                $table->index(['agency_id', 'expense_head_id']);
            });
        }

        // Frozen migration definitions: never infer classifications from free-text voucher descriptions.
        $defaults = [
            'manpower' => 'Manpower Cost', 'salary' => 'Salary', 'staff_advance' => 'Staff Advance',
            'office_rent' => 'Office Rent', 'utilities' => 'Utility Bill', 'travel' => 'Transport / Conveyance',
            'food_entertainment' => 'Food / Entertainment', 'medical' => 'Medical Expense',
            'embassy_visa' => 'Embassy / Visa Processing', 'government_fees' => 'BMET / Government Fee',
            'air_ticket' => 'Ticket / Travel Expense', 'supplier_payment' => 'Supplier Payment',
            'office_supplies' => 'Office Supplies', 'maintenance' => 'Maintenance / Repair',
            'printing_stationery' => 'Printing / Stationery', 'marketing' => 'Marketing',
            'bank_charge' => 'Bank Charge', 'enjaz_dollar' => 'Enjaz Dollar', 'other' => 'Miscellaneous / Others',
            'payment_voucher' => 'Payment Voucher',
        ];
        DB::table('agencies')->orderBy('id')->chunkById(100, function ($agencies) use ($defaults) {
            foreach ($agencies as $agency) {
                foreach ($defaults as $code => $name) {
                    $headId = DB::table('expense_heads')->insertGetId([
                        'agency_id' => $agency->id, 'code' => $code, 'name' => $name,
                        'is_active' => $code !== 'payment_voucher', 'is_system' => $code === 'payment_voucher',
                        'sort_order' => (array_search($code, array_keys($defaults), true) + 1) * 10,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    DB::table('expenses')->where('agency_id', $agency->id)->where('category', $code)
                        ->whereNull('expense_head_id')->update(['expense_head_id' => $headId]);
                    if ($code === 'payment_voucher') {
                        DB::table('payment_vouchers')->where('agency_id', $agency->id)
                            ->whereNull('expense_head_id')->update(['expense_head_id' => $headId]);
                    }
                }
            }
        });
        // If a historical voucher Expense has a known meaningful category, carry it to its voucher.
        DB::table('expenses')->whereNotNull('payment_voucher_id')->whereNotNull('expense_head_id')
            ->orderBy('id')->chunkById(100, function ($expenses) {
                foreach ($expenses as $expense) {
                    DB::table('payment_vouchers')->where('agency_id', $expense->agency_id)
                        ->where('id', $expense->payment_voucher_id)->update(['expense_head_id' => $expense->expense_head_id]);
                }
            });
    }

    public function down(): void
    {
        foreach (['expenses', 'payment_vouchers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['expense_head_id']);
                $table->dropIndex(['agency_id', 'expense_head_id']);
                $table->dropColumn('expense_head_id');
            });
        }
        Schema::dropIfExists('expense_heads');
    }
};
