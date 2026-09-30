<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['stock_code']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unique('stock_code');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['stock_code']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('stock_code');
        });
    }
};
