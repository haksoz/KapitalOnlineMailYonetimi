<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('fatura_no')->index();
            $table->date('fatura_tarihi');
            $table->unsignedTinyInteger('donem_ay'); // 1-12
            $table->unsignedSmallInteger('donem_yil');
            $table->string('para_birimi', 3)->default('TRY');
            $table->string('xml_path')->nullable();
            $table->timestamps();

            $table->unique(['supplier_id', 'fatura_no']);
            $table->index(['donem_yil', 'donem_ay']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_invoices');
    }
};
