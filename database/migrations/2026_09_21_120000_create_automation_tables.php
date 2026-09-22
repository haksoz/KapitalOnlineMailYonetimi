<?php

use App\Automation\ActionType;
use App\Automation\DedupePolicy;
use App\Automation\EventType;
use App\Automation\JobStatus;
use App\Automation\RunStatus;
use App\Automation\TimingMode;
use App\Automation\TriggeredBy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('channel', 32)->default('email');
            $table->string('legacy_key', 64)->nullable()->unique();
            $table->string('subject', 255);
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legacy_key', 64)->nullable()->unique();
            $table->string('event_type', 64);
            $table->boolean('is_enabled')->default(false);
            $table->json('conditions')->nullable();
            $table->json('timing')->nullable();
            $table->string('dedupe_policy', 32)->default(DedupePolicy::PerOccurrenceKey->value);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['event_type', 'is_enabled']);
            $table->index('sort_order');
        });

        Schema::create('automation_rule_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_rule_id')->constrained('automation_rules')->cascadeOnDelete();
            $table->string('action_type', 32);
            $table->foreignId('notification_template_id')->nullable()->constrained('notification_templates')->nullOnDelete();
            $table->json('config')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('automation_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_rule_id')->constrained('automation_rules')->cascadeOnDelete();
            $table->foreignId('automation_rule_action_id')->constrained('automation_rule_actions')->cascadeOnDelete();
            $table->string('event_type', 64);
            $table->foreignId('cari_id')->nullable()->constrained('caris')->nullOnDelete();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('occurrence_key', 191);
            $table->string('status', 32)->default(JobStatus::Pending->value);
            $table->timestamp('scheduled_at');
            $table->timestamp('executed_at')->nullable();
            $table->json('context')->nullable();
            $table->string('to_email', 255)->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->foreignId('parent_job_id')->nullable()->constrained('automation_jobs')->nullOnDelete();
            $table->timestamps();

            $table->unique(['automation_rule_action_id', 'occurrence_key'], 'automation_jobs_action_occurrence_uq');
            $table->index(['status', 'scheduled_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('cari_id');
        });

        Schema::create('automation_job_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_job_id')->constrained('automation_jobs')->cascadeOnDelete();
            $table->string('status', 32);
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->text('error_message')->nullable();
            $table->text('response_summary')->nullable();
            $table->string('triggered_by', 32)->default(TriggeredBy::Scheduler->value);
            $table->timestamps();
            $table->index(['automation_job_id', 'started_at']);
        });

        $this->migrateLegacyDefinitions();
        $this->migrateLegacySends();
        $this->seedLifecycleRules();
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_job_runs');
        Schema::dropIfExists('automation_jobs');
        Schema::dropIfExists('automation_rule_actions');
        Schema::dropIfExists('automation_rules');
        Schema::dropIfExists('notification_templates');
    }

    private function migrateLegacyDefinitions(): void
    {
        if (! Schema::hasTable('notification_definitions')) {
            return;
        }

        $map = [
            'invoice_due_reminder' => [
                'event' => EventType::InvoiceDueApproaching->value,
                'dedupe' => DedupePolicy::PerOccurrenceKey->value,
                'send_at' => '10:00',
            ],
            'invoice_overdue' => [
                'event' => EventType::InvoiceOverdue->value,
                'dedupe' => DedupePolicy::PerOccurrenceKey->value,
                'send_at' => '14:30',
            ],
            'invoice_interest_closure' => [
                'event' => EventType::InvoiceInterestClosure->value,
                'dedupe' => DedupePolicy::Once->value,
                'send_at' => '16:00',
            ],
        ];

        $now = now();
        foreach (DB::table('notification_definitions')->orderBy('id')->get() as $row) {
            $meta = $map[$row->key] ?? null;
            if ($meta === null) {
                continue;
            }
            $sendAt = '10:00';
            if (isset($row->send_at) && is_string($row->send_at) && preg_match('/(\d{1,2}):(\d{2})/', $row->send_at, $m) === 1) {
                $sendAt = sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
            } else {
                $sendAt = $meta['send_at'];
            }

            $templateId = DB::table('notification_templates')->insertGetId([
                'name' => $row->name,
                'channel' => 'email',
                'legacy_key' => $row->key,
                'subject' => $row->subject,
                'body' => $row->body,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $ruleId = DB::table('automation_rules')->insertGetId([
                'name' => $row->name,
                'legacy_key' => $row->key,
                'event_type' => $meta['event'],
                'is_enabled' => (bool) $row->is_enabled,
                'conditions' => json_encode(['invoice_paid' => false]),
                'timing' => json_encode([
                    'mode' => TimingMode::AtSendAt->value,
                    'offset_days' => $row->key === 'invoice_due_reminder' && (int) $row->start_after_days === 0
                        ? 7
                        : (int) $row->start_after_days,
                    'interval_days' => max(1, (int) $row->interval_days),
                    'send_at' => $sendAt,
                ]),
                'dedupe_policy' => $meta['dedupe'],
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
    }

    private function migrateLegacySends(): void
    {
        if (! Schema::hasTable('notification_sends')) {
            return;
        }

        $subjectType = 'App\\Models\\SalesInvoice';
        $actions = DB::table('automation_rule_actions')
            ->join('automation_rules', 'automation_rules.id', '=', 'automation_rule_actions.automation_rule_id')
            ->get([
                'automation_rule_actions.id as action_id',
                'automation_rules.id as rule_id',
                'automation_rules.legacy_key',
                'automation_rules.event_type',
            ])
            ->keyBy('legacy_key');

        $definitions = DB::table('notification_definitions')->pluck('key', 'id');

        foreach (DB::table('notification_sends')->orderBy('id')->get() as $send) {
            $key = $definitions[$send->notification_definition_id] ?? null;
            $action = $key !== null ? $actions->get($key) : null;
            if ($action === null) {
                continue;
            }
            $invoice = DB::table('sales_invoices')->where('id', $send->sales_invoice_id)->first();
            $occurrenceKey = $action->event_type.':'.$subjectType.':'.$send->sales_invoice_id.':migrated:'.$send->id;

            $jobId = DB::table('automation_jobs')->insertGetId([
                'automation_rule_id' => $action->rule_id,
                'automation_rule_action_id' => $action->action_id,
                'event_type' => $action->event_type,
                'cari_id' => $invoice->customer_cari_id ?? null,
                'subject_type' => $subjectType,
                'subject_id' => $send->sales_invoice_id,
                'occurrence_key' => $occurrenceKey,
                'status' => JobStatus::Succeeded->value,
                'scheduled_at' => $send->sent_at,
                'executed_at' => $send->sent_at,
                'context' => json_encode(['migrated_from_notification_send_id' => $send->id]),
                'to_email' => $send->to_email,
                'attempt_count' => 1,
                'created_at' => $send->created_at ?? $send->sent_at,
                'updated_at' => $send->updated_at ?? $send->sent_at,
            ]);

            DB::table('automation_job_runs')->insert([
                'automation_job_id' => $jobId,
                'status' => RunStatus::Succeeded->value,
                'started_at' => $send->sent_at,
                'finished_at' => $send->sent_at,
                'triggered_by' => TriggeredBy::Scheduler->value,
                'created_at' => $send->sent_at,
                'updated_at' => $send->sent_at,
            ]);
        }
    }

    private function seedLifecycleRules(): void
    {
        $now = now();
        $rows = [
            [
                'event' => EventType::SubscriptionCreated->value,
                'name' => 'Abonelik oluşturuldu',
                'subject' => 'Aboneliğiniz oluşturuldu: {abonelik_no}',
                'body' => "Sayın {musteri},\n\n{abonelik_no} numaralı aboneliğiniz oluşturuldu.\nBitiş tarihi: {bitis_tarihi}",
                'offset' => 0,
                'interval' => 1,
                'send_at' => '10:00',
                'dedupe' => DedupePolicy::Once->value,
                'conditions' => null,
            ],
            [
                'event' => EventType::SubscriptionExpiryApproaching->value,
                'name' => 'Abonelik süresi yaklaşıyor',
                'subject' => 'Abonelik süreniz yaklaşıyor: {abonelik_no}',
                'body' => "Sayın {musteri},\n\n{abonelik_no} numaralı aboneliğinizin bitiş tarihi {bitis_tarihi}.",
                'offset' => 7,
                'interval' => 1,
                'send_at' => '10:00',
                'dedupe' => DedupePolicy::PerOccurrenceKey->value,
                'conditions' => ['auto_renew' => false],
            ],
            [
                'event' => EventType::SubscriptionExpired->value,
                'name' => 'Abonelik süresi doldu',
                'subject' => 'Aboneliğiniz sona erdi: {abonelik_no}',
                'body' => "Sayın {musteri},\n\n{abonelik_no} numaralı aboneliğiniz {bitis_tarihi} tarihinde sona erdi.",
                'offset' => 0,
                'interval' => 1,
                'send_at' => '10:00',
                'dedupe' => DedupePolicy::PerOccurrenceKey->value,
                'conditions' => null,
            ],
            [
                'event' => EventType::SubscriptionAutoRenewDisabled->value,
                'name' => 'Otomatik yenileme kapatıldı',
                'subject' => 'Otomatik yenileme kapatıldı: {abonelik_no}',
                'body' => "Sayın {musteri},\n\n{abonelik_no} numaralı aboneliğinizde otomatik yenileme kapatıldı.",
                'offset' => 0,
                'interval' => 1,
                'send_at' => '10:00',
                'dedupe' => DedupePolicy::Once->value,
                'conditions' => null,
            ],
            [
                'event' => EventType::SubscriptionPriceChanged->value,
                'name' => 'Abonelik fiyatı değişti',
                'subject' => 'Abonelik fiyatınız güncellendi: {abonelik_no}',
                'body' => "Sayın {musteri},\n\n{abonelik_no} numaralı aboneliğinizin fiyatı güncellendi. Yeni birim fiyat: {fiyat}",
                'offset' => 0,
                'interval' => 1,
                'send_at' => '10:00',
                'dedupe' => DedupePolicy::PerOccurrenceKey->value,
                'conditions' => null,
            ],
            [
                'event' => EventType::SubscriptionQuantityChanged->value,
                'name' => 'Abonelik miktarı değişti',
                'subject' => 'Abonelik adediniz güncellendi: {abonelik_no}',
                'body' => "Sayın {musteri},\n\n{abonelik_no} numaralı aboneliğinizin adedi {adet} olarak güncellendi.",
                'offset' => 0,
                'interval' => 1,
                'send_at' => '10:00',
                'dedupe' => DedupePolicy::PerOccurrenceKey->value,
                'conditions' => null,
            ],
            [
                'event' => EventType::OrderCreated->value,
                'name' => 'Sipariş oluştu',
                'subject' => 'Yeni siparişiniz oluştu: {abonelik_no}',
                'body' => "Sayın {musteri},\n\n{abonelik_no} için {donem_baslangic} – {donem_bitis} dönemi siparişi oluştu. Faturalama sonrası ödeme bilgisi iletilecektir.",
                'offset' => 0,
                'interval' => 1,
                'send_at' => '10:00',
                'dedupe' => DedupePolicy::Once->value,
                'conditions' => null,
            ],
            [
                'event' => EventType::InvoiceIssued->value,
                'name' => 'Fatura oluştu',
                'subject' => 'Faturanız kesildi: {fatura_no}',
                'body' => "Sayın {musteri},\n\n{fatura_no} numaralı faturanız kesildi.\nTutar (KDV dahil): {tutar}\nVade: {vade_tarihi}",
                'offset' => 0,
                'interval' => 1,
                'send_at' => '10:00',
                'dedupe' => DedupePolicy::Once->value,
                'conditions' => null,
            ],
            [
                'event' => EventType::InvoicePaid->value,
                'name' => 'Ödeme alındı',
                'subject' => 'Ödemeniz alındı: {fatura_no}',
                'body' => "Sayın {musteri},\n\n{fatura_no} numaralı faturanızın ödemesi alınmıştır. Teşekkür ederiz.",
                'offset' => 0,
                'interval' => 1,
                'send_at' => '10:00',
                'dedupe' => DedupePolicy::PerOccurrenceKey->value,
                'conditions' => null,
            ],
        ];

        foreach ($rows as $row) {
            if (DB::table('automation_rules')->where('event_type', $row['event'])->exists()) {
                continue;
            }

            $templateId = DB::table('notification_templates')->insertGetId([
                'name' => $row['name'],
                'channel' => 'email',
                'legacy_key' => null,
                'subject' => $row['subject'],
                'body' => $row['body'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $ruleId = DB::table('automation_rules')->insertGetId([
                'name' => $row['name'],
                'legacy_key' => null,
                'event_type' => $row['event'],
                'is_enabled' => false,
                'conditions' => $row['conditions'] === null ? null : json_encode($row['conditions']),
                'timing' => json_encode([
                    'mode' => EventType::tryFrom($row['event'])?->defaultTimingMode()->value ?? TimingMode::AtSendAt->value,
                    'offset_days' => $row['offset'],
                    'interval_days' => $row['interval'],
                    'send_at' => $row['send_at'],
                ]),
                'dedupe_policy' => $row['dedupe'],
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
    }
};
