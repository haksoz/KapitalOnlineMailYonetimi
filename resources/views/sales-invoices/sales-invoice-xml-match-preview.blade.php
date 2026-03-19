<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Satış faturası XML — Eşleşen faturalar">
        <x-slot name="left">
            <a href="{{ route('sales-invoices.sales-invoice-xml') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    <div class="mb-4">
        <p class="text-sm text-gray-600">
            Aşağıda XML’den okunan bilgiler ve fatura numarası verilmemiş, dönem + müşteri + sözleşme no ile eşleşen faturalar listeleniyor. <strong>KDV hariç toplam</strong>ları karşılaştırıp doğru faturayı seçin ve <strong>Bu fatura bu</strong> ile onaylayın.
        </p>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">XML’den okunan fatura bilgisi</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
                <div>
                    <dt class="text-gray-500">Müşteri</dt>
                    <dd class="font-medium text-gray-900">{{ $cariName }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Fatura no (XML)</dt>
                    <dd class="font-medium text-gray-900">{{ $parsed['invoice_id'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Fatura tarihi</dt>
                    <dd class="font-medium text-gray-900">{{ $parsed['issue_date'] ? \Carbon\Carbon::parse($parsed['issue_date'])->format('d.m.Y') : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">KDV hariç toplam (XML)</dt>
                    <dd class="font-medium text-gray-900">{{ number_format($xmlTaxExclusiveAmount, 2, ',', '.') }} ₺</dd>
                </div>
            </dl>
            @if(!empty($parsed['sozlesme_nos']))
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <dt class="text-gray-500 text-xs uppercase">Sözleşme no’lar (*xxx*)</dt>
                    <dd class="text-sm font-medium text-gray-700 mt-1">{{ implode(', ', $parsed['sozlesme_nos']) }}</dd>
                </div>
            @endif
        </div>

        @if(empty($candidates))
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                <p class="text-sm font-medium text-amber-800">Bu kriterlere uyan fatura numarası verilmemiş fatura bulunamadı.</p>
                <p class="text-sm text-amber-700 mt-1">Müşteri, dönem ve sözleşme no’ların sistemdeki kayıtlarla uyumlu olduğundan emin olun.</p>
                <div class="mt-4">
                    <a href="{{ route('sales-invoices.sales-invoice-xml') }}" class="inline-flex items-center px-4 py-2 bg-amber-600 text-white rounded-lg font-semibold text-sm hover:bg-amber-700">Yeni XML yükle</a>
                    <a href="{{ route('sales-invoices.sales-invoice-xml-match-cancel') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-sm text-gray-700 hover:bg-gray-50 ml-2">İptal</a>
                </div>
            </div>
        @else
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <h2 class="px-4 py-3 text-sm font-semibold text-gray-700 border-b border-gray-200">Eşleşen faturalar — Birini seçip onaylayın</h2>
                <form action="{{ route('sales-invoices.sales-invoice-xml-confirm') }}" method="POST" id="sales-invoice-xml-confirm-form" onsubmit="return confirm('Seçilen faturaya XML’deki fatura no ve tarih yazılacak. Devam?');">
                    @csrf
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seç</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sipariş / Takip no</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Satır sayısı</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Sistem KDV hariç (₺)</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">XML KDV hariç (₺)</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Fark (₺)</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sözleşme no’lar</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($candidates as $c)
                                    @php
                                        $sysTotal = $c['total_amount_tl'] ?? 0;
                                        $diff = $sysTotal !== null ? round($sysTotal - $xmlTaxExclusiveAmount, 2) : null;
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <input type="radio" name="sales_invoice_id" value="{{ $c['id'] }}" required class="rounded border-gray-300 text-slate-600 focus:ring-slate-500">
                                        </td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                            <a href="{{ route('sales-invoices.show', $c['id']) }}" class="text-slate-600 hover:text-slate-800">{{ $c['order_number'] ?? '#' . $c['id'] }}</a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-700">{{ $c['line_count'] }}</td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-900">
                                            {{ $sysTotal !== null ? number_format($sysTotal, 2, ',', '.') : '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-700">{{ number_format($xmlTaxExclusiveAmount, 2, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-sm text-right">
                                            @if($diff !== null)
                                                <span class="{{ $diff == 0 ? 'text-green-600' : 'text-amber-600' }}">{{ number_format($diff, 2, ',', '.') }}</span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ implode(', ', $c['sozlesme_nos'] ?? []) ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex flex-wrap gap-3">
                        <button type="submit" form="sales-invoice-xml-confirm-form" class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded-lg font-semibold text-sm hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                            Bu fatura bu — Onayla
                        </button>
                        <a href="{{ route('sales-invoices.sales-invoice-xml-match-cancel') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                            İptal
                        </a>
                    </div>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
