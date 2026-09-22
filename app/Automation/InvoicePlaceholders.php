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
        $invoice->loadMissing(['customerCari', 'lines.pendingBilling.subscription.product']);
        $cari = $invoice->customerCari;
        $amount = $invoice->payableAmountTl();
        $formattedAmount = $amount !== null
            ? number_format($amount, 2, ',', '.') . ' ₺'
            : '';
        $subscriptions = self::subscriptionsOnInvoice($invoice);
        $numbers = array_values(array_filter(array_map(
            fn (array $row): string => $row['number'],
            $subscriptions,
        )));
        $quantities = array_map(
            fn (array $row): string => (string) $row['quantity'],
            $subscriptions,
        );
        $summary = self::subscriptionSummary($subscriptions);

        return [
            '{musteri}' => (string) ($cari?->short_name ?: $cari?->name ?: ''),
            '{fatura_no}' => (string) ($invoice->our_invoice_number ?? ''),
            '{fatura_tarihi}' => $invoice->our_invoice_date?->format('d.m.Y') ?? '',
            '{vade_tarihi}' => $invoice->due_date?->format('d.m.Y') ?? '',
            '{tutar}' => $formattedAmount,
            '{ftn}' => (string) ($invoice->order_number ?? ''),
            '{abonelik_no}' => implode(', ', $numbers),
            '{adet}' => implode(', ', $quantities),
            '{abonelikler}' => $summary,
            '{kalemler}' => $summary,
        ];
    }

    /**
     * @return list<array{number: string, quantity: int, product: string}>
     */
    private static function subscriptionsOnInvoice(SalesInvoice $invoice): array
    {
        $seen = [];
        $rows = [];
        foreach ($invoice->lines as $line) {
            $subscription = $line->pendingBilling?->subscription;
            if ($subscription === null) {
                continue;
            }
            $key = (string) ($subscription->getKey() ?: $subscription->sozlesme_no);
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $rows[] = [
                'number' => trim((string) ($subscription->sozlesme_no ?? '')),
                'quantity' => max(1, (int) ($subscription->quantity ?? 1)),
                'product' => trim((string) ($subscription->product?->name ?? '')),
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array{number: string, quantity: int, product: string}>  $subscriptions
     */
    private static function subscriptionSummary(array $subscriptions): string
    {
        $rows = [];
        foreach ($subscriptions as $row) {
            $title = $row['product'] !== '' ? $row['product'] : ($row['number'] !== '' ? $row['number'] : 'Abonelik');
            $parts = [$title];
            if ($row['number'] !== '' && $title !== $row['number']) {
                $parts[] = $row['number'];
            }
            $parts[] = $row['quantity'].' adet';
            $rows[] = '• '.implode(' — ', $parts);
        }

        return implode("\n", $rows);
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
