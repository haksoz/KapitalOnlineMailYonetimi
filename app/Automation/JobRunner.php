<?php

namespace App\Automation;

use App\Models\AutomationJob;
use App\Models\AutomationJobRun;
use App\Automation\Actions\ActionHandlerRegistry;
use Carbon\Carbon;
use Carbon\CarbonInterface;

final class JobRunner
{
    public function __construct(private readonly ActionHandlerRegistry $handlers)
    {
    }

    public function run(?CarbonInterface $now = null, bool $respectSendAt = true, TriggeredBy $triggeredBy = TriggeredBy::Scheduler): int
    {
        $now = Carbon::parse($now ?? now())->timezone(Automation::TIMEZONE);
        $jobs = AutomationJob::query()
            ->with(['action', 'rule', 'cari', 'subject'])
            ->where('status', JobStatus::Pending)
            ->when($respectSendAt, fn ($q) => $q->where('scheduled_at', '<=', $now))
            ->orderBy('id')
            ->get();

        $sent = 0;
        foreach ($jobs as $job) {
            if ($this->execute($job, $now, $triggeredBy)) {
                $sent++;
            }
        }

        return $sent;
    }

    public function execute(AutomationJob $job, ?CarbonInterface $now = null, TriggeredBy $triggeredBy = TriggeredBy::Scheduler): bool
    {
        $now = Carbon::parse($now ?? now())->timezone(Automation::TIMEZONE);
        $job->loadMissing(['action', 'rule']);
        if ($job->action === null || $job->action->action_type === null) {
            $this->finish($job, JobStatus::Failed, 'Aksiyon tanımı yok.', $triggeredBy, $now);

            return false;
        }

        $job->update(['status' => JobStatus::Processing]);
        $started = $now->copy();

        try {
            $result = $this->handlers->for($job->action->action_type)->handle($job);
        } catch (\Throwable $e) {
            $this->finish($job, JobStatus::Failed, $e->getMessage(), $triggeredBy, $now, $started, null, RunStatus::Failed);

            return false;
        }

        if ($result->skipped) {
            $this->finish($job, JobStatus::Skipped, $result->message, $triggeredBy, $now, $started, $result->toEmail, RunStatus::Skipped);

            return false;
        }
        if (! $result->ok) {
            $this->finish($job, JobStatus::Failed, $result->message, $triggeredBy, $now, $started, $result->toEmail, RunStatus::Failed);

            return false;
        }

        $this->finish($job, JobStatus::Succeeded, $result->message, $triggeredBy, $now, $started, $result->toEmail, RunStatus::Succeeded);

        return true;
    }

    public function retry(AutomationJob $job, ?CarbonInterface $now = null): bool
    {
        if (! in_array($job->status, [JobStatus::Failed, JobStatus::Skipped], true)) {
            return false;
        }

        $job->update([
            'status' => JobStatus::Pending,
            'error_message' => null,
        ]);

        return $this->execute($job, $now, TriggeredBy::AdminRetry);
    }

    public function cancel(AutomationJob $job): bool
    {
        if (! in_array($job->status, [JobStatus::Pending, JobStatus::Failed], true)) {
            return false;
        }

        $job->update([
            'status' => JobStatus::Cancelled,
            'error_message' => null,
        ]);

        return true;
    }

    public function reschedule(AutomationJob $job, CarbonInterface $scheduledAt): bool
    {
        if ($job->status !== JobStatus::Pending) {
            return false;
        }

        $job->update([
            'scheduled_at' => Carbon::parse($scheduledAt)->timezone(Automation::TIMEZONE),
        ]);

        return true;
    }

    public function retrigger(AutomationJob $job, ?CarbonInterface $now = null): AutomationJob
    {
        $now = Carbon::parse($now ?? now())->timezone(Automation::TIMEZONE);
        $clone = $job->replicate([
            'executed_at',
            'error_message',
            'to_email',
        ]);
        $clone->parent_job_id = $job->id;
        $clone->occurrence_key = $job->occurrence_key.':manual:'.uniqid('', true);
        $clone->status = JobStatus::Pending;
        $clone->scheduled_at = $now;
        $clone->executed_at = null;
        $clone->error_message = null;
        $clone->attempt_count = 0;
        $clone->save();

        $this->execute($clone, $now, TriggeredBy::AdminManual);

        return $clone->fresh() ?? $clone;
    }

    private function finish(
        AutomationJob $job,
        JobStatus $status,
        ?string $message,
        TriggeredBy $triggeredBy,
        CarbonInterface $now,
        ?CarbonInterface $started = null,
        ?string $toEmail = null,
        ?RunStatus $runStatus = null,
    ): void {
        $job->update([
            'status' => $status,
            'executed_at' => $now,
            'error_message' => $status === JobStatus::Succeeded ? null : $message,
            'to_email' => $toEmail ?? $job->to_email,
            'attempt_count' => (int) $job->attempt_count + 1,
        ]);

        if ($runStatus !== null) {
            AutomationJobRun::query()->create([
                'automation_job_id' => $job->id,
                'status' => $runStatus,
                'started_at' => $started ?? $now,
                'finished_at' => $now,
                'error_message' => $runStatus === RunStatus::Succeeded ? null : $message,
                'response_summary' => $message,
                'triggered_by' => $triggeredBy,
            ]);
        }
    }
}
