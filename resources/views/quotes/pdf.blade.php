@extends('layouts.report-pdf')

@section('title', \App\Models\Quote::typeLabel($quote->type).' '.$quote->quote_number)

@section('extra-css')
    .logo { display: block; height: 36px; width: auto; margin: 0 0 14px; }
    .top { width: 100%; }
    .top td { vertical-align: top; border: 0; }
    .dates { text-align: right; }
    .type { font-size: 16px; font-weight: 700; }
    .muted { color: #6b7280; }
    .parties { width: 100%; margin-top: 14px; border-top: 1px solid #e5e7eb; }
    .parties td { width: 50%; vertical-align: top; border: 0; padding-top: 12px; }
    .parties td + td { border-left: 1px solid #f3f4f6; padding-left: 12px; }
    .label { font-size: 10px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #64748b; }
    .name { margin-top: 6px; font-weight: 700; }
    .line { margin-top: 3px; }
    .product { margin-top: 18px; font-size: 13px; font-weight: 700; }
    .qty { display: inline-block; margin-right: 6px; padding: 1px 6px; background: #e2e8f0; font-size: 13px; font-weight: 800; }
    .compare { margin-top: 8px; }
    .compare th, .compare td { border-bottom: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: top; }
    .compare thead th { text-align: right; font-size: 11px; }
    .compare thead th:first-child { width: 18%; }
    .compare tbody th { text-align: left; font-weight: 500; color: #6b7280; }
    .compare td { text-align: right; }
    .pay { display: block; margin-top: 2px; font-size: 10px; font-weight: 400; color: #6b7280; }
    .lines { margin-top: 16px; }
    .lines th, .lines td { border-bottom: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
    .lines .num { text-align: right; }
    .summary { width: 100%; margin-top: 12px; }
    .summary td { vertical-align: top; border: 0; }
    .figures { text-align: right; white-space: nowrap; }
    .figures p { margin-top: 3px; }
    .gross { font-size: 13px; font-weight: 700; }
    .note { margin-top: 16px; white-space: pre-line; }
    .terms { margin-top: 12px; padding-top: 10px; border-top: 1px solid #e5e7eb; }
    .terms li { margin-top: 3px; list-style: none; }
@endsection

@section('content')
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
        $logoPath = resource_path('images/ko.png');
        $logoSrc = is_file($logoPath)
            ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath))
            : null;
    @endphp

    @if ($logoSrc)
        <img src="{{ $logoSrc }}" alt="Kapital Online" class="logo">
    @endif

    <table class="top">
        <tr>
            <td>
                <p class="type">{{ \App\Models\Quote::typeLabel($quote->type) }}</p>
                <p class="line"><span class="muted">No</span> <strong>{{ $quote->quote_number }}</strong></p>
            </td>
            <td class="dates">
                <p><span class="muted">Tarih</span> {{ $quote->created_at?->timezone('Europe/Istanbul')->format('d.m.Y') }}</p>
                <p><span class="muted">Geçerlilik</span> {{ $quote->valid_until?->format('d.m.Y') ?? '—' }}</p>
            </td>
        </tr>
    </table>

    <table class="parties">
        <tr>
            <td>
                <p class="label">Satıcı</p>
                <p class="name">KAPİTAL ONLİNE BİLGİSAYAR VE İLETİŞİM HİZ. TİC. LTD. ŞTİ.</p>
                @unless ($quote->isOptional())
                    <p class="line">Orta Mah. Ordu Sok. Ozan İş Merkezi No:21/8 Kartal / İstanbul</p>
                    <p class="line">VD YAKACIK - Vergi No: 4980863169</p>
                    <p class="line">E-posta: muhasebe@ko.com.tr</p>
                @endunless
            </td>
            <td>
                <p class="label">Alıcı</p>
                <p class="name">{{ $quote->customerCari?->name ?? '—' }}</p>
                @unless ($quote->isOptional())
                    @if ($quote->customerCari?->tax_number)
                        <p class="line">Vergi No: {{ $quote->customerCari->tax_number }}</p>
                    @endif
                    @if ($quote->customerCari?->email)
                        <p class="line">E-posta: {{ $quote->customerCari->email }}</p>
                    @endif
                @endunless
            </td>
        </tr>
    </table>

    @if ($quote->isFirm())
        <table class="lines">
            <thead>
                <tr>
                    <th>Ürün</th>
                    <th>Taahhüt</th>
                    <th class="num">Adet</th>
                    <th class="num">Birim fiyat</th>
                    <th class="num">Tutar</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($quote->items as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ $item->commitmentLabel() }}</td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">{{ $quote->formatMoney($item->birim_satis) }}</td>
                        <td class="num">{{ $quote->formatMoney($item->saleTotal()) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @php
            $used = $quote->items->pluck('taahhut_tipi')->filter()->unique();
            $summary = $quote->firmSummary();
        @endphp
        <table class="summary">
            <tr>
                <td>
                    <p>Birim fiyatlara KDV dahil değildir.</p>
                    @foreach (\App\Models\Quote::COMMITMENTS as $tip)
                        @if ($used->contains($tip))
                            <p>{{ $paymentNotes[$tip] }}</p>
                        @endif
                    @endforeach
                </td>
                <td class="figures">
                    <p>Ara toplam: {{ $quote->formatMoney($summary['net']) }}</p>
                    <p>KDV (%{{ \App\Services\QuoteMath::display($quote->vat_rate) }}): {{ $quote->formatMoney($summary['vat']) }}</p>
                    <p class="gross">Genel toplam: {{ $quote->formatMoney($summary['gross']) }}</p>
                </td>
            </tr>
        </table>
    @else
        @foreach ($quote->items as $item)
            <h2 class="product"><span class="qty">{{ $item->quantity }} adet</span> - {{ $item->product_name }}</h2>
            <table class="compare">
                <thead>
                    <tr>
                        <th></th>
                        @foreach (\App\Models\Quote::COMMITMENTS as $tip)
                            <th>
                                {{ \App\Models\Quote::commitmentLabel($tip) }}
                                <span class="pay">{{ $paymentHints[$tip] }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th>Birim fiyat</th>
                        @foreach (\App\Models\Quote::COMMITMENTS as $tip)
                            @php $option = $item->options->firstWhere('taahhut_tipi', $tip); @endphp
                            <td>{{ $option ? $quote->formatMoney($option->birim_satis) : '—' }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <th>Tutar</th>
                        @foreach (\App\Models\Quote::COMMITMENTS as $tip)
                            @php $option = $item->options->firstWhere('taahhut_tipi', $tip); @endphp
                            <td><strong>{{ $option ? $quote->formatMoney($option->saleTotal()) : '—' }}</strong></td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        @endforeach
        <p class="line" style="margin-top: 12px;">Birim fiyatlara KDV dahil değildir.</p>
    @endif

    @if (filled($quote->notes))
        <p class="note">{{ $quote->notes }}</p>
    @endif
    <ol class="terms">
        <li>1. Sipariş geçildikten sonra iade veya iptal hakkı yoktur.</li>
        <li>2. Sipariş geçilirken bir sonraki sene otomatik yenileme yapılıp yapılmayacağının iletilmesi rica edilir. Otomatik yenileme fiyatı sabitlemez. Fiyatlarda değişim var ise bilgi verilir.</li>
        <li>3. Taahhütlü siparişlerde 12 ay içerisinde adet azaltma hakkı bulunmamaktadır.</li>
        <li>4. Taahhütlü siparişlerde 12 ay içerisinde istenilen zaman adet arttırma yapılabilir, kalan gün sayısı üzerinden fatura kesilir.</li>
        <li>5. Aylık taahhütsüz geçilen siparişlerde fiyat koruması yoktur, üyelik devam ettiği sürece aylık olarak her ayın güncel fiyatları ile faturalandırılır. Yıllık Taahütlü siparişerin 12 aylık toplam bedeli sipariş geçildiğinde faturalandırılır.</li>
        <li>6. Sizlerden olumlu olumsuz bilgi gelmemesi durumunda, otomatik yenileme yapılmayacak ve yenilemeler sistem tarafından otomatik durdurulacaktır.</li>
    </ol>
@endsection
