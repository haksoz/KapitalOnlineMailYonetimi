<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Kesin teklife dönüştür">
        <x-slot name="left">
            <a href="{{ route('quotes.show', $quote) }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    <form method="POST" action="{{ route('quotes.convert.store', $quote) }}" class="bg-white rounded-xl shadow-sm p-6 max-w-4xl space-y-6">
        @csrf
        <p class="text-sm text-gray-600">
            {{ $quote->quote_number }} · {{ $quote->customerCari?->name }} · {{ $quote->currencyLabel() }}
        </p>
        <p class="text-xs text-gray-500">Satış fiyatı, birim fiyat teklifindeki kopyadan gelir. Adet burada girilir. İsterseniz dönüşüm sırasında fiyatı yeniden değiştirebilirsiniz. Alış fiyatı katalogdan tekrar okunmaz.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="vat_rate" value="KDV (%) *" />
                <x-text-input id="vat_rate" name="vat_rate" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('vat_rate', '20')" required />
            </div>
            <div>
                <x-input-label for="valid_until" value="Geçerlilik tarihi" />
                <x-text-input id="valid_until" name="valid_until" type="date" class="mt-1 block w-full" :value="old('valid_until', $quote->valid_until?->format('Y-m-d'))" />
            </div>
        </div>
        <div>
            <x-input-label for="notes" value="Müşteri notu" />
            <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('notes', $quote->notes) }}</textarea>
        </div>
        <div>
            <x-input-label for="internal_notes" value="İç not" />
            <textarea id="internal_notes" name="internal_notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('internal_notes', $quote->internal_notes) }}</textarea>
        </div>

        <div class="space-y-4">
            @foreach ($quote->items as $index => $item)
                @php
                    $prices = [];
                    foreach ($item->options as $option) {
                        $prices[$option->taahhut_tipi] = (string) \Brick\Math\BigDecimal::of((string) $option->birim_satis)->toScale(2, \Brick\Math\RoundingMode::HALF_UP);
                    }
                    $firstTip = $item->options->sortBy(fn ($option) => array_search($option->taahhut_tipi, \App\Models\Quote::COMMITMENTS, true))->first()?->taahhut_tipi;
                @endphp
                <div
                    class="border border-gray-200 rounded-xl p-4 space-y-3"
                    x-data="{
                        tip: @js(old('lines.'.$index.'.taahhut_tipi', $firstTip)),
                        prices: @js($prices),
                        satis: @js(old('lines.'.$index.'.birim_satis', $firstTip ? ($prices[$firstTip] ?? '') : '')),
                        pick(tip) {
                            this.tip = tip;
                            this.satis = this.prices[tip] ?? '';
                        }
                    }"
                >
                    <input type="hidden" name="lines[{{ $index }}][quote_item_id]" value="{{ $item->id }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-medium text-gray-900">{{ $item->product_name }}</p>
                            @if ($item->hasQuantity())
                                <p class="text-xs text-gray-500">Birim fiyat teklifi adedi: {{ $item->quantity }}</p>
                            @endif
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="lines[{{ $index }}][include]" value="1" class="rounded border-gray-300 text-slate-600 focus:ring-slate-500" @checked(! session()->hasOldInput() || old('lines.'.$index.'.include'))>
                            Dahil et
                        </label>
                    </div>
                    <div class="space-y-2">
                        @foreach (\App\Models\Quote::COMMITMENTS as $tip)
                            @php $option = $item->options->firstWhere('taahhut_tipi', $tip); @endphp
                            @if ($option)
                                <label class="flex items-center justify-between gap-3 text-sm border border-gray-100 rounded-lg px-3 py-2">
                                    <span class="inline-flex items-center gap-2">
                                        <input type="radio" name="lines[{{ $index }}][taahhut_tipi]" value="{{ $tip }}" class="border-gray-300 text-slate-600 focus:ring-slate-500" @checked(old('lines.'.$index.'.taahhut_tipi', $firstTip) === $tip) @click="pick('{{ $tip }}')">
                                        {{ $option->commitmentLabel() }}
                                    </span>
                                    <span class="text-gray-600">
                                        {{ $quote->formatMoney($option->birim_satis) }}
                                        @if ($item->hasQuantity())
                                            · {{ $item->quantity }} adet = {{ $quote->formatMoney($option->saleTotal()) }}
                                        @endif
                                    </span>
                                </label>
                            @endif
                        @endforeach
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label :for="'quantity_'.$index" value="Adet *" />
                            <x-text-input :id="'quantity_'.$index" type="number" min="1" step="1" class="mt-1 block w-full" name="lines[{{ $index }}][quantity]" :value="old('lines.'.$index.'.quantity', $item->quantity)" />
                        </div>
                        <div>
                            <x-input-label :for="'satis_'.$index" value="Birim satış *" />
                            <input :id="'satis_'.$index" type="number" min="0" step="0.01" name="lines[{{ $index }}][birim_satis]" x-model="satis" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex gap-3">
            <x-primary-button>Kesin teklif oluştur</x-primary-button>
            <a href="{{ route('quotes.show', $quote) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">Vazgeç</a>
        </div>
    </form>
</x-app-layout>
