@php
    $paymentHints = [
        \App\Models\Subscription::TAAHHUT_MONTHLY_COMMITMENT => 'Yıllık taahhüt, aylık ödeme',
        \App\Models\Subscription::TAAHHUT_MONTHLY_NO_COMMITMENT => 'Aylık ödeme',
        \App\Models\Subscription::TAAHHUT_ANNUAL_COMMITMENT => 'Yıllık ödeme',
    ];
@endphp

<p class="mb-4 text-sm leading-relaxed text-gray-700">{{ \App\Automation\QuotePlaceholders::optionalIntro() }}</p>

<table class="quote-compare">
    <thead>
        <tr>
            <th></th>
            @foreach (\App\Models\Quote::COMMITMENTS as $tip)
                <th>
                    {{ \App\Models\Quote::commitmentLabel($tip) }}
                    <span class="quote-pay">{{ $paymentHints[$tip] }}</span>
                </th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($quote->items as $item)
            <tr>
                <th scope="row">{{ $item->product_name }}</th>
                @foreach (\App\Models\Quote::COMMITMENTS as $tip)
                    @php $option = $item->options->firstWhere('taahhut_tipi', $tip); @endphp
                    <td>{{ $option ? $quote->formatMoney($option->birim_satis) : '—' }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>

<p class="mt-3 text-sm text-gray-700">Birim fiyatlara KDV dahil değildir.</p>
