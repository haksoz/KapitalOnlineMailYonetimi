<?php

namespace App\Automation\Actions;

use App\Automation\DomainPlaceholders;
use App\Automation\EventType;
use App\Automation\InvoicePlaceholders;
use App\Models\AutomationJob;
use App\Models\Cari;
use App\Models\MailSetting;
use App\Models\NotificationTemplate;
use App\Models\PendingBilling;
use App\Models\SalesInvoice;
use App\Models\Subscription;
use Illuminate\Support\Facades\Mail;

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

        $replacements = $this->replacements($job);
        $subject = $template->renderSubject($replacements);
        $body = $template->renderBody($replacements);
        $to = (string) $cari->email;

        try {
            Mail::raw($body, function ($message) use ($to, $subject): void {
                $message->to($to)->subject($subject);
            });
        } catch (\Throwable $e) {
            return ActionResult::failed($e->getMessage());
        }

        return ActionResult::success($to);
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
        if (is_array($fromContext) && $fromContext !== []) {
            /** @var array<string, string> $fromContext */
            return $fromContext;
        }

        return [];
    }
}
