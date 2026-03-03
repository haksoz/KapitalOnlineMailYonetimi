<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->decimal('usd_birim_alis', 12, 4)->nullable()->after('auto_renew');
            $table->decimal('usd_birim_satis', 12, 4)->nullable()->after('usd_birim_alis');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['usd_birim_alis', 'usd_birim_satis']);
        });
    }
};
