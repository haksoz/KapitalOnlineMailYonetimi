<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 min-w-0">
            <h1 class="text-lg sm:text-xl font-semibold text-gray-800 truncate">Kur Yönetimi</h1>
            <form action="{{ route('exchange-rates.fetch-latest') }}" method="POST" class="flex items-center gap-2">
                @csrf
                <button type="submit"
                        class="inline-flex items-center justify-center min-h-[40px] px-4 py-2 bg-slate-800 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition">
                    Merkez Bankasından Güncel Kur Çek
                </button>
            </form>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="mb-4 space-y-1">
        <p class="text-sm text-gray-600">
            Bu sayfada sistemde kullanılacak <span class="font-semibold">USD</span> ve <span class="font-semibold">EUR</span> kurları tutulur.
            İstersen Merkez Bankasından çekebilir ya da elle güncelleyebilirsin.
        </p>
        @if ($lastUpdatedDate)
            <p class="text-xs text-gray-500">
                Son güncelleme tarihi: {{ $lastUpdatedDate->format('d.m.Y') }}
            </p>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kod</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ad</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Döviz Alış</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Döviz Satış</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">İşlem</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @if ($usd)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $usd->currency_code }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $usd->name ?? '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">
                                {{ $usd->forex_buying !== null ? number_format($usd->forex_buying, 4, ',', '.') : '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">
                                {{ $usd->forex_selling !== null ? number_format($usd->forex_selling, 4, ',', '.') : '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right">
                                <a href="{{ route('exchange-rates.edit', $usd) }}" class="text-slate-600 hover:text-slate-900 font-medium">Düzenle</a>
                            </td>
                        </tr>
                    @endif

                    @if ($eur)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $eur->currency_code }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $eur->name ?? '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">
                                {{ $eur->forex_buying !== null ? number_format($eur->forex_buying, 4, ',', '.') : '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">
                                {{ $eur->forex_selling !== null ? number_format($eur->forex_selling, 4, ',', '.') : '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right">
                                <a href="{{ route('exchange-rates.edit', $eur) }}" class="text-slate-600 hover:text-slate-900 font-medium">Düzenle</a>
                            </td>
                        </tr>
                    @endif

                    @if (! $usd && ! $eur)
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
                                Henüz USD ve EUR için kur kaydı yok. Yukarıdaki butonla Merkez Bankasından çekebilir veya elle ekleyebilirsin.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>

