<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_cari_id')->constrained('caris')->cascadeOnDelete();
            $table->string('our_invoice_number', 64)->nullable();
            $table->date('our_invoice_date')->nullable();
            $table->decimal('total_amount_tl', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('customer_cari_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};
