<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Faturalandı">
        <x-slot name="right">
            <a href="{{ route('sales-invoices.sales-invoice-xml') }}" class="inline-flex items-center justify-center min-h-[40px] w-full sm:w-auto px-4 py-2 bg-slate-600 text-white rounded-lg font-semibold text-sm hover:bg-slate-700 focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition touch-manipulation">
                Satış faturası XML gir
            </a>
        </x-slot>
    </x-page-toolbar>

    <div class="mb-4">
        <p class="text-sm text-gray-600">
            Sistemin öngördüğü faturalandırmalar. Fatura gerçekten kesildiğinde satırdan &quot;Fatura bilgisi gir&quot; ile fatura numarası ve tarihini girebilirsiniz. Yeni faturalandırma Siparişler sayfasından yapılır.
        </p>
    </div>

    <div x-data="{ atmacayaKopyalaOpen: false, atmacaText: '' }" class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarih</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Müşteri</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fatura no</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fatura Takip No</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Toplam (TL)</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Satır sayısı</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">İşlem</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($salesInvoices as $inv)
                        @php
                            $atmacaLines = [];
                            foreach ($inv->lines as $line) {
                                $sub = $line->pendingBilling->subscription;
                                $qty = max(1, (int) ($sub->quantity ?? 1));
                                $unit = $qty > 0 ? ((float) $line->line_amount_tl / $qty) : (float) $line->line_amount_tl;
                                $vat = $sub->vat_rate !== null ? (float) $sub->vat_rate : 20;
                                $discount = 0;
                                $productName = $sub->product?->name ?? 'Hizmet';
                                $sozlesmeNo = $sub->sozlesme_no ?? '';
                                $descParts = array_filter([
                                    $productName,
                                    $sozlesmeNo ? ('Sözleşme: *' . $sozlesmeNo . '*') : null,
                                ]);
                                $desc = implode(' - ', $descParts);
                                $atmacaLines[] = $desc . "\t" . $qty . "\t" . number_format($unit, 2, '.', '') . "\t" . $vat . "\t" . $discount;
                            }
                            $atmacaTextRow = implode("\n", $atmacaLines);
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                {{ $inv->our_invoice_date ? $inv->our_invoice_date->format('d.m.Y') : ($inv->created_at->format('d.m.Y H:i')) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                {{ $inv->customerCari?->short_name ?: $inv->customerCari?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                {{ $inv->our_invoice_number ?? '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                {{ $inv->order_number ?? '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                {{ $inv->total_amount_tl !== null ? number_format((float) $inv->total_amount_tl, 2, ',', '.') . ' ₺' : '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-600">
                                {{ $inv->lines->count() }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm space-x-2">
                                <a href="{{ route('sales-invoices.invoice-details', $inv) }}" class="text-slate-600 hover:text-slate-900 font-medium">{{ $inv->our_invoice_number ? 'Fatura bilgisi düzenle' : 'Fatura bilgisi gir' }}</a>
                                <span class="text-gray-300">|</span>
                                <a href="{{ route('sales-invoices.show', $inv) }}" class="text-slate-600 hover:text-slate-900 font-medium">Detay</a>
                                <span class="text-gray-300">|</span>
                                <button
                                    type="button"
                                    class="text-slate-600 hover:text-slate-900 font-medium"
                                    @click="
                                        atmacaText = @js($atmacaTextRow);
                                        atmacayaKopyalaOpen = true;
                                        $nextTick(() => { if ($refs.globalAtmacaText) { $refs.globalAtmacaText.select(); document.execCommand('copy'); } });
                                    "
                                >
                                    Atmaca’ya kopyala
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
                                Henüz faturalandırma kaydı yok. Siparişler sayfasından seçim yapıp &quot;Seçilenleri faturaya geçir&quot; ile oluşturabilirsiniz.
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

        {{-- Atmaca formatı popup (liste sayfası için) --}}
        <div
            x-show="atmacayaKopyalaOpen"
            x-cloak
            class="fixed inset-0 z-40 flex items-center justify-center bg-black/40"
        >
            <div class="bg-white rounded-xl shadow-xl max-w-3xl w-full mx-4">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700">Atmaca’ya kopyala</h2>
                    <button
                        type="button"
                        @click="atmacayaKopyalaOpen = false"
                        class="text-gray-400 hover:text-gray-600 focus:outline-none"
                        aria-label="Kapat"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <div class="p-4 space-y-3">
                    <p class="text-xs text-gray-500">
                        Bu faturanın satırları Excel’e, ardından Atmaca’da “Excel’den yapıştır” alanına yapıştırılabilecek
                        formatta hazırlanmıştır.
                        Format: <span class="font-mono">Açıklama [TAB] Adet [TAB] TL Fiyat [TAB] KDV [TAB] İndirim</span>
                    </p>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-400">“Kopyala”ya tıklayın; tüm satırlar panoya kopyalanır.</span>
                        <button
                            type="button"
                            @click="$refs.globalAtmacaText && $refs.globalAtmacaText.select(); document.execCommand('copy');"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-md bg-slate-800 text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2"
                        >
                            Kopyala
                        </button>
                    </div>
                    <textarea
                        x-ref="globalAtmacaText"
                        x-model="atmacaText"
                        readonly
                        class="w-full h-56 text-xs font-mono border-gray-300 rounded-lg shadow-sm focus:ring-slate-500 focus:border-slate-500"
                    ></textarea>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
