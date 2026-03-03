<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caris', function (Blueprint $table) {
            if (!Schema::hasColumn('caris', 'short_name')) {
                $table->string('short_name', 100)->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('caris', function (Blueprint $table) {
            if (Schema::hasColumn('caris', 'short_name')) {
                $table->dropColumn('short_name');
            }
        });
    }
};
