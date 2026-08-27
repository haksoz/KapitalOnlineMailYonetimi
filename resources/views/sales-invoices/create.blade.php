<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Yeni Faturalandırma">
        <x-slot name="left">
            @if ($fromSelection ?? false)
                <a href="{{ route('pending-billings.index', ['status' => 'pending']) }}" class="inline-flex items-center gap-2 px-4 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation text-sm font-medium text-gray-700" aria-label="Siparişlere geri dön">
                    <span aria-hidden="true">&larr;</span>
                    Siparişlere geri dön
                </a>
            @else
                <a href="{{ route('sales-invoices.index') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation" aria-label="Geri">
                    <span aria-hidden="true">&larr;</span>
                </a>
            @endif
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
            @if ($hasMixedPeriods)
                <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
                    Seçilen siparişler farklı aylara ait:
                    <span class="font-medium">{{ $selectedPeriodsLabel }}</span>.
                    Aynı faturada birleştirmeden önce dönemleri kontrol edin.
                </div>
            @endif
            <form action="{{ route('sales-invoices.store') }}" method="POST" id="sales-invoice-create-form" @if ($hasMixedPeriods) data-mixed-periods="{{ $selectedPeriodsLabel }}" @endif>
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
                                <tr class="hover:bg-gray-50" data-subscription-id="{{ $pb->subscription_id }}">
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
                                    <td class="px-4 py-2 text-sm text-right" data-fark-amount-cell>
                                        @if ($accFark != 0)
                                            <span class="{{ $accFark > 0 ? 'text-amber-700' : 'text-slate-600' }} font-medium" data-fark-amount-value>{{ number_format($accFark, 2, ',', '.') }} ₺</span>
                                            <span class="text-gray-400 hidden" data-fark-amount-placeholder>—</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-center" data-fark-action-cell>
                                        @if ($showFarkCheckbox)
                                            <label class="inline-flex items-center gap-1" data-fark-checkbox-wrap>
                                                <input
                                                    type="checkbox"
                                                    name="add_fark[]"
                                                    value="{{ $pb->id }}"
                                                    class="add-fark-checkbox rounded border-gray-300 text-slate-600 focus:ring-slate-500"
                                                    data-subscription-id="{{ $pb->subscription_id }}"
                                                >
                                                <span class="text-xs text-gray-600">Bu satıra ekle</span>
                                            </label>
                                            <span class="text-gray-400 text-xs hidden" data-fark-taken-note>Başka satıra eklendi</span>
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
                    Önceki dönemlerden kalan fark varsa, &quot;Farkı ekle&quot; ile bu satırın fatura tutarına eklenir (abonelik başına yalnızca bir satırda). Bir satırda işaretlediğinizde aynı aboneliğin diğer satırlarında fark seçeneği kapanır. Sonraki alış faturası girildiğinde fark negatif düşer ve toplam sıfırlanır.
                </p>
                <div class="mt-4 flex gap-3">
                    <x-primary-button type="submit">Seçilenleri faturalandır</x-primary-button>
                    <a href="{{ route('sales-invoices.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">İptal</a>
                </div>
            </form>
            <script>
                (function () {
                    const form = document.getElementById('sales-invoice-create-form');
                    if (!form) return;

                    function syncFarkForSubscription(subscriptionId) {
                        const checkboxes = Array.from(
                            form.querySelectorAll('.add-fark-checkbox[data-subscription-id="' + subscriptionId + '"]')
                        );
                        const selected = checkboxes.find(function (cb) { return cb.checked; }) || null;

                        checkboxes.forEach(function (cb) {
                            const row = cb.closest('tr');
                            if (!row) return;

                            const amountValue = row.querySelector('[data-fark-amount-value]');
                            const amountPlaceholder = row.querySelector('[data-fark-amount-placeholder]');
                            const checkboxWrap = row.querySelector('[data-fark-checkbox-wrap]');
                            const takenNote = row.querySelector('[data-fark-taken-note]');
                            const isSelectedRow = selected !== null && cb === selected;
                            const isTakenElsewhere = selected !== null && !isSelectedRow;

                            if (amountValue && amountPlaceholder) {
                                amountValue.classList.toggle('hidden', isTakenElsewhere);
                                amountPlaceholder.classList.toggle('hidden', !isTakenElsewhere);
                            }
                            if (checkboxWrap) {
                                checkboxWrap.classList.toggle('hidden', isTakenElsewhere);
                            }
                            if (takenNote) {
                                takenNote.classList.toggle('hidden', !isTakenElsewhere);
                            }

                            if (isTakenElsewhere) {
                                cb.checked = false;
                                cb.disabled = true;
                            } else {
                                cb.disabled = false;
                            }
                        });
                    }

                    form.querySelectorAll('.add-fark-checkbox').forEach(function (cb) {
                        cb.addEventListener('change', function () {
                            const subscriptionId = this.dataset.subscriptionId;
                            if (!subscriptionId) return;

                            if (this.checked) {
                                form.querySelectorAll(
                                    '.add-fark-checkbox[data-subscription-id="' + subscriptionId + '"]'
                                ).forEach(function (other) {
                                    if (other !== cb) other.checked = false;
                                });
                            }

                            syncFarkForSubscription(subscriptionId);
                        });
                    });

                    // Sayfa yükünde (geri gelme vb.) mevcut işaretlere göre senkronize et
                    const seen = {};
                    form.querySelectorAll('.add-fark-checkbox').forEach(function (cb) {
                        const id = cb.dataset.subscriptionId;
                        if (!id || seen[id]) return;
                        seen[id] = true;
                        syncFarkForSubscription(id);
                    });

                    form.addEventListener('submit', function (e) {
                        const mixed = form.getAttribute('data-mixed-periods');
                        if (mixed) {
                            const ok = confirm(
                                'Seçilen siparişler farklı aylara ait (' + mixed + ').\n\n' +
                                'Ayrı ayları aynı faturada birleştirmek istediğinize emin misiniz?'
                            );
                            if (!ok) {
                                e.preventDefault();
                                return;
                            }
                        }

                        // Müşteri seçip listeden işaretleme yolu: seçili satırlarda da dönem karışık olabilir
                        if (!mixed) {
                            const checked = Array.from(form.querySelectorAll('input[name="pending_billing_ids[]"]:checked'));
                            if (checked.length === 0) return;
                            const periodCells = checked.map(function (cb) {
                                const row = cb.closest('tr');
                                const cell = row ? row.querySelector('td:nth-child(3)') : null;
                                return cell ? cell.textContent.trim() : '';
                            }).filter(Boolean);
                            const unique = Array.from(new Set(periodCells));
                            if (unique.length > 1) {
                                const ok = confirm(
                                    'Seçilen siparişler farklı aylara ait (' + unique.join(', ') + ').\n\n' +
                                    'Ayrı ayları aynı faturada birleştirmek istediğinize emin misiniz?'
                                );
                                if (!ok) e.preventDefault();
                            }
                        }
                    });
                })();
            </script>
        @endif
    </div>
</x-app-layout>
