<?php

namespace App\Automation;

use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Subscription;
use App\Services\QuoteMath;

final class QuotePlaceholders
{
    /**
     * @return array<string, string>
     */
    public static function forQuote(Quote $quote, bool $htmlLines = false): array
    {
        $quote->loadMissing(['customerCari', 'items.options']);
        $cari = $quote->customerCari;

        return [
            '{musteri}' => (string) ($cari?->short_name ?: $cari?->name ?: ''),
            '{tur}' => Quote::typeLabel($quote->type),
            '{teklif_no}' => (string) $quote->quote_number,
            '{teklif_turu}' => Quote::typeLabel($quote->type),
            '{tarih}' => $quote->created_at?->timezone('Europe/Istanbul')->format('d.m.Y') ?? '—',
            '{gecerlilik}' => $quote->valid_until?->format('d.m.Y') ?? '—',
            '{satici}' => self::seller($quote),
            '{cari_unvani}' => (string) ($cari?->name ?: '—'),
            '{alici}' => self::buyer($quote),
            '{kalemler}' => $htmlLines && $quote->isFirm() ? self::firmLinesHtml($quote) : self::lines($quote),
            '{dinamik_kosullar}' => self::warnings($quote),
            '{not}' => trim((string) $quote->notes),
            '{kosullar}' => self::terms(),
        ];
    }

    /**
     * Şablon metnini kaçışlayıp yalnızca kesin teklifin {kalemler} tablosunu HTML bırakır.
     */
    public static function renderPreview(string $template, Quote $quote): string
    {
        $replacements = self::forQuote($quote, htmlLines: true);
        $htmlTokens = $quote->isFirm() ? ['{kalemler}'] : [];
        $slots = [];
        foreach ($htmlTokens as $index => $token) {
            $slot = '%%HTMLTOKEN'.$index.'%%';
            $slots[$slot] = $replacements[$token] ?? '';
            $template = str_replace($token, $slot, $template);
        }

        $html = nl2br(e($template), false);
        foreach ($replacements as $token => $value) {
            if (in_array($token, $htmlTokens, true)) {
                continue;
            }
            $html = str_replace($token, nl2br(e($value), false), $html);
        }
        foreach ($slots as $slot => $value) {
            $html = str_replace($slot, $value, $html);
        }

        return $html;
    }

    public static function deliveredHtml(string $templateBody, Quote $quote): string
    {
        $body = self::renderPreview($templateBody, $quote);

        return '<!DOCTYPE html>'
            .'<html lang="tr"><head><meta charset="UTF-8"></head>'
            .'<body style="margin:0;padding:24px;background:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#111827;">'
            .$body
            .'</body></html>';
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

    public static function eventFor(Quote $quote): EventType
    {
        return $quote->isFirm() ? EventType::QuoteFirmSent : EventType::QuoteOptionalSent;
    }

    public static function documentBody(): string
    {
        return self::skeleton(false);
    }

    public static function firmDocumentBody(): string
    {
        return self::skeleton(true);
    }

    private static function skeleton(bool $warnings): string
    {
        $afterLines = $warnings
            ? "{kalemler}\n\n{dinamik_kosullar}\n\n{not}\n\n{kosullar}"
            : "{kalemler}\n\n{not}\n\n{kosullar}";

        return "{tur}\nNo {teklif_no}\n\nTarih {tarih}\nGeçerlilik {gecerlilik}\n\nSatıcı\n{satici}\n\nAlıcı\n{alici}\n\n".$afterLines;
    }

    public static function seller(Quote $quote): string
    {
        if ($quote->isOptional()) {
            return 'KAPİTAL ONLİNE BİLGİSAYAR VE İLETİŞİM HİZ. TİC. LTD. ŞTİ.';
        }

        return implode("\n", [
            'KAPİTAL ONLİNE BİLGİSAYAR VE İLETİŞİM HİZ. TİC. LTD. ŞTİ.',
            'Orta Mah. Ordu Sok. Ozan İş Merkezi No:21/8 Kartal / İstanbul',
            'VD YAKACIK - Vergi No: 4980863169',
            'E-posta: muhasebe@ko.com.tr',
        ]);
    }

    public static function buyer(Quote $quote): string
    {
        $cari = $quote->customerCari;
        $lines = [(string) ($cari?->name ?: '—')];
        if ($quote->isFirm()) {
            if (filled($cari?->tax_number)) {
                $lines[] = 'Vergi No: '.$cari->tax_number;
            }
            if (filled($cari?->email)) {
                $lines[] = 'E-posta: '.$cari->email;
            }
        }

        return implode("\n", $lines);
    }

    public static function warnings(Quote $quote): string
    {
        if (! $quote->isFirm()) {
            return '';
        }

        $lines = ['- Birim fiyatlara KDV dahil değildir.'];
        foreach (self::firmPaymentNotes($quote) as $note) {
            $lines[] = '- '.$note;
        }

        return implode("\n", $lines);
    }

    public static function terms(): string
    {
        return implode("\n", [
            '1. Sipariş geçildikten sonra iade veya iptal hakkı yoktur.',
            '2. Sipariş geçilirken bir sonraki sene otomatik yenileme yapılıp yapılmayacağının iletilmesi rica edilir. Otomatik yenileme fiyatı sabitlemez. Fiyatlarda değişim var ise bilgi verilir.',
            '3. Taahhütlü siparişlerde 12 ay içerisinde adet azaltma hakkı bulunmamaktadır.',
            '4. Taahhütlü siparişlerde 12 ay içerisinde istenilen zaman adet arttırma yapılabilir, kalan gün sayısı üzerinden fatura kesilir.',
            '5. Aylık taahhütsüz geçilen siparişlerde fiyat koruması yoktur, üyelik devam ettiği sürece aylık olarak her ayın güncel fiyatları ile faturalandırılır. Yıllık Taahütlü siparişerin 12 aylık toplam bedeli sipariş geçildiğinde faturalandırılır.',
            '6. Sizlerden olumlu olumsuz bilgi gelmemesi durumunda, otomatik yenileme yapılmayacak ve yenilemeler sistem tarafından otomatik durdurulacaktır.',
        ]);
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
                ."\n".'KDV (%'.QuoteMath::display($quote->vat_rate).'): '.$quote->formatMoney($summary['vat'])
                ."\n".'Genel toplam: '.$quote->formatMoney($summary['gross']);
        } else {
            $blocks[] = 'Birim fiyatlara KDV dahil değildir.';
        }

        return implode("\n\n", array_filter($blocks, fn (string $block): bool => $block !== ''));
    }

