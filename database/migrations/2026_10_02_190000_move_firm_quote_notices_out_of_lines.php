<?php

use App\Automation\QuotePlaceholders;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('notification_templates')->where('legacy_key', 'quote_firm_sent')->update([
            'body' => QuotePlaceholders::firmDocumentBody(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('notification_templates')->where('legacy_key', 'quote_firm_sent')->update([
            'body' => QuotePlaceholders::documentBody(),
            'updated_at' => now(),
        ]);
    }
};
