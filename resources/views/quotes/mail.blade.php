@php
    $paymentHints = [
        \App\Models\Subscription::TAAHHUT_MONTHLY_COMMITMENT => 'Yıllık taahhüt, aylık ödeme',
        \App\Models\Subscription::TAAHHUT_MONTHLY_NO_COMMITMENT => 'Aylık ödeme',
        \App\Models\Subscription::TAAHHUT_ANNUAL_COMMITMENT => 'Yıllık ödeme',
    ];
    $paymentNotes = [
        \App\Models\Subscription::TAAHHUT_MONTHLY_COMMITMENT => 'Aylık taahhütlü seçeneğinde yıllık taahhüt verilir, aylık ödenir.',
        \App\Models\Subscription::TAAHHUT_MONTHLY_NO_COMMITMENT => 'Aylık taahhütsüz seçeneğinde aylık ödenir.',
        \App\Models\Subscription::TAAHHUT_ANNUAL_COMMITMENT => 'Yıllık taahhütlü seçeneğinde, yıllık ödenir.',
    ];
    $used = $quote->items->pluck('taahhut_tipi')->filter()->unique();
    $summary = $quote->isFirm() ? $quote->firmSummary() : null;
    $label = 'font-size:11px;letter-spacing:0.04em;text-transform:uppercase;color:#64748b;font-weight:600;';
    $muted = 'color:#6b7280;';
    $cell = 'padding:8px 10px;border-bottom:1px solid #e5e7eb;font-size:14px;color:#111827;vertical-align:top;';
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>{{ \App\Models\Quote::typeLabel($quote->type) }} {{ $quote->quote_number }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="width:640px;max-width:640px;background:#ffffff;border-radius:12px;">
                    <tr>
                        <td style="padding:24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="vertical-align:top;">
                                        <div style="font-size:20px;line-height:1.35;font-weight:700;">{{ \App\Models\Quote::typeLabel($quote->type) }}</div>
                                        <div style="margin-top:4px;font-size:14px;{{ $muted }}">No <span style="font-weight:700;color:#111827;">{{ $quote->quote_number }}</span></div>
                                    </td>
                                    <td style="vertical-align:top;text-align:right;">
                                        <div style="font-size:14px;{{ $muted }}">Tarih <span style="color:#374151;">{{ $quote->created_at?->timezone('Europe/Istanbul')->format('d.m.Y') }}</span></div>
                                        <div style="font-size:14px;{{ $muted }}">Geçerlilik <span style="color:#374151;">{{ $quote->valid_until?->format('d.m.Y') ?? '—' }}</span></div>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:20px;border-top:1px solid #e5e7eb;">
                                <tr>
                                    <td width="50%" style="vertical-align:top;padding-top:16px;padding-right:16px;">
                                        <div style="{{ $label }}">Satıcı</div>
                                        <div style="margin-top:8px;font-size:14px;font-weight:700;line-height:1.4;">KAPİTAL ONLİNE BİLGİSAYAR VE İLETİŞİM HİZ. TİC. LTD. ŞTİ.</div>
                                        @unless ($quote->isOptional())
                                            <div style="margin-top:4px;font-size:14px;line-height:1.4;color:#374151;">Orta Mah. Ordu Sok. Ozan İş Merkezi No:21/8 Kartal / İstanbul</div>
                                            <div style="margin-top:4px;font-size:14px;line-height:1.4;color:#374151;">VD YAKACIK - Vergi No: 4980863169</div>
                                            <div style="margin-top:4px;font-size:14px;line-height:1.4;color:#374151;">E-posta: muhasebe@ko.com.tr</div>
                                        @endunless
                                    </td>
                                    <td width="50%" style="vertical-align:top;padding-top:16px;padding-left:16px;border-left:1px solid #f3f4f6;">
                                        <div style="{{ $label }}">Alıcı</div>
                                        <div style="margin-top:8px;font-size:14px;font-weight:700;line-height:1.4;">{{ $quote->customerCari?->name ?? '—' }}</div>
                                        @unless ($quote->isOptional())
                                            @if ($quote->customerCari?->tax_number)
                                                <div style="margin-top:4px;font-size:14px;color:#374151;">Vergi No: {{ $quote->customerCari->tax_number }}</div>
                                            @endif
                                            @if ($quote->customerCari?->email)
                                                <div style="margin-top:4px;font-size:14px;color:#374151;">E-posta: {{ $quote->customerCari->email }}</div>
                                            @endif
                                        @endunless
                                    </td>
                                </tr>
                            </table>

                            <div style="margin-top:28px;">
                                @if ($quote->isFirm())
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                        <tr>
                                            <th align="left" style="{{ $cell }}{{ $label }}">Ürün</th>
                                            <th align="left" style="{{ $cell }}{{ $label }}">Taahhüt</th>
                                            <th align="right" style="{{ $cell }}{{ $label }}">Adet</th>
                                            <th align="right" style="{{ $cell }}{{ $label }}">Birim fiyat</th>
                                            <th align="right" style="{{ $cell }}{{ $label }}">Tutar</th>
                                        </tr>
                                        @foreach ($quote->items as $item)
                                            <tr>
                                                <td style="{{ $cell }}font-weight:600;">{{ $item->product_name }}</td>
                                                <td style="{{ $cell }}">{{ $item->commitmentLabel() }}</td>
                                                <td align="right" style="{{ $cell }}">{{ $item->quantity }}</td>
                                                <td align="right" style="{{ $cell }}">{{ $quote->formatMoney($item->birim_satis) }}</td>
                                                <td align="right" style="{{ $cell }}font-weight:600;">{{ $quote->formatMoney($item->saleTotal()) }}</td>
                                            </tr>
                                        @endforeach
                                    </table>
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:16px;">
                                        <tr>
                                            <td style="vertical-align:top;font-size:14px;line-height:1.45;color:#374151;padding-right:16px;">
                                                <div>Birim fiyatlara KDV dahil değildir.</div>
                                                @foreach (\App\Models\Quote::COMMITMENTS as $tip)
                                                    @if ($used->contains($tip))
                                                        <div style="margin-top:4px;">{{ $paymentNotes[$tip] }}</div>
                                                    @endif
                                                @endforeach
                                            </td>
                                            <td style="vertical-align:top;text-align:right;white-space:nowrap;font-size:14px;color:#374151;">
                                                <div>Ara toplam: {{ $quote->formatMoney($summary['net']) }}</div>
                                                <div style="margin-top:4px;">KDV (%{{ \App\Services\QuoteMath::display($quote->vat_rate) }}): {{ $quote->formatMoney($summary['vat']) }}</div>
                                                <div style="margin-top:4px;font-size:16px;font-weight:700;color:#111827;">Genel toplam: {{ $quote->formatMoney($summary['gross']) }}</div>
                                            </td>
                                        </tr>
                                    </table>
                                @else
                                    @foreach ($quote->items as $item)
                                        <div style="margin:0 0 8px;font-size:16px;font-weight:700;"><span style="display:inline-block;margin-right:6px;padding:2px 8px;border-radius:6px;background:#e2e8f0;font-size:15px;">{{ $item->quantity }} adet</span> - {{ $item->product_name }}</div>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:24px;">
                                            <tr>
                                                <th style="{{ $cell }}width:18%;"></th>
                                                @foreach (\App\Models\Quote::COMMITMENTS as $tip)
                                                    <th align="right" style="{{ $cell }}font-weight:700;">
                                                        {{ \App\Models\Quote::commitmentLabel($tip) }}
                                                        <div style="margin-top:3px;font-size:12px;font-weight:400;{{ $muted }}">{{ $paymentHints[$tip] }}</div>
                                                    </th>
                                                @endforeach
                                            </tr>
                                            <tr>
                                                <th align="left" style="{{ $cell }}font-weight:500;{{ $muted }}">Birim fiyat</th>
                                                @foreach (\App\Models\Quote::COMMITMENTS as $tip)
                                                    @php $option = $item->options->firstWhere('taahhut_tipi', $tip); @endphp
                                                    <td align="right" style="{{ $cell }}">{{ $option ? $quote->formatMoney($option->birim_satis) : '—' }}</td>
                                                @endforeach
                                            </tr>
                                            <tr>
                                                <th align="left" style="{{ $cell }}font-weight:500;{{ $muted }}">Tutar</th>
                                                @foreach (\App\Models\Quote::COMMITMENTS as $tip)
                                                    @php $option = $item->options->firstWhere('taahhut_tipi', $tip); @endphp
                                                    <td align="right" style="{{ $cell }}font-weight:700;">{{ $option ? $quote->formatMoney($option->saleTotal()) : '—' }}</td>
                                                @endforeach
                                            </tr>
                                        </table>
                                    @endforeach
                                    <div style="font-size:14px;color:#374151;">Birim fiyatlara KDV dahil değildir.</div>
                                @endif
                            </div>

                            <div style="margin-top:24px;padding-top:16px;border-top:1px solid #e5e7eb;font-size:14px;line-height:1.5;color:#374151;">
                                @if (filled($quote->notes))
                                    <div style="white-space:pre-line;margin-bottom:12px;">{{ $quote->notes }}</div>
                                @endif
                                @foreach (preg_split("/\n/", \App\Automation\QuotePlaceholders::terms()) as $line)
                                    <div>{{ $line }}</div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
