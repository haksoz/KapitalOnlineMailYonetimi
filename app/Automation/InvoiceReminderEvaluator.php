<?php

namespace App\Automation;

use App\Models\AutomationJob;
use App\Models\AutomationRule;
use App\Models\SalesInvoice;
use Carbon\Carbon;

final class InvoiceReminderEvaluator
{
    /** @var array<int, true>|null */
    private ?array $interestSentInvoiceIds = null;

    public function primeInterestCache(): void
    {
        $this->interestSentInvoiceIds = array_fill_keys($this->interestInvoiceIds(), true);
    }

    public function forgetInterestCache(): void
    {
        $this->interestSentInvoiceIds = null;
    }

    public function isEligible(AutomationRule $rule, SalesInvoice $invoice, Carbon $today): bool
    {
        if ($invoice->is_paid || $invoice->due_date === null || $invoice->our_invoice_date === null || blank($invoice->our_invoice_number)) {
            return false;
        }

        if (! $invoice->customerCari?->canReceiveNotifications()) {
            return false;
        }

        $due = Carbon::parse($invoice->due_date->format('Y-m-d'))->startOfDay();
        $today = Carbon::parse($today->timezone(Automation::TIMEZONE)->toDateString())->startOfDay();
        $offset = $rule->offsetDays();
        $eventType = $rule->event_type;

        if ($eventType === EventType::InvoiceDueApproaching) {
            if (! $invoice->customerCari?->hasPaymentTerm()) {
                return false;
            }
            if ($today->gt($due)) {
                return false;
            }
            $startOn = $due->copy()->subDays($offset);

            return $today->gte($startOn);
        }

        if ($eventType === EventType::InvoiceOverdue) {
            if ($today->lte($due)) {
                return false;
            }
            $startOn = $due->copy()->addDay()->addDays($offset);
            if ($today->lt($startOn)) {
                return false;
            }
            if ($this->invoiceHasInterestClosureSend($invoice)) {
                return false;
            }
            $legalStart = $this->interestClosureStartDate($due);
            if ($legalStart !== null && $today->gte($legalStart)) {
                return false;
            }

            return true;
        }

        if ($eventType === EventType::InvoiceInterestClosure) {
            if ($today->lte($due)) {
                return false;
            }
            if ($this->invoiceHasInterestClosureSend($invoice)) {
                return false;
            }
            $startOn = $due->copy()->addDays($offset);

            return $today->gte($startOn);
        }

        return false;
    }

    public function intervalElapsed(AutomationRule $rule, SalesInvoice $invoice, Carbon $today): bool
    {
        $interval = $rule->intervalDays();
        $lastAt = $this->lastSuccessAt($rule, $invoice);
        if ($lastAt === null) {
            return true;
        }

        $nextAllowed = $lastAt->copy()->timezone(Automation::TIMEZONE)->startOfDay()->addDays($interval);

        return $nextAllowed->lte($today->copy()->startOfDay());
    }

    public function invoiceHasInterestClosureSend(SalesInvoice $invoice): bool
    {
        if ($this->interestSentInvoiceIds !== null) {
            return isset($this->interestSentInvoiceIds[$invoice->id]);
        }

        return in_array($invoice->id, $this->interestInvoiceIds(), true);
    }

    private function interestClosureStartDate(Carbon $due): ?Carbon
    {
        $legal = AutomationRule::query()
            ->where('event_type', EventType::InvoiceInterestClosure->value)
            ->first();
        if ($legal === null || ! $legal->is_enabled) {
            return null;
        }

        return $due->copy()->startOfDay()->addDays($legal->offsetDays());
    }

    /**
     * @return list<int>
     */
    private function interestInvoiceIds(): array
    {
        $rule = AutomationRule::query()
            ->where('event_type', EventType::InvoiceInterestClosure->value)
            ->first();
        if ($rule === null) {
            return [];
        }

        return AutomationJob::query()
            ->where('automation_rule_id', $rule->id)
            ->where('subject_type', (new SalesInvoice)->getMorphClass())
            ->whereIn('status', [JobStatus::Succeeded->value, JobStatus::Pending->value, JobStatus::Processing->value])
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function lastSuccessAt(AutomationRule $rule, SalesInvoice $invoice): ?Carbon
    {
        $lastJob = AutomationJob::query()
            ->where('automation_rule_id', $rule->id)
            ->where('subject_type', $invoice->getMorphClass())
            ->where('subject_id', $invoice->id)
            ->where('status', JobStatus::Succeeded)
            ->orderByDesc('executed_at')
            ->first();

        return $lastJob?->executed_at !== null
            ? Carbon::parse($lastJob->executed_at)
            : null;
    }
}
