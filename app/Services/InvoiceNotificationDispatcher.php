<?php

namespace App\Services;

use App\Models\MailSetting;
use App\Models\NotificationDefinition;
use App\Models\NotificationSend;
use App\Models\SalesInvoice;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InvoiceNotificationDispatcher
{
    public function dispatch(?CarbonInterface $today = null): int
    {
        MailSetting::applyToRuntime();
        $today = Carbon::parse($today ?? now())->startOfDay();

        $sent = 0;
        $definitions = NotificationDefinition::query()->where('is_enabled', true)->get();
        foreach ($definitions as $definition) {
            $sent += $this->dispatchDefinition($definition, $today);
        }

        return $sent;
    }

    private function dispatchDefinition(NotificationDefinition $definition, Carbon $today): int
    {
        $invoices = SalesInvoice::query()
            ->with(['customerCari'])
            ->where('is_paid', false)
            ->whereNotNull('due_date')
            ->whereNotNull('our_invoice_number')
            ->where('our_invoice_number', '!=', '')
            ->whereHas('customerCari', function ($q): void {
                $q->receivesNotifications();
            })
            ->get();

        $count = 0;
        foreach ($invoices as $invoice) {
            if (! $this->isEligible($definition, $invoice, $today)) {
                continue;
            }
            if (! $this->intervalElapsed($definition, $invoice, $today)) {
                continue;
            }
            if ($this->send($definition, $invoice, $today)) {
                $count++;
            }
        }

        return $count;
    }

    public function isEligible(NotificationDefinition $definition, SalesInvoice $invoice, Carbon $today): bool
    {
        if ($invoice->is_paid || $invoice->due_date === null || $invoice->our_invoice_date === null || blank($invoice->our_invoice_number)) {
            return false;
        }

        if (! $invoice->customerCari?->canReceiveNotifications()) {
            return false;
        }

        $due = $invoice->due_date->copy()->startOfDay();
        $invoiceDate = $invoice->our_invoice_date->copy()->startOfDay();
        $today = $today->copy()->startOfDay();

        if ($definition->key === NotificationDefinition::KEY_INVOICE_DUE_REMINDER) {
            if ($today->gt($due)) {
                return false;
            }
            $startOn = $invoiceDate->copy()->addDays((int) $definition->start_after_days);

            return $today->gte($startOn);
        }

        if ($definition->key === NotificationDefinition::KEY_INVOICE_OVERDUE) {
            if ($today->lte($due)) {
                return false;
            }
            $startOn = $due->copy()->addDay()->addDays((int) $definition->start_after_days);

            return $today->gte($startOn);
        }

        return false;
    }

    public function intervalElapsed(NotificationDefinition $definition, SalesInvoice $invoice, Carbon $today): bool
    {
        $last = NotificationSend::query()
            ->where('notification_definition_id', $definition->id)
            ->where('sales_invoice_id', $invoice->id)
            ->orderByDesc('sent_at')
            ->first();

        if ($last === null) {
            return true;
        }

        $nextAllowed = $last->sent_at->copy()->startOfDay()->addDays((int) $definition->interval_days);

        return $nextAllowed->lte($today->copy()->startOfDay());
    }

    private function send(NotificationDefinition $definition, SalesInvoice $invoice, Carbon $today): bool
    {
        $to = (string) $invoice->customerCari?->email;
        if ($to === '' || ! $invoice->customerCari?->canReceiveNotifications()) {
            return false;
        }
        $replacements = $this->replacements($invoice);
        $subject = $definition->renderSubject($replacements);
        $body = $definition->renderBody($replacements);
        $sentAt = $today->copy()->startOfDay()->setTime((int) now()->hour, (int) now()->minute);

        try {
            Mail::raw($body, function ($message) use ($to, $subject): void {
                $message->to($to)->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::error('Bildiri maili gönderilemedi.', [
                'definition' => $definition->key,
                'sales_invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        NotificationSend::create([
            'notification_definition_id' => $definition->id,
            'sales_invoice_id' => $invoice->id,
            'sent_at' => $sentAt,
            'to_email' => $to,
        ]);

        return true;
    }

    /**
     * @return array<string, string>
     */
    public function replacements(SalesInvoice $invoice): array
    {
        $cari = $invoice->customerCari;

        return [
            '{musteri}' => (string) ($cari?->short_name ?: $cari?->name ?: ''),
            '{fatura_no}' => (string) ($invoice->our_invoice_number ?? ''),
            '{fatura_tarihi}' => $invoice->our_invoice_date?->format('d.m.Y') ?? '',
            '{vade_tarihi}' => $invoice->due_date?->format('d.m.Y') ?? '',
            '{tutar}' => $invoice->total_amount_tl !== null
                ? number_format((float) $invoice->total_amount_tl, 2, ',', '.') . ' ₺'
                : '',
            '{ftn}' => (string) ($invoice->order_number ?? ''),
        ];
    }

    public function sendTest(NotificationDefinition $definition, SalesInvoice $invoice, string $to): void
    {
        MailSetting::applyToRuntime();
        $invoice->loadMissing('customerCari');

        $replacements = $this->replacements($invoice);
        $subject = '[TEST] ' . $definition->renderSubject($replacements);
        $body = $definition->renderBody($replacements);

        Mail::raw($body, function ($message) use ($to, $subject): void {
            $message->to($to)->subject($subject);
        });
    }
}
