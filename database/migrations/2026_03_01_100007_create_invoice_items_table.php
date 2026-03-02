<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('adet')->default(1);
            $table->decimal('usd_birim_maliyet', 12, 4)->default(0);
            $table->decimal('usd_toplam', 14, 4)->default(0);
            $table->decimal('kur', 12, 4)->default(1);
            $table->decimal('tl_toplam', 14, 2)->default(0);

            // Satış bilgisi (operasyonel)
            $table->decimal('birim_satis_fiyati', 12, 2)->nullable();
            $table->decimal('tl_satis_toplam', 14, 2)->nullable();
            $table->decimal('kar_orani', 8, 2)->nullable(); // yüzde
            $table->string('odeme_tipi', 32)->nullable(); // havale, kredikarti
            $table->decimal('fark_tutari', 14, 2)->nullable();
            $table->string('satis_fatura_no')->nullable()->index();
            $table->string('satis_durumu', 32)->nullable(); // Faturalandı, Beklemede

            $table->timestamps();

            $table->index(['subscription_id', 'created_at']);
            $table->index('supplier_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
