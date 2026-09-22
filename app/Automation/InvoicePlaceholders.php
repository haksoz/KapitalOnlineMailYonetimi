<?php

namespace App\Automation;

use App\Models\SalesInvoice;
use Carbon\Carbon;
use Carbon\CarbonInterface;

final class InvoicePlaceholders
{
    /**
     * @return array<string, string>
     */
    public static function forInvoice(SalesInvoice $invoice): array
    {
        $invoice->loadMissing(['customerCari', 'lines.pendingBilling.subscription']);
        $cari = $invoice->customerCari;
        $amount = $invoice->payableAmountTl();
        $formattedAmount = $amount !== null
            ? number_format($amount, 2, ',', '.') . ' ₺'
            : '';

        return [
            '{musteri}' => (string) ($cari?->short_name ?: $cari?->name ?: ''),
            '{fatura_no}' => (string) ($invoice->our_invoice_number ?? ''),
            '{fatura_tarihi}' => $invoice->our_invoice_date?->format('d.m.Y') ?? '',
            '{vade_tarihi}' => $invoice->due_date?->format('d.m.Y') ?? '',
            '{tutar}' => $formattedAmount,
            '{ftn}' => (string) ($invoice->order_number ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public static function invoiceContext(SalesInvoice $invoice, array $extra = []): array
    {
        return array_merge([
            'invoice_paid' => (bool) $invoice->is_paid,
            'our_invoice_number' => $invoice->our_invoice_number,
            'due_date' => $invoice->due_date?->toDateString(),
            'placeholders' => self::forInvoice($invoice),
        ], $extra);
    }

    public static function sendAtOnDate(string $sendAt, CarbonInterface $now): Carbon
    {
        $now = Carbon::parse($now)->timezone(Automation::TIMEZONE);
        [$hour, $minute] = array_pad(explode(':', $sendAt), 2, '0');

        return $now->copy()->setTime((int) $hour, (int) $minute, 0);
    }
}
