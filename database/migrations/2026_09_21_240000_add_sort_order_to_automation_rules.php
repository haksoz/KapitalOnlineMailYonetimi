<?php

use App\Automation\EventType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('automation_rules')) {
            return;
        }

        if (! Schema::hasColumn('automation_rules', 'sort_order')) {
            Schema::table('automation_rules', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0)->after('dedupe_policy');
                $table->index('sort_order');
            });
        }

        foreach (DB::table('automation_rules')->orderBy('id')->get(['id', 'event_type']) as $row) {
            $event = EventType::tryFrom((string) $row->event_type);
            DB::table('automation_rules')->where('id', $row->id)->update([
                'sort_order' => $event?->processSort() ?? ((int) $row->id * 10),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('automation_rules') || ! Schema::hasColumn('automation_rules', 'sort_order')) {
            return;
        }

        Schema::table('automation_rules', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
