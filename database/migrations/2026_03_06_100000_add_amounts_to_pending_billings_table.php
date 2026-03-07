<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_billings', function (Blueprint $table) {
            $table->decimal('expected_alis_tl', 14, 2)->nullable()->after('status');
            $table->decimal('expected_satis_tl', 14, 2)->nullable()->after('expected_alis_tl');
            $table->decimal('exchange_rate_used', 14, 6)->nullable()->after('expected_satis_tl');
            $table->timestamp('amounts_updated_at')->nullable()->after('exchange_rate_used');
        });
    }

    public function down(): void
    {
        Schema::table('pending_billings', function (Blueprint $table) {
            $table->dropColumn(['expected_alis_tl', 'expected_satis_tl', 'exchange_rate_used', 'amounts_updated_at']);
        });
    }
};
