<?php

namespace App\Automation;

use App\Models\AutomationJob;
use App\Models\AutomationRule;
use App\Models\AutomationRuleAction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class AutomationBus
{
    public function __construct(private readonly ConditionEvaluator $conditions)
    {
    }

    public function emit(DomainEvent $event): int
    {
        $rules = AutomationRule::query()
            ->enabled()
            ->where('event_type', $event->type->value)
            ->with(['actions' => fn ($q) => $q->enabled()->orderBy('sort_order')])
            ->get();

        $created = 0;
        foreach ($rules as $rule) {
            if (! $this->conditions->matches($rule, $event)) {
                continue;
            }
            foreach ($rule->actions as $action) {
                if ($this->enqueue($event, $rule, $action) !== null) {
                    $created++;
                }
            }
        }

        return $created;
    }

    public function enqueue(DomainEvent $event, AutomationRule $rule, AutomationRuleAction $action): ?AutomationJob
    {
        $key = OccurrenceKey::make($event, $rule, $action);
        $scheduledAt = $this->scheduledAt($rule, $event);

        $existing = AutomationJob::query()
            ->where('automation_rule_action_id', $action->id)
            ->where('occurrence_key', $key)
            ->first();
        if ($existing !== null) {
            return null;
        }

        try {
            $job = DB::transaction(function () use ($event, $rule, $action, $key, $scheduledAt) {
                return AutomationJob::query()->create([
                    'automation_rule_id' => $rule->id,
                    'automation_rule_action_id' => $action->id,
                    'event_type' => $event->type,
                    'cari_id' => $event->cariId,
                    'subject_type' => $event->subject->getMorphClass(),
                    'subject_id' => $event->subject->getKey(),
                    'occurrence_key' => $key,
                    'status' => JobStatus::Pending,
                    'scheduled_at' => $scheduledAt,
                    'context' => $event->context,
                    'attempt_count' => 0,
                ]);
            });
        } catch (\Throwable) {
            return null;
        }

        if ($job !== null && $rule->timingMode() === TimingMode::Immediate) {
            $this->runNow($job);
        }

        return $job;
    }

    private function runNow(AutomationJob $job): void
    {
        try {
            app(JobRunner::class)->execute($job);
        } catch (\Throwable $e) {
            Log::error('Hemen gönderim çalıştırılamadı.', [
                'automation_job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function scheduledAt(AutomationRule $rule, DomainEvent $event): Carbon
    {
        $occurred = Carbon::parse($event->occurredAt)->timezone(Automation::TIMEZONE);
        $mode = $rule->timingMode();

        if ($mode === TimingMode::Immediate) {
            return $occurred->copy();
        }

        $sendAt = $rule->sendAtForInput();
        $base = $occurred->copy();
        if ($mode === TimingMode::OffsetDays) {
            $base->addDays($rule->offsetDays());
        }

        $scheduled = InvoicePlaceholders::sendAtOnDate($sendAt, $base);
        if ($scheduled->lt($occurred)) {
            return $occurred->copy();
        }

        return $scheduled;
    }
}
