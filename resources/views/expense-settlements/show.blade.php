<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Giderleştirme {{ $expenseSettlement->gider_number }}">
        <x-slot name="left">
            <a href="{{ route('expense-settlements.index') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Giderleştirme bilgileri</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
                <div>
                    <dt class="text-gray-500">Müşteri</dt>
                    <dd class="font-medium text-gray-900">{{ $expenseSettlement->customerCari?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Gider No (GDN)</dt>
                    <dd class="font-medium text-gray-900 font-mono">{{ $expenseSettlement->gider_number }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Giderleştirme tarihi</dt>
                    <dd class="font-medium text-gray-900">{{ $expenseSettlement->settlement_date?->format('d.m.Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Toplam (KDV’siz)</dt>
                    <dd class="font-medium text-gray-900">
                        {{ $expenseSettlement->total_amount_tl !== null ? number_format((float) $expenseSettlement->total_amount_tl, 2, ',', '.') . ' ₺' : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Durum</dt>
                    <dd class="mt-1">
                        <x-expense-settlement-status-badge :settlement="$expenseSettlement" />
                        @if ($expenseSettlement->is_closed && $expenseSettlement->closed_at)
                            <span class="ml-2 text-xs text-gray-500">{{ $expenseSettlement->closed_at->format('d.m.Y H:i') }}</span>
                        @endif
                    </dd>
                </div>
            </dl>
            @if ($expenseSettlement->notes)
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <dt class="text-gray-500 text-sm">Not</dt>
                    <dd class="text-sm text-gray-700">{{ $expenseSettlement->notes }}</dd>
                </div>
            @endif

            <div class="mt-4 flex flex-wrap gap-2">
                <x-expense-settlement-status-action :settlement="$expenseSettlement" variant="button" />
                <form method="POST" action="{{ route('expense-settlements.revert', $expenseSettlement) }}" onsubmit="return confirm('Bu giderleştirme silinecek; bağlı siparişler tekrar bekleyen siparişlere dönecek. Devam etmek istiyor musunuz?');">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-3 py-2 text-xs font-semibold rounded-lg border border-red-300 text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                        Giderleştirmeyi geri al
                    </button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <h2 class="px-4 py-3 text-sm font-semibold text-gray-700 border-b border-gray-200">Satırlar</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sözleşme no</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ürün</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dönem</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tutar (TL)</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">İşlem</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($expenseSettlement->lines as $line)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                    {{ $line->pendingBilling->subscription->sozlesme_no ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ $line->pendingBilling->subscription->product?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ $line->pendingBilling->period_start?->locale('tr')->translatedFormat('F Y') }}
                                </td>
                                <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">
                                    {{ number_format((float) $line->line_amount_tl, 2, ',', '.') }} ₺
                                </td>
                                <td class="px-4 py-3 text-right text-sm">
                                    @if ($line->pendingBilling)
                                        <a href="{{ route('subscriptions.show', $line->pendingBilling->subscription) }}" class="text-slate-600 hover:text-slate-900 font-medium">Abonelik</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
