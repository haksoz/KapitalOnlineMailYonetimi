<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Yeni Giderleştirme">
        <x-slot name="left">
            <a href="{{ route('pending-billings.index', ['status' => 'pending']) }}" class="inline-flex items-center gap-2 px-4 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation text-sm font-medium text-gray-700" aria-label="Siparişlere geri dön">
                <span aria-hidden="true">&larr;</span>
                Siparişlere geri dön
            </a>
        </x-slot>
    </x-page-toolbar>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-3xl">
        @php
            $selectedPeriods = $pendingBillings
                ->pluck('period_start')
                ->filter()
                ->map(fn ($d) => $d->format('Y-m'))
                ->unique()
                ->sort()
                ->values();
            $hasMixedPeriods = $selectedPeriods->count() > 1;
            $selectedPeriodsLabel = $selectedPeriods
                ->map(function ($ym) {
                    try {
                        return \Carbon\Carbon::createFromFormat('Y-m', $ym)->locale('tr')->translatedFormat('F Y');
                    } catch (\Throwable) {
                        return $ym;
                    }
                })
                ->implode(', ');
        @endphp

        <p class="mb-4 text-sm text-gray-700">
            <span class="font-medium text-gray-900">Müşteri:</span>
            {{ $pendingBillings->first()?->subscription?->customerCari?->name ?? '—' }}
            <a href="{{ route('pending-billings.index', ['status' => 'pending']) }}" class="ml-2 text-slate-600 hover:text-slate-800 text-sm">← Siparişlere dön</a>
        </p>

        @if ($hasMixedPeriods)
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
                Seçilen siparişler farklı aylara ait:
                <span class="font-medium">{{ $selectedPeriodsLabel }}</span>.
                Aynı giderleştirmede birleştirmeden önce dönemleri kontrol edin.
            </div>
        @endif

        <p class="mb-3 text-sm text-gray-600">
            Bu işlem faturasız (KDV’siz) satış kaydıdır. Seçilen siparişler giderleştirilir; gerçekleşen satış tutarı yazılır ve GDN numarası atanır.
        </p>

        <form
            action="{{ route('expense-settlements.store') }}"
            method="POST"
            id="expense-settlement-create-form"
            @if ($hasMixedPeriods) data-mixed-periods="{{ $selectedPeriodsLabel }}" @endif
        >
            @csrf
            <input type="hidden" name="customer_cari_id" value="{{ $customerCariId }}">
            @foreach ($pendingBillings as $pb)
                <input type="hidden" name="pending_billing_ids[]" value="{{ $pb->id }}">
            @endforeach

            <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="settlement_date" value="Giderleştirme tarihi" />
                    <x-text-input id="settlement_date" name="settlement_date" type="date" class="mt-1 block w-full" :value="old('settlement_date', now()->toDateString())" />
                </div>
                <div>
                    <x-input-label for="notes" value="Not (opsiyonel)" />
                    <x-text-input id="notes" name="notes" type="text" class="mt-1 block w-full" :value="old('notes')" />
                </div>
            </div>

            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sözleşme / Ürün</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Dönem</th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Tutar (TL)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($pendingBillings as $pb)
                            @php
                                $amount = $pb->actual_satis_tl ?? $pb->expected_satis_tl;
                                if ($amount === null || $amount === '') {
                                    $usdRate = $usdEfektifSelling ?? null;
                                    if ($usdRate !== null) {
                                        $sub = $pb->subscription;
                                        $usdAlis = $sub->usd_birim_alis !== null && $sub->usd_birim_alis !== '' ? (float) $sub->usd_birim_alis : null;
                                        $usdSatis = $sub->usd_birim_satis !== null && $sub->usd_birim_satis !== '' ? (float) $sub->usd_birim_satis : null;
                                        if ($usdAlis !== null && $usdAlis > 0 && $usdSatis !== null) {
                                            $qty = (int) $sub->quantity;
                                            $alisKdvHaric = $usdAlis * $qty * $usdRate;
                                            $amount = $alisKdvHaric * ($usdSatis / $usdAlis);
                                        }
                                    }
                                }
                                $amount = $amount !== null && $amount !== '' ? (float) $amount : null;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm">
                                    <span class="font-medium text-gray-900">{{ $pb->subscription->sozlesme_no }}</span>
                                    @if ($pb->subscription->product)
                                        <br><span class="text-gray-500">{{ $pb->subscription->product->name }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-600">
                                    {{ $pb->period_start?->locale('tr')->translatedFormat('F Y') }}
                                </td>
                                <td class="px-4 py-2 text-sm text-right">
                                    @php
                                        $defaultAmount = old('line_amounts.'.$pb->id, $amount !== null ? number_format($amount, 2, '.', '') : '');
                                    @endphp
                                    <input
                                        type="number"
                                        name="line_amounts[{{ $pb->id }}]"
                                        value="{{ $defaultAmount }}"
                                        step="0.01"
                                        min="0"
                                        required
                                        class="w-32 ml-auto block rounded-md border-gray-300 text-right text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                    >
                                    <p class="mt-1 text-[11px] text-gray-500 text-right leading-tight">
                                        Gerekirse düzenleyin; kesinleşen satış olur.
                                    </p>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @error('line_amounts')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('line_amounts.*')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500">
                Tutar alanını gerekirse elle düzenleyebilirsiniz; kaydedilen tutar kesinleşen satış olur.
            </p>

            <div class="mt-4 flex gap-3">
                <x-primary-button type="submit">Seçilenleri giderleştir</x-primary-button>
                <a href="{{ route('pending-billings.index', ['status' => 'pending']) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">İptal</a>
            </div>
        </form>
        <script>
            (function () {
                const form = document.getElementById('expense-settlement-create-form');
                if (!form) return;
                form.addEventListener('submit', function (e) {
                    const mixed = form.getAttribute('data-mixed-periods');
                    if (!mixed) return;
                    const ok = confirm(
                        'Seçilen siparişler farklı aylara ait (' + mixed + ').\n\n' +
                        'Ayrı ayları aynı giderleştirmede birleştirmek istediğinize emin misiniz?'
                    );
                    if (!ok) e.preventDefault();
                });
            })();
        </script>
    </div>
</x-app-layout>
