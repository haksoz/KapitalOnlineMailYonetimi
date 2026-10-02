@php
    $paymentNotes = [
        \App\Models\Subscription::TAAHHUT_MONTHLY_COMMITMENT => 'Aylık taahhütlü seçeneğinde yıllık taahhüt verilir, aylık ödenir.',
        \App\Models\Subscription::TAAHHUT_MONTHLY_NO_COMMITMENT => 'Aylık taahhütsüz seçeneğinde aylık ödenir.',
        \App\Models\Subscription::TAAHHUT_ANNUAL_COMMITMENT => 'Yıllık taahhütlü seçeneğinde, yıllık ödenir.',
    ];
    $used = $quote->items->pluck('taahhut_tipi')->filter()->unique();
@endphp

<div class="quote-notices mt-6 border-t border-gray-200 pt-4 text-sm text-gray-700">
    <div>- Birim fiyatlara KDV dahil değildir.</div>
    @foreach (\App\Models\Quote::COMMITMENTS as $tip)
        @if ($used->contains($tip))
            <div style="margin-top:4px;">- {{ $paymentNotes[$tip] }}</div>
        @endif
    @endforeach
</div>
