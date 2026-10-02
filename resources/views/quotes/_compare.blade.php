@php
    $paymentHints = [
        \App\Models\Subscription::TAAHHUT_MONTHLY_COMMITMENT => 'Yıllık taahhüt, aylık ödeme',
        \App\Models\Subscription::TAAHHUT_MONTHLY_NO_COMMITMENT => 'Aylık ödeme',
        \App\Models\Subscription::TAAHHUT_ANNUAL_COMMITMENT => 'Yıllık ödeme',
    ];
@endphp

@foreach ($quote->items as $item)
    <section class="mb-8">
        <h2 class="mb-3 text-base font-semibold text-gray-900"><span class="quote-qty">{{ $item->quantity }} adet</span> - {{ $item->product_name }}</h2>
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
                <tr>
                    <th scope="row">Birim fiyat</th>
                    @foreach (\App\Models\Quote::COMMITMENTS as $tip)
                        @php $option = $item->options->firstWhere('taahhut_tipi', $tip); @endphp
                        <td>{{ $option ? $quote->formatMoney($option->birim_satis) : '—' }}</td>
                    @endforeach
                </tr>
                <tr>
                    <th scope="row">Tutar</th>
                    @foreach (\App\Models\Quote::COMMITMENTS as $tip)
                        @php $option = $item->options->firstWhere('taahhut_tipi', $tip); @endphp
                        <td><strong>{{ $option ? $quote->formatMoney($option->saleTotal()) : '—' }}</strong></td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </section>
@endforeach

<p class="text-sm text-gray-700">Birim fiyatlara KDV dahil değildir.</p>
