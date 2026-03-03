<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caris', function (Blueprint $table) {
            $table->id();

            // Kalıcı, sistem içi ve API kimliği
            $table->uuid('uuid')->unique();

            // Firma / kişi adı
            $table->string('name');

            // ISO ülke kodu (TR, DE, US ...)
            $table->char('country_code', 2)->default('TR');

            // Uluslararası vergi / kimlik numarası (VKN, VAT, EIN vb.)
            $table->string('tax_number', 50)->nullable();

            // customer, supplier, both vb.
            $table->string('cari_type', 32)->nullable();

            $table->timestamps();

            // tax_number dolu olduğunda (country_code, tax_number) kombinasyonu tekil olmalı.
            // MySQL'de NULL değerler unique index içinde birden fazla satıra izin verir;
            // böylece tax_number boş olan kayıtlar için kısıt yoktur.
            $table->unique(['country_code', 'tax_number'], 'caris_country_tax_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caris');
    }
};
