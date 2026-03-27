<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Cari Hesap Dökümü">
        <x-slot name="left">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
        <x-slot name="right">
            <a href="{{ route('admin.reports.cari-ledger.export', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-slate-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition">
                Excel indir
            </a>
        </x-slot>
    </x-page-toolbar>

    <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 mb-4">
        <form method="GET" action="{{ route('admin.reports.cari-ledger') }}" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <div>
                <x-input-label for="cari_id" value="Müşteri" />
                <select id="cari_id" name="cari_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Tümü</option>
                    @foreach ($caris as $cari)
                        <option value="{{ $cari->id }}" @selected((string) ($filters['cari_id'] ?? '') === (string) $cari->id)>
                            {{ $cari->short_name ?: $cari->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @php
                $hasPeriodYear = request()->filled('period_year');
                $hasPeriodMonth = request()->filled('period_month');
                $hasPeriod = $hasPeriodYear || $hasPeriodMonth;
            @endphp

            @if (! $hasPeriod)
                <div>
                    <x-input-label for="from" value="Başlangıç" />
                    <x-text-input id="from" name="from" type="date" class="mt-1 block w-full" :value="$filters['from'] ?? ''" />
                </div>
                <div>
                    <x-input-label for="to" value="Bitiş" />
                    <x-text-input id="to" name="to" type="date" class="mt-1 block w-full" :value="$filters['to'] ?? ''" />
                </div>
            @endif

            <div>
                <x-input-label for="period_year" value="Müşteri Dönem (yıl)" />
                <select id="period_year" name="period_year" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">— Tümü —</option>
                    @for ($y = now()->year; $y >= now()->year - 3; $y--)
                        <option value="{{ $y }}" @selected((string) ($filters['period_year'] ?? '') === (string) $y)>
                            {{ $y }}
                        </option>
                    @endfor
                </select>
            </div>
            <div>
                <x-input-label for="period_month" value="Müşteri Dönem (ay)" />
                <select id="period_month" name="period_month" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">— Tümü —</option>
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected((string) ($filters['period_month'] ?? '') === (string) $m)>
                            {{ \Carbon\Carbon::createFromDate(2000, $m, 1)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
            </div>
            <div>
                <x-input-label for="movement_type" value="Tip" />
                <select id="movement_type" name="movement_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">— Tümü —</option>
                    <option value="alis" @selected(($filters['movement_type'] ?? '') === 'alis')>Alış</option>
                    <option value="satis" @selected(($filters['movement_type'] ?? '') === 'satis')>Satış</option>
                </select>
            </div>
            <div>
                <x-input-label for="product_id" value="Ürün" />
                <select id="product_id" name="product_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">— Tümü —</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected((string) ($filters['product_id'] ?? '') === (string) $product->id)>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="contract_no" value="Sözleşme No" />
                <select id="contract_no" name="contract_no" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">— Tümü —</option>
                    @foreach ($contractNumbers as $contractNumber)
                        <option value="{{ $contractNumber }}" @selected((string) ($filters['contract_no'] ?? '') === (string) $contractNumber)>
                            {{ $contractNumber }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2 lg:col-span-6">
                <button type="submit" name="get_data" value="1" class="w-full inline-flex items-center justify-center min-h-[38px] px-4 py-2 bg-slate-800 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition">
                    Getir
                </button>
            </div>
            <div class="md:col-span-3 lg:col-span-6">
                <p class="text-xs font-medium text-gray-600 mb-1">Durumlar</p>
                <div class="flex flex-wrap gap-3">
                    @foreach ($availableStatuses as $statusKey => $statusLabel)
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="statuses[]" value="{{ $statusKey }}" class="rounded border-gray-300 text-slate-600 focus:ring-slate-500"
                                @checked(in_array($statusKey, $filters['statuses'] ?? [], true))>
                            <span>{{ $statusLabel }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-4" x-data="{
        totals: null,
        loading: false,
        error: null,
        async loadTotals() {
            this.loading = true;
            this.error = null;
            this.totals = null;
            try {
                const url = '{{ route('admin.reports.cari-ledger.totals') }}' + window.location.search;
                const r = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!r.ok) throw new Error('Hesaplanamadı');
                this.totals = await r.json();
            } catch (e) {
                this.error = e.message || 'Bir hata oluştu';
            }
            this.loading = false;
        },
        fmt(n) {
            if (n === null || n === undefined || n === '') return '—';
            const num = Number(n);
            if (Number.isNaN(num)) return '—';
            return num.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }">
        <div class="px-4 py-3 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="text-sm font-semibold text-gray-700">Finansal Özet</h2>
            <button type="button" @click="loadTotals()" :disabled="loading"
                class="inline-flex items-center justify-center min-h-[38px] px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 disabled:opacity-50">
                <span x-show="!loading">Finansal Toplamlar</span>
                <span x-show="loading" x-cloak>Hesaplanıyor…</span>
            </button>
        </div>

        <div x-show="totals || error" x-cloak class="px-4 py-3 border-b border-gray-200 bg-gray-50"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100">
            <p x-show="error" class="text-sm text-red-600" x-text="error"></p>
            <dl x-show="totals && !error" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-sm">
                <div>
                    <dt class="text-gray-500">Beklenen Alış (TL)</dt>
                    <dd class="font-medium text-gray-900" x-text="fmt(totals?.expected_alis_tl)"></dd>
                </div>
                <div>
                    <dt class="text-gray-500">Gerçekleşen Alış (TL)</dt>
                    <dd class="font-medium text-gray-900" x-text="fmt(totals?.actual_alis_tl)"></dd>
                </div>
                <div>
                    <dt class="text-gray-500">Beklenen Satış (TL)</dt>
                    <dd class="font-medium text-gray-900" x-text="fmt(totals?.expected_satis_tl)"></dd>
                </div>
                <div>
                    <dt class="text-gray-500">Gerçekleşen Satış (TL)</dt>
                    <dd class="font-medium text-gray-900" x-text="fmt(totals?.actual_satis_tl)"></dd>
                </div>
                <div>
                    <dt class="text-gray-500">Satış Fark (TL)</dt>
                    <dd class="font-medium" :class="totals && totals.fark_satis_tl > 0 ? 'text-red-600' : 'text-gray-900'" x-text="fmt(totals?.fark_satis_tl)"></dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Cari</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Dönem</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Tip</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Sözleşme</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Ürün</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">İşlem Tarihi</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-600">Beklenen Alış</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-600">Gerçekleşen Alış</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-600">Beklenen Satış</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-600">Gerçekleşen Satış</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-600">Satış Fark</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Fatura No</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($report['rows'] as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2">{{ $row['cari_unvan'] }}</td>
                            <td class="px-3 py-2">{{ $row['donem'] ?? '—' }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $row['hareket_tipi'] === 'alis' ? 'bg-blue-100 text-blue-800' : 'bg-emerald-100 text-emerald-800' }}">
                                    {{ $row['hareket_tipi_label'] }}
                                </span>
                            </td>
                            <td class="px-3 py-2">{{ $row['sozlesme_no'] ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $row['urun_adi'] ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $row['islem_tarihi'] ?? '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format((float) $row['beklenen_alis_tl'], 2, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format((float) $row['gerceklesen_alis_tl'], 2, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format((float) $row['beklenen_satis_tl'], 2, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format((float) $row['gerceklesen_satis_tl'], 2, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format((float) $row['fark_satis_tl'], 2, ',', '.') }}</td>
                            <td class="px-3 py-2">
                                {{ $row['hareket_tipi'] === 'alis' ? ($row['alis_fatura_no'] ?? '—') : ($row['satis_fatura_no'] ?? '—') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-3 py-8 text-center text-gray-500">Filtrelere uygun kayıt bulunamadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-app-layout>
