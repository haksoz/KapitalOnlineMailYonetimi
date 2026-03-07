<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_billings', function (Blueprint $table) {
            $table->string('supplier_invoice_number', 64)->nullable()->after('amounts_updated_at');
            $table->date('supplier_invoice_date')->nullable()->after('supplier_invoice_number');
            $table->decimal('actual_alis_tl', 14, 2)->nullable()->after('supplier_invoice_date');
            $table->decimal('actual_satis_tl', 14, 2)->nullable()->after('actual_alis_tl');
            $table->decimal('fee_difference_tl', 14, 2)->nullable()->after('actual_satis_tl');
        });
    }

    public function down(): void
    {
        Schema::table('pending_billings', function (Blueprint $table) {
            $table->dropColumn([
                'supplier_invoice_number',
                'supplier_invoice_date',
                'actual_alis_tl',
                'actual_satis_tl',
                'fee_difference_tl',
            ]);
        });
    }
};
