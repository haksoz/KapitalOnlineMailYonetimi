<?php

namespace App\Automation\Actions;

use App\Automation\DomainPlaceholders;
use App\Automation\EventType;
use App\Automation\InvoicePlaceholders;
use App\Automation\NotificationMail;
use App\Automation\QuotePlaceholders;
use App\Models\AutomationJob;
use App\Models\Cari;
use App\Models\MailSetting;
use App\Models\NotificationTemplate;
use App\Models\PendingBilling;
use App\Models\Quote;
use App\Models\SalesInvoice;
use App\Models\Subscription;

final class EmailActionHandler implements ActionHandler
{
    public function handle(AutomationJob $job): ActionResult
    {
        MailSetting::applyToRuntime();
        $job->loadMissing(['action.template', 'cari', 'subject']);

        $template = $job->action?->template;
        if (! $template instanceof NotificationTemplate) {
            $templateId = (int) ($job->action?->config['template_id'] ?? $job->action?->notification_template_id ?? 0);
            $template = $templateId > 0 ? NotificationTemplate::query()->find($templateId) : null;
        }
        if ($template === null) {
            return ActionResult::failed('E-posta şablonu bulunamadı.');
        }

        $cari = $job->cari;
        if (! $cari instanceof Cari) {
            $cari = $job->cari_id ? Cari::query()->find($job->cari_id) : null;
        }

        if (in_array($job->event_type, [EventType::QuoteSent, EventType::QuoteOptionalSent, EventType::QuoteFirmSent], true)) {
            return $this->sendQuote($job, $template, $cari);
        }

        if ($cari === null || ! $cari->canReceiveNotifications()) {
            return ActionResult::skipped('Cari bildirimi kapalı veya e-posta yok.');
        }

        $subject = $job->subject;
        if ($subject instanceof SalesInvoice && $subject->is_paid && in_array(
            $job->event_type,
            EventType::invoiceReminderTypes(),
            true
        )) {
            return ActionResult::skipped('Fatura ödendi.');
        }

        $recipients = $cari->notificationEmails();
        if ($recipients === []) {
            return ActionResult::skipped('Cari bildirimi kapalı veya e-posta yok.');
        }

        $replacements = $this->replacements($job);
        $subject = $template->renderSubject($replacements);
        $body = $template->renderBody($replacements);

        try {
            NotificationMail::send($recipients, $subject, $body, MailSetting::notificationBcc());
        } catch (\Throwable $e) {
            return ActionResult::failed($e->getMessage());
        }

        return ActionResult::success(implode(', ', $recipients));
    }

    /**
     * @return array<string, string>
     */
    private function replacements(AutomationJob $job): array
    {
        $fromContext = $job->context['placeholders'] ?? null;
        $subject = $job->subject;
        if ($subject instanceof SalesInvoice) {
            return InvoicePlaceholders::forInvoice($subject);
        }
        if ($subject instanceof Subscription) {
            return DomainPlaceholders::forSubscription($subject);
        }
        if ($subject instanceof PendingBilling) {
            return DomainPlaceholders::forOrder($subject);
        }
        if ($subject instanceof Quote) {
            return QuotePlaceholders::forQuote($subject);
        }
        if (is_array($fromContext) && $fromContext !== []) {
            /** @var array<string, string> $fromContext */
            return $fromContext;
        }

        return [];
    }

    private function sendQuote(AutomationJob $job, NotificationTemplate $template, ?Cari $cari): ActionResult
    {
        $recipients = $this->quoteRecipients($job, $cari);
        if ($recipients === []) {
            return ActionResult::skipped('Alıcı e-posta yok.');
        }

        try {
            $replacements = $this->replacements($job);
            NotificationMail::send(
                $recipients,
                $template->renderSubject($replacements),
                $template->renderBody($replacements),
                MailSetting::notificationBcc(),
                $this->quoteHtml($job),
            );
        } catch (\Throwable $e) {
            return ActionResult::failed($e->getMessage());
        }

        return ActionResult::success(implode(', ', $recipients));
    }

    private function quoteHtml(AutomationJob $job): ?string
    {
        $quote = $job->subject instanceof Quote ? $job->subject : null;
        if (! $quote instanceof Quote && $job->subject_id) {
            $quote = Quote::query()->find($job->subject_id);
        }
        if (! $quote instanceof Quote) {
            return null;
        }

        $quote->loadMissing(['customerCari', 'items.options']);

        return view('quotes.mail', ['quote' => $quote])->render();
    }

    /**
     * @return list<string>
     */
    private function quoteRecipients(AutomationJob $job, ?Cari $cari): array
    {
        $fromContext = $job->context['recipients'] ?? null;
        if (is_array($fromContext)) {
            $clean = [];
            foreach ($fromContext as $email) {
                if (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                    continue;
                }
                $clean[strtolower($email)] = $email;
            }
            if ($clean !== []) {
                return array_values($clean);
            }
        }

        return $cari?->notificationEmails() ?? [];
    }
}
