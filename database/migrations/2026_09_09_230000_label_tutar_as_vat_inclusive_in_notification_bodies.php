<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $definitions = DB::table('notification_definitions')->select('id', 'body')->get();

        foreach ($definitions as $definition) {
            $body = (string) $definition->body;
            $updated = str_replace('Tutar: {tutar}', 'Tutar (KDV dahil): {tutar}', $body);
            if ($updated === $body) {
                continue;
            }

            DB::table('notification_definitions')
                ->where('id', $definition->id)
                ->update(['body' => $updated]);
        }
    }

    public function down(): void
    {
        $definitions = DB::table('notification_definitions')->select('id', 'body')->get();

        foreach ($definitions as $definition) {
            $body = (string) $definition->body;
            $updated = str_replace('Tutar (KDV dahil): {tutar}', 'Tutar: {tutar}', $body);
            if ($updated === $body) {
                continue;
            }

            DB::table('notification_definitions')
                ->where('id', $definition->id)
                ->update(['body' => $updated]);
        }
    }
};
