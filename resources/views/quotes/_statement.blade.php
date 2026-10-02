@php
    $paymentNotes = [
        \App\Models\Subscription::TAAHHUT_MONTHLY_COMMITMENT => 'Aylık taahhütlü seçeneğinde yıllık taahhüt verilir, aylık ödenir.',
        \App\Models\Subscription::TAAHHUT_MONTHLY_NO_COMMITMENT => 'Aylık taahhütsüz seçeneğinde aylık ödenir.',
        \App\Models\Subscription::TAAHHUT_ANNUAL_COMMITMENT => 'Yıllık taahhütlü seçeneğinde, yıllık ödenir.',
    ];
    $used = $quote->items->pluck('taahhut_tipi')->filter()->unique();
    $summary = $quote->firmSummary();
@endphp

<table class="min-w-full text-sm">
    <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wider text-gray-500">
            <th class="py-2 pr-3">Ürün</th>
            <th class="py-2 pr-3">Taahhüt</th>
            <th class="py-2 pr-3 text-right">Adet</th>
            <th class="py-2 pr-3 text-right">Birim fiyat</th>
            <th class="py-2 text-right">Tutar</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($quote->items as $item)
            <tr class="border-b border-gray-100">
                <td class="py-3 pr-3">
                    <div class="font-medium text-gray-900">{{ $item->product_name }}</div>
                </td>
                <td class="py-3 pr-3">{{ $item->commitmentLabel() }}</td>
                <td class="py-3 pr-3 text-right">{{ $item->quantity }}</td>
                <td class="py-3 pr-3 text-right">{{ $quote->formatMoney($item->birim_satis) }}</td>
                <td class="py-3 text-right font-medium">{{ $quote->formatMoney($item->saleTotal()) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="quote-summary">
    <div class="quote-summary-notes">
        <p>Birim fiyatlara KDV dahil değildir.</p>
        @foreach (\App\Models\Quote::COMMITMENTS as $tip)
            @if ($used->contains($tip))
                <p>{{ $paymentNotes[$tip] }}</p>
            @endif
        @endforeach
    </div>
    <div class="quote-summary-figures">
        <p>Ara toplam: {{ $quote->formatMoney($summary['net']) }}</p>
        <p>KDV (%{{ \App\Services\QuoteMath::display($quote->vat_rate) }}): {{ $quote->formatMoney($summary['vat']) }}</p>
        <p class="quote-gross">Genel toplam: {{ $quote->formatMoney($summary['gross']) }}</p>
    </div>
</div>
