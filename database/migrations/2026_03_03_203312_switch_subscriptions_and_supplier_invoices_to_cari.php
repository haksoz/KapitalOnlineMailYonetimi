<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('customer_cari_id')->nullable()->after('id')->constrained('caris')->nullOnDelete();
            $table->foreignId('provider_cari_id')->nullable()->after('customer_cari_id')->constrained('caris')->nullOnDelete();
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->index(['customer_cari_id', 'durum']);
            $table->index(['provider_cari_id', 'sozlesme_no']);
        });

        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->foreignId('cari_id')->nullable()->after('id')->constrained('caris')->nullOnDelete();
        });
        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->dropUnique(['supplier_id', 'fatura_no']);
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');
        });
        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->unique(['cari_id', 'fatura_no'], 'supplier_invoices_cari_id_fatura_no_unique');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['customer_cari_id', 'durum']);
            $table->dropIndex(['provider_cari_id', 'sozlesme_no']);
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['customer_cari_id']);
            $table->dropForeign(['provider_cari_id']);
            $table->dropColumn(['customer_cari_id', 'provider_cari_id']);
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('company_id')->after('id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->after('product_id')->constrained('suppliers')->nullOnDelete();
            $table->index(['company_id', 'durum']);
            $table->index(['supplier_id', 'sozlesme_no']);
        });

        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->dropUnique('supplier_invoices_cari_id_fatura_no_unique');
            $table->dropForeign(['cari_id']);
            $table->dropColumn('cari_id');
            $table->foreignId('supplier_id')->after('id')->constrained('suppliers')->cascadeOnDelete();
            $table->unique(['supplier_id', 'fatura_no']);
        });
    }
};
