<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Giderleştirmeler">
    </x-page-toolbar>

    <div class="mb-4">
        <p class="text-sm text-gray-600">
            Faturasız (KDV’siz) satışların giderleştirme kayıtları. Yeni giderleştirme Siparişler sayfasından yapılır.
        </p>
    </div>

    <div class="mb-4">
        <form method="GET" action="{{ route('expense-settlements.index') }}">
            <div class="flex gap-2">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Müşteri adı veya gider no ile ara..."
                    class="flex-1 min-w-0 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-slate-500 focus:border-slate-500"
                >
                <button
                    type="submit"
                    class="px-4 py-2 bg-slate-600 text-white rounded-lg text-sm font-semibold hover:bg-slate-700 focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition"
                >
                    Ara
                </button>
                @if(request('search'))
                    <a
                        href="{{ route('expense-settlements.index') }}"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-300 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition"
                    >
                        Temizle
                    </a>
                @endif
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarih</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Müşteri</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gider No</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Toplam (TL)</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durum</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Satır sayısı</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">İşlem</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($expenseSettlements as $settlement)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                {{ $settlement->settlement_date?->format('d.m.Y') ?? $settlement->created_at->format('d.m.Y') }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                {{ $settlement->customerCari?->short_name ?: $settlement->customerCari?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 font-mono">
                                {{ $settlement->gider_number }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                {{ $settlement->total_amount_tl !== null ? number_format((float) $settlement->total_amount_tl, 2, ',', '.') . ' ₺' : '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                <x-expense-settlement-status-badge :settlement="$settlement" />
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-600">
                                {{ $settlement->lines->count() }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                <div class="inline-flex items-center justify-end gap-0.5">
                                    <x-expense-settlement-status-action :settlement="$settlement" variant="icon" />
                                    <a
                                        href="{{ route('expense-settlements.show', $settlement) }}"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-md text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-1 touch-manipulation"
                                        title="Detay"
                                        aria-label="Detay"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
                                Henüz giderleştirme kaydı yok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($expenseSettlements->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                {{ $expenseSettlements->withQueryString()->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
