<x-app-layout>
    <style>
        .quote-letterhead-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 1.5rem; }
        .quote-dates { margin-left: auto; text-align: right; }
        .quote-type { font-size: 1.25rem; line-height: 1.35; font-weight: 700; color: #111827; }
        .quote-compare { width: 100%; border-collapse: collapse; }
        .quote-compare th, .quote-compare td { border-bottom: 1px solid #e5e7eb; padding: 0.6rem 0.75rem; vertical-align: top; }
        .quote-compare thead th { font-size: 0.8125rem; font-weight: 650; color: #111827; text-align: right; }
        .quote-compare thead th:first-child { width: 28%; text-align: left; }
        .quote-compare tbody th { text-align: left; font-weight: 600; color: #111827; font-size: 0.875rem; }
        .quote-compare td { text-align: right; font-size: 0.875rem; color: #111827; }
        .quote-pay { display: block; margin-top: 0.2rem; font-size: 0.75rem; font-weight: 400; color: #6b7280; }
        .quote-summary { display: flex; align-items: flex-start; justify-content: space-between; gap: 1.5rem; margin-top: 1rem; }
        .quote-summary-figures { margin-left: auto; text-align: right; white-space: nowrap; font-size: 0.875rem; color: #374151; }
        .quote-summary-figures p + p { margin-top: 0.25rem; }
        .quote-summary-figures .quote-gross { font-size: 1rem; font-weight: 700; color: #111827; }
        .quote-notices { margin-top: 1.5rem; border-top: 1px solid #e5e7eb; padding-top: 1rem; font-size: 0.875rem; line-height: 1.45; color: #374151; }
        .quote-notices div + div { margin-top: 0.25rem; }
        @media print {
            aside, header, .no-print { display: none !important; }
            .lg\:ml-64 { margin-left: 0 !important; }
            body { background: white !important; }
            .quote-letterhead-top { display: flex !important; justify-content: space-between !important; }
            .quote-dates { text-align: right !important; }
            .quote-letterhead-parties { display: grid !important; grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
            .quote-compare { width: 100% !important; }
            .quote-summary { display: flex !important; justify-content: space-between !important; }
            .quote-summary-figures { margin-left: auto !important; text-align: right !important; }
            .quote-notices { border-top: 1px solid #e5e7eb !important; }
        }
    </style>

    <div class="no-print mb-4">
        <x-flash-messages />
        <x-page-toolbar title="Müşteri belgesi">
            <x-slot name="left">
                <a href="{{ route('quotes.show', $quote) }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50" aria-label="Geri">
                    <span aria-hidden="true">&larr;</span>
                </a>
            </x-slot>
            <x-slot name="right">
                <a href="{{ route('quotes.customer.pdf', $quote) }}" class="inline-flex items-center justify-center min-h-[40px] px-3 py-2 bg-white border border-gray-300 rounded-lg text-xs font-semibold text-slate-800 uppercase tracking-widest">PDF indir</a>
                <button type="button" onclick="window.print()" class="inline-flex items-center justify-center min-h-[40px] px-3 py-2 bg-slate-800 rounded-lg text-xs font-semibold text-white uppercase tracking-widest">Yazdır</button>
            </x-slot>
        </x-page-toolbar>
        @if ($quote->isOptional())
            <p class="text-xs text-gray-500 mb-4">Bu belge birim fiyat bilgilendirmesidir. Müşteri PDF üzerinde ürünü işaretleyip adedi yazar. Adet ve son fiyat, kesin teklifte belirlenir. Alış, kâr ve iç not burada yoktur.</p>
        @else
            <p class="text-xs text-gray-500 mb-4">Bu görünüm müşteriye verilir. Alış fiyatı, kâr ve iç not burada yoktur.</p>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-6xl">
        <div class="quote-letterhead-top">
            <section>
                <p class="quote-type">{{ \App\Models\Quote::typeLabel($quote->type) }}</p>
                <p class="mt-1 text-sm text-gray-700"><span class="text-gray-500">No</span> <span class="font-semibold text-gray-900">{{ $quote->quote_number }}</span></p>
            </section>
            <section class="quote-dates">
                <p class="text-sm text-gray-700"><span class="text-gray-500">Tarih</span> {{ $quote->created_at?->timezone('Europe/Istanbul')->format('d.m.Y') }}</p>
                <p class="text-sm text-gray-700"><span class="text-gray-500">Geçerlilik</span> {{ $quote->valid_until?->format('d.m.Y') ?? '—' }}</p>
            </section>
        </div>

        <div class="quote-letterhead-parties mt-5 grid grid-cols-1 gap-4 border-t border-gray-200 pt-5 sm:grid-cols-2">
            <section>
                <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Satıcı</h2>
                @if ($quote->isOptional())
                    <p class="mt-2 text-sm font-semibold leading-snug text-gray-900">KAPİTAL ONLİNE BİLGİSAYAR VE İLETİŞİM HİZ. TİC. LTD. ŞTİ.</p>
                @else
                    <div class="mt-2">
                        @include('quotes._seller', ['bare' => true])
                    </div>
                @endif
            </section>
            <section class="sm:border-l sm:border-gray-100 sm:pl-4">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Alıcı</h2>
                <p class="mt-2 text-sm font-semibold leading-snug text-gray-900">{{ $quote->customerCari?->name ?? '—' }}</p>
                @unless ($quote->isOptional())
                    @if ($quote->customerCari?->tax_number)
                        <p class="mt-1 text-sm leading-snug text-gray-700">Vergi No: {{ $quote->customerCari->tax_number }}</p>
                    @endif
                    @if ($quote->customerCari?->email)
                        <p class="mt-1 text-sm leading-snug text-gray-700 break-all">E-posta: {{ $quote->customerCari->email }}</p>
                    @endif
                @endunless
            </section>
        </div>

        <div class="mt-8">
            @if ($quote->isFirm())
                @include('quotes._statement')
            @else
                @include('quotes._compare')
            @endif
        </div>

        @if ($quote->isFirm())
            @include('quotes._firm_notices')
        @endif

        @include('quotes._terms')
    </div>
</x-app-layout>
