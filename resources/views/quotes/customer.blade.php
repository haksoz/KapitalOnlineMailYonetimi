<x-app-layout>
    <style>
        .quote-letterhead-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 1.5rem; }
        .quote-dates { margin-left: auto; text-align: right; }
        .quote-type { margin-top: 0.35rem; font-size: 1.25rem; line-height: 1.35; font-weight: 700; color: #111827; }
        @media print {
            aside, header, .no-print { display: none !important; }
            .lg\:ml-64 { margin-left: 0 !important; }
            body { background: white !important; }
            .quote-letterhead-top { display: flex !important; justify-content: space-between !important; }
            .quote-dates { text-align: right !important; }
            .quote-letterhead-parties { display: grid !important; grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
        }
    </style>

    <div class="no-print mb-4">
        <x-page-toolbar title="Müşteri belgesi">
            <x-slot name="left">
                <a href="{{ route('quotes.show', $quote) }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50" aria-label="Geri">
                    <span aria-hidden="true">&larr;</span>
                </a>
            </x-slot>
            <x-slot name="right">
                <button type="button" onclick="window.print()" class="inline-flex items-center justify-center min-h-[40px] px-3 py-2 bg-slate-800 rounded-lg text-xs font-semibold text-white uppercase tracking-widest">Yazdır</button>
            </x-slot>
        </x-page-toolbar>
        <p class="text-xs text-gray-500 mb-4">Bu görünüm müşteriye verilir. Alış fiyatı, kâr ve iç not burada yoktur.</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-6xl">
        <div class="border-b border-gray-200 pb-5 mb-6 space-y-5">
            <div class="quote-letterhead-top">
                <section>
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Teklif bilgileri</h2>
                    <p class="quote-type">{{ \App\Models\Quote::typeLabel($quote->type) }}</p>
                    <p class="mt-1 text-sm text-gray-700"><span class="text-gray-500">No</span> <span class="font-semibold text-gray-900">{{ $quote->quote_number }}</span></p>
                </section>
                <section class="quote-dates">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Tarih bilgileri</h2>
                    <p class="mt-2 text-sm text-gray-700"><span class="text-gray-500">Tarih</span> {{ $quote->created_at?->timezone('Europe/Istanbul')->format('d.m.Y') }}</p>
                    <p class="text-sm text-gray-700"><span class="text-gray-500">Geçerlilik</span> {{ $quote->valid_until?->format('d.m.Y') ?? '—' }}</p>
                </section>
            </div>
            <div class="quote-letterhead-parties grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-gray-100 pt-5">
                <section>
                    @if ($quote->isOptional())
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Satıcı bilgileri</h2>
                        <p class="mt-2 text-sm font-semibold leading-snug text-gray-900">KAPİTAL ONLİNE BİLGİSAYAR VE İLETİŞİM HİZ. TİC. LTD. ŞTİ.</p>
                    @else
                        @include('quotes._seller')
                    @endif
                </section>
                <section class="sm:border-l sm:border-gray-100 sm:pl-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Alıcı bilgileri</h2>
                    <p class="mt-2 text-sm font-semibold leading-snug text-gray-900">{{ $quote->customerCari?->name ?? '—' }}</p>
                    @unless ($quote->isOptional())
                        <dl class="mt-2 space-y-1 text-sm text-gray-700">
                            <div class="flex gap-2"><dt class="shrink-0 text-gray-500">Vergi no</dt><dd>{{ $quote->customerCari?->tax_number ?: '—' }}</dd></div>
                            <div class="flex gap-2"><dt class="shrink-0 text-gray-500">E-posta</dt><dd class="break-all">{{ $quote->customerCari?->email ?: '—' }}</dd></div>
                        </dl>
                    @endunless
                </section>
            </div>
        </div>

        @if ($quote->isFirm())
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
                                @if ($item->stock_code)
                                    <div class="text-xs text-gray-500">{{ $item->stock_code }}</div>
                                @endif
                            </td>
                            <td class="py-3 pr-3">{{ $item->commitmentLabel() }}</td>
                            <td class="py-3 pr-3 text-right">{{ $item->quantity }}</td>
                            <td class="py-3 pr-3 text-right">{{ $quote->formatMoney($item->birim_satis) }}</td>
                            <td class="py-3 text-right font-medium">{{ $quote->formatMoney($item->saleTotal()) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @php $summary = $quote->firmSummary(); @endphp
            <div class="mt-4 space-y-1 text-sm text-right">
                <p>Ara toplam: {{ $quote->formatMoney($summary['net']) }}</p>
                <p>KDV (%{{ \App\Services\QuoteMath::display($quote->vat_rate) }}): {{ $quote->formatMoney($summary['vat']) }}</p>
                <p class="text-base font-semibold">Genel toplam: {{ $quote->formatMoney($summary['gross']) }}</p>
            </div>
        @else
            @foreach ($quote->items as $item)
                <div class="mb-6">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="font-semibold text-gray-900">{{ $item->product_name }}</h2>
                            @if ($item->stock_code)
                                <p class="text-xs text-gray-500">{{ $item->stock_code }}</p>
                            @endif
                        </div>
                        <p class="shrink-0 rounded-lg border border-slate-300 bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-900">Adet: {{ $item->quantity }}</p>
                    </div>
                    <table class="min-w-full text-sm mt-2">
                        <thead>
                            <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wider text-gray-500">
                                <th class="py-2 pr-3">Taahhüt</th>
                                <th class="py-2 pr-3 text-right">Birim fiyat</th>
                                <th class="py-2 text-right">Tutar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (\App\Models\Quote::COMMITMENTS as $tip)
                                @php $option = $item->options->firstWhere('taahhut_tipi', $tip); @endphp
                                @if ($option)
                                    <tr class="border-b border-gray-100">
                                        <td class="py-2 pr-3">{{ $option->commitmentLabel() }}</td>
                                        <td class="py-2 pr-3 text-right">{{ $quote->formatMoney($option->birim_satis) }}</td>
                                        <td class="py-2 text-right font-medium">{{ $quote->formatMoney($option->saleTotal()) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        @endif

        @include('quotes._terms')
    </div>
</x-app-layout>
