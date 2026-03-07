<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 min-w-0">
            <h1 class="text-lg sm:text-xl font-semibold text-gray-800 truncate">Siparişler</h1>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="mb-4">
        <p class="text-sm text-gray-600">
            Önce öde sonra kullan mantığına göre dönem başı gelen siparişler burada listelenir. Yeni abonelik oluşturulunca ilk dönem, sonraki dönemler ise günlük <code class="text-xs bg-gray-100 px-1 rounded">pending-billings:enqueue</code> komutu ile eklenir.
            Beklenen TL tutarlar işlem günü <strong>efektif satış</strong> kuru (USD) ile hesaplanır. Tabloda gördüğünüz tutarlar kayıtlı değilse anlık kur ile gösterilir; <strong>&quot;Kur ile hesapla&quot;</strong> butonu ile hesaplanan tutarlar <strong>veritabanına yazılır</strong> ve faturalandırma yaparken bu kayıtlı tutarlar kullanılır. Toplu güncelleme için <code class="text-xs bg-gray-100 px-1 rounded">pending-billings:refresh-amounts</code> komutu da kullanılabilir.
        </p>
        @if ($usdEfektifSelling === null)
            <p class="text-sm text-amber-700 mt-1">
                Bugün için USD kuru tanımlı değil. Kurlar sayfasından &quot;Güncel kurları çek&quot; ile çekebilir veya elle girebilirsiniz; aksi halde beklenen TL hesaplanmaz.
            </p>
        @endif
    </div>

    <div class="mb-4 border-b border-gray-200">
        <nav class="flex gap-1" aria-label="Sipariş durumu sekmeleri">
            <a href="{{ route('pending-billings.index', ['status' => 'pending']) }}" class="px-4 py-3 text-sm font-medium rounded-t-lg border-b-2 transition-colors {{ ($currentStatus ?? 'pending') === 'pending' ? 'border-slate-600 text-slate-800 bg-white -mb-px' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">Beklemede</a>
            <a href="{{ route('pending-billings.index', ['status' => 'invoiced']) }}" class="px-4 py-3 text-sm font-medium rounded-t-lg border-b-2 transition-colors {{ ($currentStatus ?? '') === 'invoiced' ? 'border-slate-600 text-slate-800 bg-white -mb-px' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">Faturalandı</a>
            <a href="{{ route('pending-billings.index', ['status' => 'cancelled']) }}" class="px-4 py-3 text-sm font-medium rounded-t-lg border-b-2 transition-colors {{ ($currentStatus ?? '') === 'cancelled' ? 'border-slate-600 text-slate-800 bg-white -mb-px' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">İptal</a>
        </nav>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        @if (($currentStatus ?? '') === 'pending')
            <form action="{{ route('sales-invoices.create') }}" method="GET" id="faturalandir-form" onsubmit="var c = document.querySelectorAll('.pending-row-checkbox:checked'); if (c.length === 0) { alert('En az bir sipariş seçin.'); return false; } return true;">
        @endif
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        @if (($currentStatus ?? '') === 'pending')
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <label class="inline-flex items-center gap-1 cursor-pointer">
                                    <input type="checkbox" id="select-all-pending" class="rounded border-gray-300 text-slate-600 focus:ring-slate-500" aria-label="Tümünü seç">
                                    <span>Seç</span>
                                </label>
                            </th>
                        @endif
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Müşteri</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sözleşme no / Ürün</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dönem</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Alış (TL)</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Satış (TL)</th>
                        @if (($currentStatus ?? '') === 'pending')
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alış faturası</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Önceki dönemler farkı</th>
                        @endif
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Havuza düşme</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">İşlem</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($pendingBillings as $pb)
                        @php
                            $sub = $pb->subscription;
                            $alisKdvHaric = isset($pb->expected_alis_tl) && $pb->expected_alis_tl !== '' ? (float) $pb->expected_alis_tl : null;
                            $satisTl = isset($pb->expected_satis_tl) && $pb->expected_satis_tl !== '' ? (float) $pb->expected_satis_tl : null;
                            $usdAlis = $sub->usd_birim_alis !== null && $sub->usd_birim_alis !== '' ? (float) $sub->usd_birim_alis : null;
                            $usdSatis = $sub->usd_birim_satis !== null && $sub->usd_birim_satis !== '' ? (float) $sub->usd_birim_satis : null;
                            if ($alisKdvHaric === null && $usdEfektifSelling !== null && $usdAlis !== null) {
                                $qty = (int) $sub->quantity;
                                $alisKdvHaric = $usdAlis * $qty * $usdEfektifSelling;
                                if ($usdAlis > 0 && $usdSatis !== null) {
                                    $satisTl = $alisKdvHaric * ($usdSatis / $usdAlis);
                                }
                            }
                            $actualAlis = isset($pb->actual_alis_tl) && $pb->actual_alis_tl !== '' && $pb->actual_alis_tl !== null ? (float) $pb->actual_alis_tl : null;
                            $actualSatis = isset($pb->actual_satis_tl) && $pb->actual_satis_tl !== '' && $pb->actual_satis_tl !== null ? (float) $pb->actual_satis_tl : null;
                            $donemLabel = $pb->period_start ? $pb->period_start->locale('tr')->translatedFormat('F Y') : '—';
                            $accumulatedFark = $accumulatedFarkBySubscription[$pb->subscription_id ?? 0] ?? 0;
                            $showKurGuncelle = $pb->status === 'pending' && $actualAlis === null;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            @if (($currentStatus ?? '') === 'pending')
                                <td class="px-4 py-3">
                                    <input type="checkbox" name="pending_billing_ids[]" value="{{ $pb->id }}" form="faturalandir-form" class="pending-row-checkbox rounded border-gray-300 text-slate-600 focus:ring-slate-500">
                                </td>
                            @endif
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 font-mono">{{ $pb->id }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ $sub->customerCari?->short_name ?: $sub->customerCari?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="font-medium text-gray-900">{{ $sub->sozlesme_no }}</span>
                                @if ($sub->product)
                                    <br><span class="text-gray-500">{{ $sub->product->name }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">{{ $donemLabel }}</td>
                            <td class="px-4 py-3 text-sm text-right text-gray-800">
                                @if ($actualAlis !== null)
                                    <span class="block text-gray-500">Beklenen 0,00 ₺</span>
                                    <span class="block font-medium">Kesinleşen {{ number_format($actualAlis, 2, ',', '.') }} ₺</span>
                                @elseif ($alisKdvHaric !== null)
                                    <span class="block font-medium">Beklenen {{ number_format($alisKdvHaric, 2, ',', '.') }} ₺</span>
                                    @if ($pb->amounts_updated_at)
                                        <span class="block text-xs text-gray-400">{{ $pb->amounts_updated_at->format('d.m.Y') }} kuru</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-right text-gray-800">
                                @if ($actualAlis !== null && $actualSatis === null)
                                    {{-- Alış faturası girilmiş, henüz faturalanmadı: satış kesinleşmedi, sadece beklenen (gerçek alıştan hesaplanan) --}}
                                    @if ($satisTl !== null)
                                        <span class="block font-medium">Beklenen {{ number_format($satisTl, 2, ',', '.') }} ₺</span>
                                    @else
                                        —
                                    @endif
                                @elseif ($actualSatis !== null)
                                    @if ($satisTl !== null)
                                        <span class="block text-gray-500">Beklenen {{ number_format($satisTl, 2, ',', '.') }} ₺</span>
                                    @endif
                                    <span class="block font-medium">Kesinleşen {{ number_format($actualSatis, 2, ',', '.') }} ₺</span>
                                @elseif ($satisTl !== null)
                                    <span class="block font-medium">Beklenen {{ number_format($satisTl, 2, ',', '.') }} ₺</span>
                                @else
                                    —
                                @endif
                            </td>
                            @if (($currentStatus ?? '') === 'pending')
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    @if ($actualAlis !== null && ($pb->supplier_invoice_number || $pb->supplier_invoice_date))
                                        @if ($pb->supplier_invoice_number)
                                            <span class="font-medium text-gray-900">{{ $pb->supplier_invoice_number }}</span>
                                        @endif
                                        @if ($pb->supplier_invoice_date)
                                            @if ($pb->supplier_invoice_number)
                                                <br>
                                            @endif
                                            <span class="text-gray-500">{{ $pb->supplier_invoice_date->format('d.m.Y') }}</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right">
                                    @if ($accumulatedFark != 0)
                                        <span class="{{ $accumulatedFark > 0 ? 'text-amber-700' : 'text-slate-600' }} font-medium">{{ number_format($accumulatedFark, 2, ',', '.') }} ₺</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            @endif
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                {{ $pb->created_at->format('d.m.Y H:i') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm space-x-2">
                                @if ($showKurGuncelle)
                                    <form action="{{ route('pending-billings.refresh-amounts', $pb) }}" method="POST" class="inline" onsubmit="return confirm('Bu kayıt için beklenen alış/satış tutarları güncel kur ile güncellenecek. Devam?');">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ request('status') }}">
                                        <button type="submit" class="text-amber-600 hover:text-amber-800 font-medium">Kur ile hesapla</button>
                                    </form>
                                    <span class="text-gray-300">|</span>
                                @endif
                                @if ($pb->status !== 'cancelled')
                                    <a href="{{ route('pending-billings.supplier-invoice', [$pb, 'status' => $currentStatus ?? 'pending']) }}" class="text-slate-600 hover:text-slate-900 font-medium">{{ $actualAlis !== null ? 'Alış düzelt' : 'Alış gir' }}</a>
                                    <span class="text-gray-300">|</span>
                                @endif
                                <a href="{{ route('subscriptions.show', $pb->subscription) }}" class="text-slate-600 hover:text-slate-900 font-medium">Abonelik</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ($currentStatus ?? '') === 'pending' ? 11 : 8 }}" class="px-4 py-8 text-center text-sm text-gray-500">
                                Kayıt yok. Yeni abonelik eklediğinizde ilk dönem veya günlük komut çalıştığında burada görünecektir.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (($currentStatus ?? '') === 'pending')
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 flex flex-wrap items-center gap-3">
                <button type="submit" form="faturalandir-form" class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded-lg font-semibold text-sm hover:bg-slate-700">
                    Seçilenleri faturaya geçir
                </button>
                <span class="text-sm text-gray-500">Faturalandırmak istediğiniz siparişleri işaretleyip butona tıklayın.</span>
            </div>
            </form>
        @endif
        @if ($pendingBillings->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                {{ $pendingBillings->links() }}
            </div>
        @endif
    </div>
    @if (($currentStatus ?? '') === 'pending')
        <script>
            document.getElementById('select-all-pending')?.addEventListener('change', function () {
                document.querySelectorAll('.pending-row-checkbox').forEach(function (cb) { cb.checked = this.checked; }, this);
            });
        </script>
    @endif
</x-app-layout>
