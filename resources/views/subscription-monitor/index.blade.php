@php
    use Carbon\Carbon;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">
                    Abone Takip
                </h2>
                <p class="mt-0.5 text-sm text-gray-500">
                    Seçilen ay için carilerin abonelik, sipariş ve fatura durumunu tek ekranda izleyin.
                </p>
            </div>
            <form method="GET" action="{{ route('subscription-monitor.index') }}" class="flex items-center gap-2">
                @php
                    $currentYear = (int) now()->year;
                    $years = range($currentYear - 2, $currentYear + 2);
                @endphp
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <span>Ay:</span>
                    <select name="month" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($m === (int) $month)>{{ Carbon::create(null, $m, 1)->locale('tr')->translatedFormat('F') }}</option>
                        @endfor
                    </select>
                </label>
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <span>Yıl:</span>
                    <select name="year" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        @foreach ($years as $y)
                            <option value="{{ $y }}" @selected($y === (int) $year)>{{ $y }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-slate-700 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                    Göster
                </button>
            </form>
        </div>
    </x-slot>

    <div class="space-y-6">
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
                            <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Cari</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Abonelik</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Beklenen Sipariş</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Oluşan Sipariş</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" title="Alış faturası atanmış sipariş sayısı">Alış fat. gelen</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Satış Faturaya Bağlı</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Durum</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($customerSummaries as $row)
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <span class="font-medium text-gray-900">
                                        {{ $row['customer']?->short_name ?: $row['customer']?->name ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-gray-900">
                                    {{ $row['subscription_count'] }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-gray-900">
                                    {{ $row['expected_periods'] }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-gray-900">
                                    {{ $row['pending_count'] }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-gray-900">
                                    {{ $row['supplier_invoiced_count'] }}
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">
                                    Seçilen ay için aktif aboneliği olan cari bulunamadı.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