    private static function optionalLine(Quote $quote, QuoteItem $item): string
    {
        $lines = [$item->quantity.' adet - '.trim($item->product_name)];
        foreach (Quote::COMMITMENTS as $tip) {
            $option = $item->options->firstWhere('taahhut_tipi', $tip);
            $lines[] = '';
            $lines[] = Quote::commitmentLabel($tip).' — '.self::paymentHint($tip);
            $lines[] = 'Birim fiyat: '.($option ? $quote->formatMoney($option->birim_satis) : '—');
            $lines[] = 'Tutar: '.($option ? $quote->formatMoney($option->saleTotal()) : '—');
        }

        return implode("\n", $lines);
    }

    private static function firmLine(Quote $quote, QuoteItem $item): string
    {
        return implode("\n", [
            trim($item->product_name),
            'Taahhüt: '.($item->commitmentLabel() ?: '—'),
            'Adet: '.$item->quantity,
            'Birim fiyat: '.$quote->formatMoney($item->birim_satis),
            'Tutar: '.$quote->formatMoney($item->saleTotal()),
        ]);
    }

    private static function firmLinesHtml(Quote $quote): string
    {
        $cell = 'padding:8px 10px;border-bottom:1px solid #e5e7eb;font-size:14px;color:#111827;';
        $head = 'padding:8px 10px;border-bottom:1px solid #e5e7eb;font-size:11px;letter-spacing:0.04em;text-transform:uppercase;color:#6b7280;font-weight:600;';
        $rows = '';
        foreach ($quote->items as $item) {
            $rows .= '<tr>'
                .'<td style="'.$cell.'font-weight:600;">'.e(trim($item->product_name)).'</td>'
                .'<td style="'.$cell.'">'.e($item->commitmentLabel() ?: '—').'</td>'
                .'<td align="right" style="'.$cell.'">'.e((string) $item->quantity).'</td>'
                .'<td align="right" style="'.$cell.'">'.e($quote->formatMoney($item->birim_satis)).'</td>'
                .'<td align="right" style="'.$cell.'font-weight:600;">'.e($quote->formatMoney($item->saleTotal())).'</td>'
                .'</tr>';
        }

        $summary = $quote->firmSummary();

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%;">'
            .'<tr>'
            .'<th align="left" style="'.$head.'">Ürün</th>'
            .'<th align="left" style="'.$head.'">Taahhüt</th>'
            .'<th align="right" style="'.$head.'">Adet</th>'
            .'<th align="right" style="'.$head.'">Birim fiyat</th>'
            .'<th align="right" style="'.$head.'">Tutar</th>'
            .'</tr>'
            .$rows
            .'</table>'
            .'<div style="margin-top:16px;text-align:right;font-size:14px;color:#374151;">'
            .'<div>Ara toplam: '.e($quote->formatMoney($summary['net'])).'</div>'
            .'<div style="margin-top:4px;">KDV (%'.e(QuoteMath::display($quote->vat_rate)).'): '.e($quote->formatMoney($summary['vat'])).'</div>'
            .'<div style="margin-top:4px;font-size:16px;font-weight:700;color:#111827;">Genel toplam: '.e($quote->formatMoney($summary['gross'])).'</div>'
            .'</div>';
    }

    /**
     * @return list<string>
     */
    private static function firmPaymentNotes(Quote $quote): array
    {
        $notes = [
            Subscription::TAAHHUT_MONTHLY_COMMITMENT => 'Aylık taahhütlü seçeneğinde yıllık taahhüt verilir, aylık ödenir.',
            Subscription::TAAHHUT_MONTHLY_NO_COMMITMENT => 'Aylık taahhütsüz seçeneğinde aylık ödenir.',
            Subscription::TAAHHUT_ANNUAL_COMMITMENT => 'Yıllık taahhütlü seçeneğinde, yıllık ödenir.',
        ];
        $used = $quote->items->pluck('taahhut_tipi')->filter()->unique();
        $lines = [];
        foreach (Quote::COMMITMENTS as $tip) {
            if ($used->contains($tip)) {
                $lines[] = $notes[$tip];
            }
        }

        return $lines;
    }

    private static function paymentHint(string $tip): string
    {
        return match ($tip) {
            Subscription::TAAHHUT_MONTHLY_COMMITMENT => 'Yıllık taahhüt, aylık ödeme',
            Subscription::TAAHHUT_MONTHLY_NO_COMMITMENT => 'Aylık ödeme',
            Subscription::TAAHHUT_ANNUAL_COMMITMENT => 'Yıllık ödeme',
            default => '',
        };
    }
}
