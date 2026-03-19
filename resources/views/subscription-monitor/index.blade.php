@php
    use Carbon\Carbon;
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Abone Takip" />

    <p class="mt-0.5 text-sm text-gray-500">
        Seçilen ay için carilerin abonelik, sipariş ve fatura durumunu tek ekranda izleyin.
    </p>

    <form method="GET" action="{{ route('subscription-monitor.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
        @php
            $currentYear = (int) now()->year;
            $years = range($currentYear - 2, $currentYear + 2);
        @endphp
        <div class="min-w-[140px]">
            <x-input-label for="month" value="Ay" />
            <select id="month" name="month" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                @for ($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($m === (int) $month)>{{ Carbon::create(null, $m, 1)->locale('tr')->translatedFormat('F') }}</option>
                @endfor
            </select>
        </div>
        <div class="min-w-[100px]">
            <x-input-label for="year" value="Yıl" />
            <select id="year" name="year" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                @foreach ($years as $y)
                    <option value="{{ $y }}" @selected($y === (int) $year)>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[200px]">
            <x-input-label for="status" value="Durum" />
            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                <option value="" @selected(($statusFilter ?? '') === '')>— Tümü —</option>
                <option value="tamamlandi" @selected(($statusFilter ?? '') === 'tamamlandi')>Tamamlandı</option>
                <option value="eksik" @selected(($statusFilter ?? '') === 'eksik')>Eksik sipariş var</option>
                <option value="faturalanmamis" @selected(($statusFilter ?? '') === 'faturalanmamis')>Faturalandırılmamış siparişler var</option>
            </select>
        </div>
        <div class="pb-1">
            <x-primary-button type="submit">Filtrele</x-primary-button>
        </div>
    </form>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">
                        Cari Bazında Durum
                    </h3>
                    <p class="mt-0.5 text-xs text-gray-500">
                        {{ $monthStart->locale('tr')->translatedFormat('F Y') }} için özet.
                    </p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="w-10 px-2 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"></th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Cari</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Abonelik</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Sipariş</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" title="Alış faturası atanmış sipariş sayısı">Alış fat.</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Satış Fat.</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">E-Fatura</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Durum</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">İşlem</th>
                        </tr>
                    </thead>
                    @forelse ($customerSummaries as $row)
                        <tbody x-data="{ open: false }" class="bg-white divide-y divide-gray-100 border-b border-gray-200">
                            <tr @click="open = !open" class="cursor-pointer hover:bg-gray-50 transition-colors">
                                <td class="w-10 px-2 py-2 whitespace-nowrap align-middle">
                                    <span class="inline-flex text-gray-500 transition-transform" :class="{ 'rotate-90': open }">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <span class="font-medium text-gray-900">
                                        {{ $row['customer']?->short_name ?: $row['customer']?->name ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-gray-900">
                                    {{ $row['subscription_count'] }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-gray-900">
                                    {{ $row['pending_count'] }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-gray-900">
                                    {{ $row['supplier_invoiced_count'] }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-gray-900">
                                    {{ $row['billed_count'] }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-gray-900">
                                    {{ $row['invoiced_count'] }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    @php
                                        $status = $row['status'];
                                        $classes = match ($status) {
                                            'Eksik sipariş var' => 'bg-amber-100 text-amber-800 ring-amber-200',
                                            'Faturalandırılmamış siparişler var' => 'bg-indigo-100 text-indigo-800 ring-indigo-200',
                                            default => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset {{ $classes }}">
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap" @click.stop>
                                    @if ($row['status'] === 'Eksik sipariş var' && $row['customer']?->id)
                                        <form action="{{ route('subscription-monitor.enqueue-missing-for-cari') }}" method="POST" class="inline" onsubmit="return confirm('Seçilen ay ({{ $monthStart->locale('tr')->translatedFormat('F Y') }}) için bu carinin eksik dönem siparişleri oluşturulacak ve gerekiyorsa ilgili aboneliklerin bitiş tarihleri bu ay sonuna kadar güncellenecek. Devam etmek istiyor musunuz?');">
                                            @csrf
                                            <input type="hidden" name="customer_cari_id" value="{{ $row['customer']->id }}">
                                            <input type="hidden" name="year" value="{{ $year }}">
                                            <input type="hidden" name="month" value="{{ $month }}">
                                            @if (!empty($statusFilter))
                                                <input type="hidden" name="status" value="{{ $statusFilter }}">
                                            @endif
                                            <button type="submit" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md text-white bg-slate-600 hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                                                Bu ay için siparişleri oluştur
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                            <tr x-show="open" class="bg-gray-50/80">
                                <td colspan="9" class="px-4 py-3">
                                    <div class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">
                                        {{ $monthStart->locale('tr')->translatedFormat('F Y') }} — Abonelik / dönem durumları
                                    </div>
                                    <table class="min-w-full text-sm border border-gray-200 rounded-lg overflow-hidden bg-white">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Abonelik</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Adet</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">USD Satış</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Sipariş</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Bitiş tarihi</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Alış fat.</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Satış fat.</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">E-Fatura</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($row['details'] ?? [] as $d)
                                                @php
                                                    $sub = $d['subscription'];
                                                    $pb = $d['pending_billing'];
                                                    $productLabel = $sub->product?->name ?? 'Abonelik #' . $sub->id;
                                                    $qty = (int) $sub->quantity;
                                                    $usdBirim = $sub->usd_birim_satis ? (float) $sub->usd_birim_satis : 0;
                                                    $expectedUsd = $qty * $usdBirim;
                                                    $kesinlesenTl = $pb && $pb->actual_satis_tl !== null ? (float) $pb->actual_satis_tl : null;
                                                @endphp
                                                <tr class="border-t border-gray-100">
                                                    <td class="px-3 py-2 text-gray-900">{{ $productLabel }}</td>
                                                    <td class="px-3 py-2 text-gray-900">{{ $qty }}</td>
                                                    <td class="px-3 py-2 text-gray-900">
                                                        <span>{{ number_format($expectedUsd, 2, ',', '.') }} USD</span>
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        @if ($pb)
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">Var</span>
                                                        @else
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">Yok</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2 text-gray-600">
                                                        @if ($sub->bitis_tarihi)
                                                            {{ $sub->bitis_tarihi->locale('tr')->translatedFormat('d M Y') }}
                                                        @else
                                                            <span class="text-gray-400">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        @if ($d['supplier_invoiced'])
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">Evet</span>
                                                        @else
                                                            <span class="text-gray-500">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        @if ($d['sales_fat_invoiced'] ?? false)
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">Evet</span>
                                                        @else
                                                            <span class="text-gray-500">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        @if ($d['sales_invoiced'])
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">Evet</span>
                                                        @else
                                                            <span class="text-gray-500">—</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    @empty
                        <tbody>
                            <tr>
                                <td colspan="9" class="px-4 py-6 text-center text-sm text-gray-500">
                                    Seçilen ay için aktif aboneliği olan cari bulunamadı.
                                </td>
                            </tr>
                        </tbody>
                    @endforelse
                </table>
            </div>
            @if ($customerSummaries->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                    {{ $customerSummaries->withQueryString()->links() }}
                </div>
            @endif
    </div>

    <div class="mt-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">Özet</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Cari Sayısı</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $totals['customer_count'] }}</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Abonelik Sayısı</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $totals['subscription_count'] }}</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Beklenen Sipariş</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $totals['expected_periods'] }}</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Oluşan / Alış fat. gelen</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900">
                    {{ $totals['pending_count'] }} <span class="text-sm font-normal text-gray-500">/ {{ $totals['supplier_invoiced_count'] }}</span>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Satış Faturaya Bağlı</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $totals['invoiced_count'] }}</div>
            </div>
        </div>
    </div>
</x-app-layout>

