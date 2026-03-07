<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 min-w-0">
            <h1 class="text-lg sm:text-xl font-semibold text-gray-800 truncate">Faturalandı</h1>
            <a href="{{ route('sales-invoices.create') }}" class="inline-flex items-center justify-center min-h-[40px] px-4 py-2 bg-slate-600 text-white rounded-lg font-semibold text-xs uppercase tracking-widest hover:bg-slate-700 focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                Yeni faturalandırma
            </a>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="mb-4">
        <p class="text-sm text-gray-600">
            Müşteriye gönderilen ödeme talepleri / faturalar. Siparişlerden seçilen kayıtlar tek fatura altında toplanabilir.
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarih</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Müşteri</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fatura no</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Toplam (TL)</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Satır sayısı</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">İşlem</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($salesInvoices as $inv)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                {{ $inv->created_at->format('d.m.Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                {{ $inv->customerCari?->short_name ?: $inv->customerCari?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                {{ $inv->our_invoice_number ?? '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                {{ $inv->total_amount_tl !== null ? number_format((float) $inv->total_amount_tl, 2, ',', '.') . ' ₺' : '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-600">
                                {{ $inv->lines->count() }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                <a href="{{ route('sales-invoices.show', $inv) }}" class="text-slate-600 hover:text-slate-900 font-medium">Detay</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">
                                Henüz faturalandırma kaydı yok. Siparişler sayfasından müşteri seçip kayıtları faturalandırabilirsiniz.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($salesInvoices->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                {{ $salesInvoices->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
