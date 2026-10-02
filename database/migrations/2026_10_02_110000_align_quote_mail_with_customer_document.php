<?php

use App\Automation\QuotePlaceholders;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $body = QuotePlaceholders::documentBody();
        $now = now();

        DB::table('notification_templates')->where('legacy_key', 'quote_optional_sent')->update([
            'subject' => 'Birim fiyat teklifiniz: {teklif_no}',
            'body' => $body,
            'updated_at' => $now,
        ]);
        DB::table('notification_templates')->where('legacy_key', 'quote_firm_sent')->update([
            'subject' => 'Kesin teklifiniz: {teklif_no}',
            'body' => $body,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        $now = now();
        DB::table('notification_templates')->where('legacy_key', 'quote_optional_sent')->update([
            'subject' => 'Birim fiyat teklifiniz: {teklif_no}',
            'body' => "Sayın {musteri},\n\n{teklif_no} numaralı birim fiyat teklifimiz aşağıdadır.\nGeçerlilik: {gecerlilik}\n\n{kalemler}\n\n{not}",
            'updated_at' => $now,
        ]);
        DB::table('notification_templates')->where('legacy_key', 'quote_firm_sent')->update([
            'subject' => 'Kesin teklifiniz: {teklif_no}',
            'body' => "Sayın {musteri},\n\n{teklif_no} numaralı kesin teklifimiz aşağıdadır.\nGeçerlilik: {gecerlilik}\n\n{kalemler}\n\n{not}",
            'updated_at' => $now,
        ]);
    }
};
