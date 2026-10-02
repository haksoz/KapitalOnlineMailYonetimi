<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $row = DB::table('notification_templates')->where('legacy_key', 'quote_firm_sent')->first();
        if ($row === null || ! str_contains((string) $row->body, '{alici} için hazırladığımız')) {
            return;
        }

        DB::table('notification_templates')->where('id', $row->id)->update([
            'body' => str_replace('{alici} için hazırladığımız', '{cari_unvani} için hazırladığımız', (string) $row->body),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $row = DB::table('notification_templates')->where('legacy_key', 'quote_firm_sent')->first();
        if ($row === null || ! str_contains((string) $row->body, '{cari_unvani} için hazırladığımız')) {
            return;
        }

        DB::table('notification_templates')->where('id', $row->id)->update([
            'body' => str_replace('{cari_unvani} için hazırladığımız', '{alici} için hazırladığımız', (string) $row->body),
            'updated_at' => now(),
        ]);
    }
};
