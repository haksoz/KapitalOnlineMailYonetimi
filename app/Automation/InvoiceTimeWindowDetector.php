<?php

namespace App\Automation;

use App\Models\AutomationRule;
use App\Models\SalesInvoice;
use Carbon\Carbon;
use Carbon\CarbonInterface;

final class InvoiceTimeWindowDetector
{
    public function __construct(
        private readonly AutomationBus $bus,
        private readonly InvoiceReminderEvaluator $evaluator,
    ) {
    }

    public function detect(?CarbonInterface $now = null): int
    {
        $now = Carbon::parse($now ?? now())->timezone(Automation::TIMEZONE);
        $today = Carbon::parse($now->toDateString())->startOfDay();
        $this->evaluator->primeInterestCache();

        try {
            $rules = AutomationRule::query()
                ->enabled()
                ->whereIn('event_type', array_map(
                    fn (EventType $type) => $type->value,
                    EventType::invoiceReminderTypes(),
                ))
                ->with(['actions' => fn ($q) => $q->enabled()->orderBy('sort_order')])
                ->get();

            $invoices = SalesInvoice::query()
                ->with(['customerCari', 'lines.pendingBilling.subscription'])
                ->where('is_paid', false)
                ->whereNotNull('due_date')
                ->whereNotNull('our_invoice_number')
                ->where('our_invoice_number', '!=', '')
                ->whereHas('customerCari', function ($q): void {
                    $q->receivesNotifications();
                })
                ->get();

            $queued = 0;
            foreach ($rules as $rule) {
                foreach ($invoices as $invoice) {
                    if (! $this->evaluator->isEligible($rule, $invoice, $today)) {
                        continue;
                    }
                    $policy = $rule->dedupePolicy();
                    if ($policy !== DedupePolicy::Once
                        && ! $this->evaluator->intervalElapsed($rule, $invoice, $today)) {
                        continue;
                    }

                    $event = new DomainEvent(
                        type: $rule->event_type,
                        subject: $invoice,
                        cariId: $invoice->customer_cari_id,
                        context: InvoicePlaceholders::invoiceContext($invoice),
                        fingerprint: $policy === DedupePolicy::Once
                            ? 'once'
                            : $today->toDateString(),
                        occurredAt: $now,
                    );
                    foreach ($rule->actions as $action) {
                        if ($this->bus->enqueue($event, $rule, $action) !== null) {
                            $queued++;
                        }
                    }
                }
            }

            return $queued;
        } finally {
            $this->evaluator->forgetInterestCache();
        }
    }
}
