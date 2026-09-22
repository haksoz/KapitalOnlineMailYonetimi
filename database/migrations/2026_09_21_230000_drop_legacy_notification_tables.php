<?php

use App\Automation\EventType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('automation_rules')) {
            foreach (DB::table('automation_rules')
                ->where('event_type', EventType::InvoiceDueApproaching->value)
                ->get(['id', 'timing']) as $row) {
                $timing = json_decode((string) $row->timing, true);
                if (! is_array($timing)) {
                    $timing = [];
                }
                if ((int) ($timing['offset_days'] ?? 0) === 0) {
                    $timing['offset_days'] = 7;
                    DB::table('automation_rules')->where('id', $row->id)->update([
                        'timing' => json_encode($timing),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        Schema::dropIfExists('notification_sends');
        Schema::dropIfExists('notification_definitions');
    }

    public function down(): void
    {
        // Eski tanım tabloları geri oluşturulmaz; metinler notification_templates içinde durur.
    }
};
