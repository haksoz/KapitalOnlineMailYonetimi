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
        $now = now();
        $optionalBody = "Sayın {musteri},\n\n{teklif_no} numaralı birim fiyat teklifimiz aşağıdadır.\nGeçerlilik: {gecerlilik}\n\n{kalemler}\n\n{not}";
        $firmBody = "Sayın {musteri},\n\n{teklif_no} numaralı kesin teklifimiz aşağıdadır.\nGeçerlilik: {gecerlilik}\n\n{kalemler}\n\n{not}";

        $existing = DB::table('notification_templates')->where('legacy_key', 'quote_sent')->first();
        if ($existing !== null) {
            DB::table('notification_templates')->where('id', $existing->id)->update([
                'name' => 'Birim fiyat teklifi gönderildi',
                'legacy_key' => 'quote_optional_sent',
                'subject' => 'Birim fiyat teklifiniz: {teklif_no}',
                'body' => $optionalBody,
                'updated_at' => $now,
            ]);
            DB::table('automation_rules')->where('legacy_key', 'quote_sent')->update([
                'name' => 'Birim fiyat teklifi gönderildi',
                'legacy_key' => 'quote_optional_sent',
                'event_type' => EventType::QuoteOptionalSent->value,
                'updated_at' => $now,
            ]);
        } elseif (! DB::table('automation_rules')->where('event_type', EventType::QuoteOptionalSent->value)->exists()) {
            $this->seed($now, 'Birim fiyat teklifi gönderildi', 'quote_optional_sent', EventType::QuoteOptionalSent, 'Birim fiyat teklifiniz: {teklif_no}', $optionalBody);
        }

        if (! DB::table('automation_rules')->where('event_type', EventType::QuoteFirmSent->value)->exists()) {
            $this->seed($now, 'Kesin teklif gönderildi', 'quote_firm_sent', EventType::QuoteFirmSent, 'Kesin teklifiniz: {teklif_no}', $firmBody);
        }
    }

    public function down(): void
    {
        $ruleIds = DB::table('automation_rules')->where('legacy_key', 'quote_firm_sent')->pluck('id');
        DB::table('automation_rule_actions')->whereIn('automation_rule_id', $ruleIds)->delete();
        DB::table('automation_rules')->whereIn('id', $ruleIds)->delete();
        DB::table('notification_templates')->where('legacy_key', 'quote_firm_sent')->delete();

        DB::table('notification_templates')->where('legacy_key', 'quote_optional_sent')->update([
            'name' => 'Teklif gönderildi',
            'legacy_key' => 'quote_sent',
            'subject' => 'Teklifiniz: {teklif_no}',
            'body' => "Sayın {musteri},\n\n{teklif_no} numaralı teklifimiz aşağıdadır.\nTür: {teklif_turu}\nGeçerlilik: {gecerlilik}\n\n{kalemler}\n\n{not}",
        ]);
        DB::table('automation_rules')->where('legacy_key', 'quote_optional_sent')->update([
            'name' => 'Teklif gönderildi',
            'legacy_key' => 'quote_sent',
            'event_type' => EventType::QuoteSent->value,
        ]);
    }

    private function seed(mixed $now, string $name, string $legacyKey, EventType $event, string $subject, string $body): void
    {
        $templateId = DB::table('notification_templates')->insertGetId([
            'name' => $name,
            'channel' => 'email',
            'legacy_key' => $legacyKey,
            'subject' => $subject,
            'body' => $body,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $ruleId = DB::table('automation_rules')->insertGetId([
            'name' => $name,
            'legacy_key' => $legacyKey,
            'event_type' => $event->value,
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
};
