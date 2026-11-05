<?php

use App\Automation\EventType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'quote_renewal_optional_sent' => EventType::QuoteRenewalOptionalSent,
            'quote_renewal_firm_sent' => EventType::QuoteRenewalFirmSent,
        ] as $key => $event) {
            $now = now();
            $templateId = DB::table('notification_templates')->insertGetId([
                'name' => $event->label(), 'channel' => 'email', 'legacy_key' => $key,
                'subject' => 'Abonelik yenileme teklifiniz: {teklif_no}',
                'body' => "Sayın {musteri},\n\n{yenileme_aciklamasi}\n\n{teklif_no} numaralı teklifimizi ekte bilgilerinize sunarız.\nGeçerlilik: {gecerlilik}\n\n{kalemler}\n{dinamik_kosullar}\n\n{not}\n\nİyi çalışmalar dileriz.",
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $ruleId = DB::table('automation_rules')->insertGetId([
                'name' => $event->label(), 'legacy_key' => $key, 'event_type' => $event->value,
                'is_enabled' => true, 'conditions' => null,
                'timing' => json_encode(['mode' => 'immediate', 'offset_days' => 0, 'interval_days' => 1, 'send_at' => '10:00']),
                'dedupe_policy' => 'per_occurrence_key', 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('automation_rule_actions')->insert([
                'automation_rule_id' => $ruleId, 'action_type' => 'email',
                'notification_template_id' => $templateId, 'config' => json_encode(['template_id' => $templateId]),
                'sort_order' => 0, 'is_enabled' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $keys = ['quote_renewal_optional_sent', 'quote_renewal_firm_sent'];
        $ruleIds = DB::table('automation_rules')->whereIn('legacy_key', $keys)->pluck('id');
        DB::table('automation_rule_actions')->whereIn('automation_rule_id', $ruleIds)->delete();
        DB::table('automation_rules')->whereIn('id', $ruleIds)->delete();
        DB::table('notification_templates')->whereIn('legacy_key', $keys)->delete();
    }
};
