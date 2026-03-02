<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sozlesme_no')->index();
            $table->date('baslangic_tarihi');
            $table->date('bitis_tarihi')->nullable();
            $table->string('taahhut_tipi', 32)->default('monthly_commitment'); // monthly_commitment, monthly_no_commitment, annual_commitment
            $table->string('faturalama_periyodu', 16)->default('monthly'); // monthly, yearly
            $table->string('durum', 32)->default('active')->index(); // active, cancelled, pending
            $table->timestamps();

            $table->index(['company_id', 'durum']);
            $table->index(['supplier_id', 'sozlesme_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
