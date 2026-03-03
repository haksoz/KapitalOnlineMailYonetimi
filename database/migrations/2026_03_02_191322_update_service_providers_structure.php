<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_providers', function (Blueprint $table) {
            // Eski code index'ini kaldırıp unique yapacağız
            $table->dropIndex('service_providers_code_index');

            // Platform / marka türü (mail, domain, hosting vb.)
            $table->string('service_type', 50)->nullable()->after('code');

            // Kısa kod benzersiz olmalı
            $table->unique('code', 'service_providers_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('service_providers', function (Blueprint $table) {
            // Unique index'i kaldır, eski normal index'i geri koy
            $table->dropUnique('service_providers_code_unique');
            $table->dropColumn('service_type');
            $table->index('code', 'service_providers_code_index');
        });
    }
};
