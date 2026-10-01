<?php

namespace App\Automation;

use App\Models\Quote;
use App\Models\QuoteItem;

final class QuotePlaceholders
{
    /**
     * @return array<string, string>
     */
    public static function forQuote(Quote $quote): array
    {
        $quote->loadMissing(['customerCari', 'items.options']);
        $cari = $quote->customerCari;

        return [
            '{musteri}' => (string) ($cari?->short_name ?: $cari?->name ?: ''),
            '{teklif_no}' => (string) $quote->quote_number,
            '{teklif_turu}' => Quote::typeLabel($quote->type),
            '{gecerlilik}' => $quote->valid_until?->format('d.m.Y') ?? '—',
            '{kalemler}' => self::lines($quote),
            '{not}' => trim((string) $quote->notes),
        ];
    }

    /**
     * @param  list<string>  $recipients
     * @return array<string, mixed>
     */
    public static function context(Quote $quote, array $recipients): array
    {
        return [
            'recipients' => array_values($recipients),
            'placeholders' => self::forQuote($quote),
        ];
    }

    private static function lines(Quote $quote): string
    {
        $blocks = [];
        foreach ($quote->items as $item) {
            $blocks[] = $quote->isFirm()
                ? self::firmLine($quote, $item)
                : self::optionalLine($quote, $item);
        }

        if ($quote->isFirm()) {
            $summary = $quote->firmSummary();
            $blocks[] = 'Ara toplam: '.$quote->formatMoney($summary['net'])
                ."\n".'KDV: '.$quote->formatMoney($summary['vat'])
                ."\n".'Genel toplam: '.$quote->formatMoney($summary['gross']);
        }

        return implode("\n\n", $blocks);
    }

    private static function optionalLine(Quote $quote, QuoteItem $item): string
    {
        $lines = [trim($item->product_name.' — '.$item->quantity.' adet')];
        foreach (Quote::COMMITMENTS as $tip) {
            $option = $item->options->firstWhere('taahhut_tipi', $tip);
            if ($option === null) {
                continue;
            }
            $lines[] = $option->commitmentLabel().': '.$quote->formatMoney($option->birim_satis)
                .' × '.$item->quantity.' = '.$quote->formatMoney($option->saleTotal());
        }

        return implode("\n", $lines);
    }

    private static function firmLine(Quote $quote, QuoteItem $item): string
    {
        return trim($item->product_name.' — '.($item->commitmentLabel() ?: '—')
            .' — '.$item->quantity.' adet — '.$quote->formatMoney($item->birim_satis)
            .' — '.$quote->formatMoney($item->saleTotal()));
    }
}
