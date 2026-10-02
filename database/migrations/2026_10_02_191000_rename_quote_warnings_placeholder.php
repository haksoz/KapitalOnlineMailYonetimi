<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('notification_templates')
            ->where('legacy_key', 'quote_firm_sent')
            ->update([
                'body' => DB::raw("REPLACE(body, '{uyarilar}', '{dinamik_kosullar}')"),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('notification_templates')
            ->where('legacy_key', 'quote_firm_sent')
            ->update([
                'body' => DB::raw("REPLACE(body, '{dinamik_kosullar}', '{uyarilar}')"),
                'updated_at' => now(),
            ]);
    }
};
