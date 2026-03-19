<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Yeni Faturalandırma">
        <x-slot name="left">
            <a href="{{ route('sales-invoices.index') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-3xl">
        @if (!($fromSelection ?? false))
            <form action="{{ route('sales-invoices.create') }}" method="GET" class="mb-6">
                <div class="flex flex-wrap items-end gap-3">
                    <div class="min-w-[200px]">
                        <x-input-label for="customer_cari_id" value="Müşteri (Cari) *" />
                        <select id="customer_cari_id" name="customer_cari_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                            <option value="">— Seçin —</option>
                            @foreach ($customerCaris as $c)
                                <option value="{{ $c->id }}" @selected((string) $customerCariId === (string) $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded-lg font-semibold text-sm hover:bg-slate-700">
                        Siparişleri getir
                    </button>
                </div>
            </form>
        @else
            <p class="mb-4 text-sm text-gray-700">
                <span class="font-medium text-gray-900">Müşteri:</span>
                {{ $pendingBillings->first()?->subscription?->customerCari?->name ?? '—' }}
                <a href="{{ route('pending-billings.index', ['status' => 'pending']) }}" class="ml-2 text-slate-600 hover:text-slate-800 text-sm">← Siparişlere dön</a>
            </p>
        @endif

        @if ($customerCariId && $pendingBillings->isEmpty())
            <p class="text-sm text-gray-600">
                Bu müşteri için bekleyen (henüz faturalanmamış) sipariş kaydı yok.
            </p>
        @endif

        @if ($pendingBillings->isNotEmpty())
            <form action="{{ route('sales-invoices.store') }}" method="POST">
                @csrf
                <input type="hidden" name="customer_cari_id" value="{{ $customerCariId }}">
                @if ($fromSelection ?? false)
                    @foreach ($pendingBillings as $pb)
                        <input type="hidden" name="pending_billing_ids[]" value="{{ $pb->id }}">
                    @endforeach
                @endif
                <p class="text-sm text-gray-600 mb-3">
                    @if ($fromSelection ?? false)
                        Seçtiğiniz siparişler aşağıda. Önceki dönemler farkını eklemek istediğiniz satırı işaretleyip fatura oluşturabilirsiniz.
                    @else
                        Faturalandırmak istediğiniz kayıtları işaretleyin. Toplu göndermek için birden fazla seçebilirsiniz.
                    @endif
                </p>
                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                @if (!($fromSelection ?? false))
                                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Seç</th>
                                @endif
                                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sözleşme / Ürün</th>
                                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Dönem</th>
                                <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Tutar (TL)</th>
                                <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Önceki dönemler farkı</th>
                                <th scope="col" class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Farkı ekle</th>
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
                                    $accFark = $accumulatedFarkBySubscription[$pb->subscription_id ?? 0] ?? 0;
                                    $showFarkCheckbox = $accFark != 0;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    @if (!($fromSelection ?? false))
                                    <td class="px-4 py-2">
                                        <input type="checkbox" name="pending_billing_ids[]" value="{{ $pb->id }}" class="rounded border-gray-300 text-slate-600 focus:ring-slate-500">
                                    </td>
                                    @endif
                                    <td class="px-4 py-2 text-sm">
                                        <span class="font-medium text-gray-900">{{ $pb->subscription->sozlesme_no }}</span>
                                        @if ($pb->subscription->product)
                                            <br><span class="text-gray-500">{{ $pb->subscription->product->name }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-600">
                                        {{ $pb->period_start?->locale('tr')->translatedFormat('F Y') }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-right font-medium text-gray-900">
                                        @if ($amount !== null)
                                            {{ number_format((float) $amount, 2, ',', '.') }} ₺
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm text-right">
                                        @if ($accFark != 0)
                                            <span class="{{ $accFark > 0 ? 'text-amber-700' : 'text-slate-600' }} font-medium">{{ number_format($accFark, 2, ',', '.') }} ₺</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        @if ($showFarkCheckbox)
                                            <label class="inline-flex items-center gap-1">
                                                <input type="checkbox" name="add_fark[]" value="{{ $pb->id }}" class="rounded border-gray-300 text-slate-600 focus:ring-slate-500">
                                                <span class="text-xs text-gray-600">Bu satıra ekle</span>
                                            </label>
                                        @else
                                            <span class="text-gray-400 text-xs">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @error('pending_billing_ids')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-xs text-gray-500">
                    Önceki dönemlerden kalan fark varsa, &quot;Farkı ekle&quot; ile bu satırın fatura tutarına eklenir (abonelik başına yalnızca bir satırda işaretleyin). Sonraki alış faturası girildiğinde fark negatif düşer ve toplam sıfırlanır.
                </p>
                <div class="mt-4 flex gap-3">
                    <x-primary-button type="submit">Seçilenleri faturalandır</x-primary-button>
                    <a href="{{ route('sales-invoices.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">İptal</a>
                </div>
            </form>
        @endif
    </div>
</x-app-layout>
