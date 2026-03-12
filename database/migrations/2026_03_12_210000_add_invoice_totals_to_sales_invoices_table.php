<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->decimal('invoice_total_net_tl', 14, 2)->nullable()->after('total_amount_tl');
            $table->decimal('invoice_total_diff_tl', 14, 2)->nullable()->after('invoice_total_net_tl');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_total_net_tl',
                'invoice_total_diff_tl',
            ]);
        });
    }
};

