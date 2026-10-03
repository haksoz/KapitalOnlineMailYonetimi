<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('quote_items')->whereNull('quantity')->update(['quantity' => 1]);

        Schema::table('quote_items', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->nullable(false)->change();
        });
    }
};
