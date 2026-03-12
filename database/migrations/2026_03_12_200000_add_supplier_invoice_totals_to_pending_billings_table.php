<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_billings', function (Blueprint $table) {
            $table->decimal('supplier_invoice_total_tl', 14, 2)->nullable()->after('actual_alis_tl');
            $table->decimal('supplier_invoice_total_diff_tl', 14, 2)->nullable()->after('supplier_invoice_total_tl');
        });
    }

    public function down(): void
    {
        Schema::table('pending_billings', function (Blueprint $table) {
            $table->dropColumn([
                'supplier_invoice_total_tl',
                'supplier_invoice_total_diff_tl',
            ]);
        });
    }
};

