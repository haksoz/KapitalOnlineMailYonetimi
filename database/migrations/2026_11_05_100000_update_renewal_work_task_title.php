<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('work_tasks')
            ->where('type', 'subscription_renewal')
            ->where('title', 'Abonelik yenileme teklifini hazırla')
            ->update(['title' => 'Abonelik bitiyor. Yenileme teklifi hazırla.']);
    }

    public function down(): void
    {
        DB::table('work_tasks')
            ->where('type', 'subscription_renewal')
            ->where('title', 'Abonelik bitiyor. Yenileme teklifi hazırla.')
            ->update(['title' => 'Abonelik yenileme teklifini hazırla']);
    }
};
