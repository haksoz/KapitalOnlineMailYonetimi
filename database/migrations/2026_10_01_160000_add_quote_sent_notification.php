<?php

use App\Automation\ActionType;
use App\Automation\DedupePolicy;
use App\Automation\EventType;
use App\Automation\TimingMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('automation_rules')->where('event_type', EventType::QuoteSent->value)->exists()) {
            return;
        }

        $now = now();
        $templateId = DB::table('notification_templates')->insertGetId([
            'name' => 'Teklif gönderildi',
            'channel' => 'email',
            'legacy_key' => 'quote_sent',
            'subject' => 'Teklifiniz: {teklif_no}',
            'body' => "Sayın {musteri},\n\n{teklif_no} numaralı teklifimiz aşağıdadır.\nTür: {teklif_turu}\nGeçerlilik: {gecerlilik}\n\n{kalemler}\n\n{not}",
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $ruleId = DB::table('automation_rules')->insertGetId([
            'name' => 'Teklif gönderildi',
            'legacy_key' => 'quote_sent',
            'event_type' => EventType::QuoteSent->value,
            'is_enabled' => true,
            'conditions' => null,
            'timing' => json_encode([
                'mode' => TimingMode::Immediate->value,
                'offset_days' => 0,
                'interval_days' => 1,
                'send_at' => '10:00',
            ]),
            'dedupe_policy' => DedupePolicy::PerOccurrenceKey->value,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('automation_rule_actions')->insert([
            'automation_rule_id' => $ruleId,
            'action_type' => ActionType::Email->value,
            'notification_template_id' => $templateId,
            'config' => json_encode(['template_id' => $templateId]),
            'sort_order' => 0,
            'is_enabled' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        $ruleIds = DB::table('automation_rules')->where('legacy_key', 'quote_sent')->pluck('id');
        DB::table('automation_rule_actions')->whereIn('automation_rule_id', $ruleIds)->delete();
        DB::table('automation_rules')->whereIn('id', $ruleIds)->delete();
        DB::table('notification_templates')->where('legacy_key', 'quote_sent')->delete();
    }
};
