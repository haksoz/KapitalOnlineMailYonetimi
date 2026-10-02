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
    public static function forQuote(Quote $quote): array
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
            '{alici}' => self::buyer($quote),
            '{kalemler}' => self::lines($quote),
            '{not}' => trim((string) $quote->notes),
            '{kosullar}' => self::terms(),
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

    public static function eventFor(Quote $quote): EventType
    {
        return $quote->isFirm() ? EventType::QuoteFirmSent : EventType::QuoteOptionalSent;
    }

    public static function documentBody(): string
    {
        return "{tur}\nNo {teklif_no}\n\nTarih {tarih}\nGeçerlilik {gecerlilik}\n\nSatıcı\n{satici}\n\nAlıcı\n{alici}\n\n{kalemler}\n\n{not}\n\n{kosullar}";
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

        $blocks[] = 'Birim fiyatlara KDV dahil değildir.';

        if ($quote->isFirm()) {
            foreach (self::firmPaymentNotes($quote) as $note) {
                $blocks[] = $note;
            }
            $summary = $quote->firmSummary();
            $blocks[] = 'Ara toplam: '.$quote->formatMoney($summary['net'])
                ."\n".'KDV (%'.QuoteMath::display($quote->vat_rate).'): '.$quote->formatMoney($summary['vat'])
                ."\n".'Genel toplam: '.$quote->formatMoney($summary['gross']);
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
