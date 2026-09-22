<?php

use App\Automation\EventType;
use App\Automation\TimingMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('automation_rules')) {
            return;
        }

        foreach (DB::table('automation_rules')->orderBy('id')->get(['id', 'event_type', 'timing']) as $row) {
            $timing = json_decode((string) $row->timing, true);
            if (! is_array($timing)) {
                $timing = [];
            }
            if (isset($timing['mode']) && is_string($timing['mode']) && TimingMode::tryFrom($timing['mode']) !== null) {
                continue;
            }

            $event = EventType::tryFrom((string) $row->event_type);
            $timing['mode'] = ($event?->defaultTimingMode() ?? TimingMode::AtSendAt)->value;

            DB::table('automation_rules')->where('id', $row->id)->update([
                'timing' => json_encode($timing),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('automation_rules')) {
            return;
        }

        foreach (DB::table('automation_rules')->orderBy('id')->get(['id', 'timing']) as $row) {
            $timing = json_decode((string) $row->timing, true);
            if (! is_array($timing) || ! array_key_exists('mode', $timing)) {
                continue;
            }
            unset($timing['mode']);
            DB::table('automation_rules')->where('id', $row->id)->update([
                'timing' => json_encode($timing),
                'updated_at' => now(),
            ]);
        }
    }
};
