<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_definitions', function (Blueprint $table) {
            $table->time('send_at')->default('10:00:00')->after('interval_days');
        });

        DB::table('notification_definitions')->where('key', 'invoice_due_reminder')->update(['send_at' => '10:00:00']);
        DB::table('notification_definitions')->where('key', 'invoice_overdue')->update(['send_at' => '14:30:00']);
    }

    public function down(): void
    {
        Schema::table('notification_definitions', function (Blueprint $table) {
            $table->dropColumn('send_at');
        });
    }
};
