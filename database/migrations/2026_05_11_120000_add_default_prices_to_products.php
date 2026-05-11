<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('default_alis_usd', 10, 2)->nullable()->after('description')->comment('Varsayilan alis USD fiyati');
            $table->decimal('default_satis_usd', 10, 2)->nullable()->after('default_alis_usd')->comment('Varsayilan satis USD fiyati');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['default_alis_usd', 'default_satis_usd']);
        });
    }
};
