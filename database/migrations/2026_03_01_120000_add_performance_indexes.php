<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add explicit indexes for performance (subscriptions, invoice_items, supplier_invoices).
     * Some may already exist via foreign keys or previous definitions; we add only missing ones.
     */
    public function up(): void
    {
        // subscriptions: index on sozlesme_no (subscription identifier), index on company_id
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!$this->indexExists('subscriptions', 'subscriptions_sozlesme_no_index')) {
                $table->index('sozlesme_no', 'subscriptions_sozlesme_no_index');
            }
            if (!$this->indexExists('subscriptions', 'subscriptions_company_id_index')) {
                $table->index('company_id', 'subscriptions_company_id_index');
            }
        });

        // invoice_items: index on subscription_id, index on supplier_invoice_id
        Schema::table('invoice_items', function (Blueprint $table) {
            if (!$this->indexExists('invoice_items', 'invoice_items_subscription_id_index')) {
                $table->index('subscription_id', 'invoice_items_subscription_id_index');
            }
            if (!$this->indexExists('invoice_items', 'invoice_items_supplier_invoice_id_index')) {
                $table->index('supplier_invoice_id', 'invoice_items_supplier_invoice_id_index');
            }
        });

        // supplier_invoices: index on supplier_id, index on fatura_no (invoice_no)
        Schema::table('supplier_invoices', function (Blueprint $table) {
            if (!$this->indexExists('supplier_invoices', 'supplier_invoices_supplier_id_index')) {
                $table->index('supplier_id', 'supplier_invoices_supplier_id_index');
            }
            if (!$this->indexExists('supplier_invoices', 'supplier_invoices_fatura_no_index')) {
                $table->index('fatura_no', 'supplier_invoices_fatura_no_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex('subscriptions_sozlesme_no_index');
            $table->dropIndex('subscriptions_company_id_index');
        });
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex('invoice_items_subscription_id_index');
            $table->dropIndex('invoice_items_supplier_invoice_id_index');
        });
        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->dropIndex('supplier_invoices_supplier_id_index');
            $table->dropIndex('supplier_invoices_fatura_no_index');
        });
    }

    private function indexExists(string $table, string $name): bool
    {
        $indexes = Schema::getIndexListing($table);
        return in_array($name, $indexes, true);
    }
};
