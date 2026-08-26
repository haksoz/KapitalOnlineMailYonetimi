<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('currency', 3)->default('USD')->after('description');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('currency', 3)->default('USD')->after('vat_rate');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('currency');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
