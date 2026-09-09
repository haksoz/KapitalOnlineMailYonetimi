<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->boolean('is_paid')->default(false)->after('notes');
            $table->timestamp('paid_at')->nullable()->after('is_paid');

            $table->index('is_paid');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropIndex(['is_paid']);
            $table->dropColumn(['is_paid', 'paid_at']);
        });
    }
};
