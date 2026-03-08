<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('sales-invoices.index') }}" class="text-gray-500 hover:text-gray-700">&larr;</a>
            <h1 class="text-xl font-semibold text-gray-800">Faturalandırma #{{ $salesInvoice->id }}</h1>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Fatura bilgileri</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
                <div>
                    <dt class="text-gray-500">Müşteri</dt>
                    <dd class="font-medium text-gray-900">{{ $salesInvoice->customerCari?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Fatura no (bizim)</dt>
                    <dd class="font-medium text-gray-900">{{ $salesInvoice->our_invoice_number ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Fatura tarihi</dt>
                    <dd class="font-medium text-gray-900">{{ $salesInvoice->our_invoice_date?->format('d.m.Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Toplam (TL)</dt>
                    <dd class="font-medium text-gray-900">{{ $salesInvoice->total_amount_tl !== null ? number_format((float) $salesInvoice->total_amount_tl, 2, ',', '.') . ' ₺' : '—' }}</dd>
                </div>
            </dl>
            @if ($salesInvoice->notes)
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <dt class="text-gray-500 text-sm">Not</dt>
                    <dd class="text-sm text-gray-700">{{ $salesInvoice->notes }}</dd>
                </div>
            @endif
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
                        @foreach ($salesInvoice->lines as $line)
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
                                <td class="px-4 py-3 text-right text-sm space-x-2">
                                    @if ($line->pendingBilling->actual_alis_tl === null || $line->pendingBilling->actual_alis_tl === '')
                                        <a href="{{ route('pending-billings.supplier-invoice', [$line->pendingBilling, 'status' => 'invoiced']) }}" class="text-slate-600 hover:text-slate-900 font-medium">Alış gir</a>
                                        <span class="text-gray-300">|</span>
                                    @endif
                                    <a href="{{ route('subscriptions.show', $line->pendingBilling->subscription) }}" class="text-slate-600 hover:text-slate-900 font-medium">Abonelik</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
