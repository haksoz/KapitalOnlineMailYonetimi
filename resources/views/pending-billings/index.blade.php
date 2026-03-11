<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 min-w-0">
            <h1 class="text-lg sm:text-xl font-semibold text-gray-800 truncate">Siparişler</h1>
            <a href="{{ route('pending-billings.supplier-invoice-xml', ['status' => $currentStatus ?? 'pending']) }}" class="inline-flex items-center justify-center min-h-[40px] px-4 py-2 bg-slate-600 text-white rounded-lg font-semibold text-sm hover:bg-slate-700 focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                Alış faturası XML gir
            </a>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="mb-4">
        <p class="text-sm text-gray-600">
            Önce öde sonra kullan mantığına göre dönem başı gelen siparişler burada listelenir. Yeni abonelik oluşturulunca ilk dönem, sonraki dönemler ise günlük <code class="text-xs bg-gray-100 px-1 rounded">pending-billings:enqueue</code> komutu ile eklenir.
            Beklenen TL tutarlar işlem günü <strong>efektif satış</strong> kuru (USD) ile hesaplanır. Tabloda gördüğünüz tutarlar kayıtlı değilse anlık kur ile gösterilir; <strong>&quot;Hesapla&quot;</strong> butonu ile hesaplanan tutarlar <strong>veritabanına yazılır</strong> ve faturalandırma yaparken bu kayıtlı tutarlar kullanılır. Toplu güncelleme için <code class="text-xs bg-gray-100 px-1 rounded">pending-billings:refresh-amounts</code> komutu da kullanılabilir.
        </p>
        @if ($usdEfektifSelling === null)
            <p class="text-sm text-amber-700 mt-1">
                Bugün için USD kuru tanımlı değil. Kurlar sayfasından &quot;Güncel kurları çek&quot; ile çekebilir veya elle girebilirsiniz; aksi halde beklenen TL hesaplanmaz.
            </p>
        @endif
    </div>

    <div class="mb-4 border-b border-gray-200">
        <nav class="flex gap-1" aria-label="Sipariş durumu sekmeleri">
            @php
                $queryParams = request()->only(['customer_cari_id', 'period_year', 'period_month', 'per_page']);
            @endphp
            <a href="{{ route('pending-billings.index', array_merge($queryParams, ['status' => 'pending'])) }}" class="px-4 py-3 text-sm font-medium rounded-t-lg border-b-2 transition-colors {{ ($currentStatus ?? 'pending') === 'pending' ? 'border-slate-600 text-slate-800 bg-white -mb-px' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">Beklemede</a>
            <a href="{{ route('pending-billings.index', array_merge($queryParams, ['status' => 'invoiced'])) }}" class="px-4 py-3 text-sm font-medium rounded-t-lg border-b-2 transition-colors {{ ($currentStatus ?? '') === 'invoiced' ? 'border-slate-600 text-slate-800 bg-white -mb-px' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">Faturalandı</a>
            <a href="{{ route('pending-billings.index', array_merge($queryParams, ['status' => 'cancelled'])) }}" class="px-4 py-3 text-sm font-medium rounded-t-lg border-b-2 transition-colors {{ ($currentStatus ?? '') === 'cancelled' ? 'border-slate-600 text-slate-800 bg-white -mb-px' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">İptal</a>
        </nav>
    </div>

    <form method="GET" action="{{ route('pending-billings.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
        <input type="hidden" name="status" value="{{ $currentStatus ?? 'pending' }}">
        <div class="min-w-[180px]">
            <label for="customer_cari_id" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Müşteri</label>
            <select id="customer_cari_id" name="customer_cari_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 text-sm">
                <option value="">— Tümü —</option>
                @foreach ($caris ?? [] as $c)
                    <option value="{{ $c->id }}" @selected(request('customer_cari_id') == $c->id)>{{ $c->short_name ?: $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[120px]">
            <label for="period_year" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Dönem (yıl)</label>
            <select id="period_year" name="period_year" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 text-sm">
                <option value="">— Tümü —</option>
                @for ($y = now()->year; $y >= now()->year - 3; $y--)
                    <option value="{{ $y }}" @selected(request('period_year') === (string) $y)>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <div class="min-w-[140px]">
            <label for="period_month" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Dönem (ay)</label>
            <select id="period_month" name="period_month" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 text-sm">
                <option value="">— Tümü —</option>
                @for ($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected(request('period_month') === (string) $m)>{{ \Carbon\Carbon::createFromDate(2000, $m, 1)->translatedFormat('F') }}</option>
                @endfor
            </select>
        </div>
        <button type="submit" class="inline-flex items-center justify-center min-h-[38px] px-4 py-2 bg-slate-800 text-white text-sm font-medium rounded-md hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">Filtrele</button>
        @if (request()->filled('customer_cari_id') || request()->filled('period_year') || request()->filled('period_month'))
            <a href="{{ route('pending-billings.index', array_merge(request()->only('status', 'per_page'))) }}" class="inline-flex items-center min-h-[38px] px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Filtreyi temizle</a>
        @endif
    </form>

    <style>
        .pb-eligible-row {
            background-color: #bbf7d0 !important; /* yeşil-200, biraz daha koyu */
        }
    </style>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        @if (($currentStatus ?? '') === 'pending')
            {{-- Seçilen siparişleri faturalandırma onay ekranına (sales-invoices.create) gönder --}}
            <form action="{{ route('sales-invoices.create') }}" method="GET" id="faturalandir-form">
        @endif
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        @if (($currentStatus ?? '') === 'pending')
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="select-all-pending" class="rounded border-gray-300 text-slate-600 focus:ring-slate-500" aria-label="Tümünü seç">
                                </label>
                            </th>
                        @endif
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Müşteri</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sözleşme no / Ürün</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Adet</th>
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
                                if ($satisTl === null && $usdAlis > 0 && $usdSatis !== null) {
                                    $satisTl = $alisKdvHaric * ($usdSatis / $usdAlis);
                                }
                            }
                            $actualAlis = isset($pb->actual_alis_tl) && $pb->actual_alis_tl !== '' && $pb->actual_alis_tl !== null ? (float) $pb->actual_alis_tl : null;
                            // Faturalandıysa kesinleşen satışı fatura satırından al (fark eklendiyse orada doğru tutar var)
                            $actualSatis = null;
                            if ($pb->salesInvoiceLine && $pb->salesInvoiceLine->line_amount_tl !== null && $pb->salesInvoiceLine->line_amount_tl !== '') {
                                $actualSatis = (float) $pb->salesInvoiceLine->line_amount_tl;
                            } elseif (isset($pb->actual_satis_tl) && $pb->actual_satis_tl !== '' && $pb->actual_satis_tl !== null) {
                                $actualSatis = (float) $pb->actual_satis_tl;
                            }
                            if ($actualAlis !== null && $actualSatis === null && $usdAlis !== null && $usdAlis > 0 && $usdSatis !== null) {
                                $satisTl = $actualAlis * ($usdSatis / $usdAlis);
                            }
                            $donemLabel = $pb->period_start ? $pb->period_start->locale('tr')->translatedFormat('F Y') : '—';
                            $accumulatedFark = $accumulatedFarkBySubscription[$pb->subscription_id ?? 0] ?? 0;
                            $showKurGuncelle = $pb->status === 'pending' && $actualAlis === null;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            @if (($currentStatus ?? '') === 'pending')
                                <td class="px-4 py-3">
                                    <input
                                        type="checkbox"
                                        name="pending_billing_ids[]"
                                        value="{{ $pb->id }}"
                                        form="faturalandir-form"
                                        class="pending-row-checkbox rounded border-gray-300 text-slate-600 focus:ring-slate-500"
                                        data-customer-id="{{ $sub->customer_cari_id }}"
                                        data-period-year="{{ $pb->period_start?->year }}"
                                        data-period-month="{{ $pb->period_start?->month }}"
                                    >
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
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">
                                {{ $sub->quantity ?? 1 }}
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
                                    @if (($currentStatus ?? '') === 'invoiced' && $actualAlis !== null && ($satisTl === null || $satisTl == 0))
                                        {{-- Faturalandı + alış kesinleşmiş, beklenen satış yok (alış fat. önce girilmiş sipariş) --}}
                                        <span class="block text-gray-500">Beklenen 0,00 ₺</span>
                                    @elseif ($satisTl !== null)
                                        <span class="block text-gray-500">Beklenen {{ number_format($satisTl, 2, ',', '.') }} ₺</span>
                                    @endif
                                    <span class="block font-medium">Kesinleşen {{ number_format($actualSatis, 2, ',', '.') }} ₺</span>
                                    @if(($currentStatus ?? '') === 'invoiced' && auth()->user()?->isAdmin())
                                        <a href="{{ route('admin.pending-billings.edit-sale', $pb) }}" class="mt-1 inline-block text-xs text-slate-600 hover:text-slate-900">
                                            Kesinleşen satışı düzelt
                                        </a>
                                    @endif
                                @elseif ($satisTl !== null)
                                    <span class="block font-medium">Beklenen {{ number_format($satisTl, 2, ',', '.') }} ₺</span>
                                @else
                                    —
                                @endif
                            </td>
                            @if (($currentStatus ?? '') === 'pending')
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    @if ($pb->supplier_invoice_number || $pb->supplier_invoice_date)
                                        @if ($pb->supplier_invoice_number)
                                            <span class="font-medium text-gray-900">{{ $pb->supplier_invoice_number }}</span>
                                        @endif
                                        @if ($pb->supplier_invoice_date)
                                            @if ($pb->supplier_invoice_number)
                                                <br>
                                            @endif
                                            <span class="text-gray-500">{{ $pb->supplier_invoice_date->format('d.m.Y') }}</span>
                                        @endif
                                        <button
                                            type="submit"
                                            form="clear-supplier-{{ $pb->id }}"
                                            class="mt-1 text-xs text-amber-700 hover:text-amber-900 font-medium"
                                            onclick="return confirm('Bu siparişten alış faturası bilgileri kaldırılacak. Devam?');"
                                        >
                                            Alışı geri al
                                        </button>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right">
                                    @if ($accumulatedFark != 0)
                                        <span class="{{ $accumulatedFark > 0 ? 'text-red-600' : 'text-slate-600' }} font-medium">{{ number_format($accumulatedFark, 2, ',', '.') }} ₺</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            @endif
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                {{ $pb->created_at->format('d.m.Y') }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <div class="flex flex-wrap justify-end items-center gap-2">
                                    @if ($showKurGuncelle)
                                        <form action="{{ route('pending-billings.refresh-amounts', $pb) }}" method="POST" onsubmit="return confirm('Bu kayıt için beklenen alış/satış tutarları güncel kur ile güncellenecek. Devam?');" class="inline">
                                            @csrf
                                            <input type="hidden" name="status" value="{{ request('status') }}">
                                            <button type="submit" class="text-amber-600 hover:text-amber-800 font-medium whitespace-nowrap">Hesapla</button>
                                        </form>
                                    @endif
                                    @if ($pb->status !== 'cancelled' && $actualAlis === null)
                                        <a href="{{ route('pending-billings.supplier-invoice', [$pb, 'status' => $currentStatus ?? 'pending']) }}" class="text-slate-600 hover:text-slate-900 font-medium whitespace-nowrap">Alış gir</a>
                                    @endif
                                    <a href="{{ route('subscriptions.show', $pb->subscription) }}" class="text-slate-600 hover:text-slate-900 font-medium whitespace-nowrap">Abonelik</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ($currentStatus ?? '') === 'pending' ? 12 : 9 }}" class="px-4 py-8 text-center text-sm text-gray-500">
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

            @foreach ($pendingBillings as $pb)
                @if ($pb->supplier_invoice_number || $pb->supplier_invoice_date)
                    <form id="clear-supplier-{{ $pb->id }}" action="{{ route('pending-billings.clear-supplier-invoice', $pb) }}" method="POST" class="hidden">
                        @csrf
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    </form>
                @endif
            @endforeach
        @endif
        @php
            $total = $pendingBillings->total();
            $currentPerPage = (int) request('per_page', 20);
            $pageQuery = request()->only(['status', 'customer_cari_id', 'period_year', 'period_month']);
        @endphp
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-sm text-gray-600">Toplam <strong>{{ $total }}</strong> kayıt.</span>
                <span class="text-sm text-gray-500">Sayfa başına:</span>
                @foreach ([15, 20, 25, 50, 100] as $n)
                    @if ($n === $currentPerPage)
                        <span class="inline-flex items-center px-2 py-1 text-sm font-medium rounded bg-slate-200 text-slate-800">{{ $n }}</span>
                    @else
                        <a href="{{ route('pending-billings.index', array_merge($pageQuery, ['per_page' => $n, 'page' => 1])) }}" class="inline-flex items-center px-2 py-1 text-sm text-slate-600 hover:bg-slate-100 rounded">{{ $n }}</a>
                    @endif
                @endforeach
            </div>
            @if ($pendingBillings->hasPages())
                <div class="flex items-center">
                    {{ $pendingBillings->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
    @if (($currentStatus ?? '') === 'pending')
        <div id="pending-lock-banner" class="mx-4 mt-3 mb-1 hidden px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700">
            Seçim kilitlendi:
            <span data-lock-text class="font-medium"></span>.
            Farklı müşteri veya döneme ait siparişleri seçemezsiniz.
        </div>
        <script>
            (function () {
                const selectAll = document.getElementById('select-all-pending');
                const checkboxes = Array.from(document.querySelectorAll('.pending-row-checkbox'));
                const banner = document.getElementById('pending-lock-banner');
                const lockTextEl = banner ? banner.querySelector('[data-lock-text]') : null;

                let lockedCustomerId = null;
                let lockedYear = null;
                let lockedMonth = null;

                const form = document.getElementById('faturalandir-form');

                function updateRowHighlight() {
                    checkboxes.forEach(function (cb) {
                        const row = cb.closest('tr');
                        if (!row) return;
                        row.classList.remove('pb-eligible-row');
                        if (!lockedCustomerId) return;
                        const cId = cb.dataset.customerId || null;
                        const y = cb.dataset.periodYear || null;
                        const m = cb.dataset.periodMonth || null;
                        if (cId === lockedCustomerId && y === lockedYear && m === lockedMonth) {
                            row.classList.add('pb-eligible-row');
                        }
                    });
                }

                function resetLockIfNoSelection() {
                    const anyChecked = checkboxes.some(cb => cb.checked);
                    if (!anyChecked) {
                        lockedCustomerId = lockedYear = lockedMonth = null;
                        if (banner) {
                            banner.classList.add('hidden');
                            if (lockTextEl) lockTextEl.textContent = '';
                        }
                        updateRowHighlight();
                    }
                }

                function applyLockFromCheckbox(cb) {
                    const cId = cb.dataset.customerId || null;
                    const y = cb.dataset.periodYear || null;
                    const m = cb.dataset.periodMonth || null;

                    if (!lockedCustomerId) {
                        lockedCustomerId = cId;
                        lockedYear = y;
                        lockedMonth = m;
                        if (banner && lockTextEl) {
                            const periodLabel = (y && m) ? (y + '-' + String(m).padStart(2, '0')) : 'dönem bilgisi yok';
                            lockTextEl.textContent = 'Müşteri ID ' + (cId || '—') + ' — Dönem ' + periodLabel;
                            banner.classList.remove('hidden');
                        }
                        updateRowHighlight();
                        return true;
                    }

                    if (cId !== lockedCustomerId || y !== lockedYear || m !== lockedMonth) {
                        return false;
                    }

                    return true;
                }

                checkboxes.forEach(function (cb) {
                    cb.addEventListener('change', function () {
                        if (this.checked) {
                            if (!applyLockFromCheckbox(this)) {
                                alert('Sadece aynı müşteri ve aynı döneme ait siparişleri seçebilirsiniz.');
                                this.checked = false;
                            }
                        } else {
                            resetLockIfNoSelection();
                        }
                        updateRowHighlight();
                    });
                });

                if (selectAll) {
                    selectAll.addEventListener('change', function () {
                        if (this.checked) {
                            // Tüm seçimler kapatılıp ilk geçerli satıra göre kilit belirlenecek
                            lockedCustomerId = lockedYear = lockedMonth = null;
                            if (banner) {
                                banner.classList.add('hidden');
                                if (lockTextEl) lockTextEl.textContent = '';
                            }

                            checkboxes.forEach(function (cb) {
                                if (cb.disabled) {
                                    cb.checked = false;
                                    return;
                                }
                                // İlk uygun seçim kilidi belirler, sonrakiler uyumluysa seçilir
                                if (applyLockFromCheckbox(cb)) {
                                    cb.checked = true;
                                } else {
                                    cb.checked = false;
                                }
                            });
                            updateRowHighlight();
                        } else {
                            checkboxes.forEach(function (cb) { cb.checked = false; });
                            resetLockIfNoSelection();
                        }
                    });
                }
                if (form) {
                    form.addEventListener('submit', function (e) {
                        const anyChecked = checkboxes.some(cb => cb.checked);
                        if (!anyChecked) {
                            alert('En az bir sipariş seçin.');
                            e.preventDefault();
                            return;
                        }
                        if (!lockedCustomerId) {
                            alert('Müşteri ve dönem seçimi geçersiz. Lütfen seçimleri tekrar yapın.');
                            e.preventDefault();
                            return;
                        }
                    });
                }
                // İlk yüklemede (sayfa yenileme sonrası) mevcut seçim varsa highlight et
                updateRowHighlight();
            })();
        </script>
    @endif
</x-app-layout>
