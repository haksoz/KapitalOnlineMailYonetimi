<?php

namespace App\Services;

use App\Automation\InvoiceReminderEvaluator;
use App\Automation\InvoiceTimeWindowDetector;
use App\Automation\JobRunner;
use App\Automation\SubscriptionTimeWindowDetector;
use App\Automation\NotificationMail;
use App\Models\AutomationRule;
use App\Models\MailSetting;
use App\Models\NotificationTemplate;
use App\Models\SalesInvoice;
use App\Automation\InvoicePlaceholders;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class InvoiceNotificationDispatcher
{
    public function __construct(
        private readonly InvoiceTimeWindowDetector $invoiceDetector,
        private readonly SubscriptionTimeWindowDetector $subscriptionDetector,
        private readonly JobRunner $runner,
        private readonly InvoiceReminderEvaluator $evaluator,
    ) {
    }

    public function dispatch(?CarbonInterface $now = null, bool $respectSendAt = true): int
    {
        MailSetting::applyToRuntime();
        $now = Carbon::parse($now ?? now());
        $this->invoiceDetector->detect($now);
        $this->subscriptionDetector->detect($now);

        return $this->runner->run($now, $respectSendAt);
    }

    public function isEligible(AutomationRule $rule, SalesInvoice $invoice, Carbon $today): bool
    {
        return $this->evaluator->isEligible($rule, $invoice, $today);
    }

    public function intervalElapsed(AutomationRule $rule, SalesInvoice $invoice, Carbon $today): bool
    {
        return $this->evaluator->intervalElapsed($rule, $invoice, $today);
    }

    /**
     * @return array<string, string>
     */
    public function replacements(SalesInvoice $invoice): array
    {
        return InvoicePlaceholders::forInvoice($invoice);
    }

    public function sendTest(NotificationTemplate $template, SalesInvoice $invoice, string $to): void
    {
        MailSetting::applyToRuntime();
        $invoice->loadMissing(['customerCari', 'lines.pendingBilling.subscription']);

        $replacements = $this->replacements($invoice);
        $subject = '[TEST] '.$template->renderSubject($replacements);
        $body = $template->renderBody($replacements);

        NotificationMail::send($to, $subject, $body);
    }
}
