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

    <form method="GET" action="{{ route('sales-invoices.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
        <div class="min-w-[220px] flex-1">
            <label for="search" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Ara</label>
            <input
                id="search"
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Müşteri, fatura no veya takip no..."
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 text-sm"
            >
        </div>
        <div class="min-w-[180px]">
            <label for="customer_cari_id" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Müşteri</label>
            <select id="customer_cari_id" name="customer_cari_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 text-sm">
                <option value="">— Tümü —</option>
                @foreach ($caris ?? [] as $c)
                    <option value="{{ $c->id }}" @selected(request('customer_cari_id') == $c->id)>{{ $c->short_name ?: $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[120px]">
            <label for="period_year" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Dönem (yıl)</label>
            <select id="period_year" name="period_year" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 text-sm">
                <option value="">— Tümü —</option>
                @for ($y = now()->year; $y >= now()->year - 3; $y--)
                    <option value="{{ $y }}" @selected((string) request('period_year') === (string) $y)>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <div class="min-w-[140px]">
            <label for="period_month" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Dönem (ay)</label>
            <select id="period_month" name="period_month" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 text-sm">
                <option value="">— Tümü —</option>
                @for ($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected((string) request('period_month') === (string) $m)>{{ \Carbon\Carbon::createFromDate(2000, $m, 1)->locale('tr')->translatedFormat('F') }}</option>
                @endfor
            </select>
        </div>
        <div class="min-w-[140px]">
            <label for="payment_status" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Ödeme</label>
            <select id="payment_status" name="payment_status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 text-sm">
                <option value="">— Tümü —</option>
                <option value="paid" @selected(request('payment_status') === 'paid')>Ödendi</option>
                <option value="unpaid" @selected(request('payment_status') === 'unpaid')>Ödenmedi</option>
            </select>
        </div>
        <button type="submit" class="inline-flex items-center justify-center min-h-[38px] px-4 py-2 bg-slate-800 text-white text-sm font-medium rounded-md hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">Filtrele</button>
        @if (request()->filled('search') || request()->filled('customer_cari_id') || request()->filled('period_year') || request()->filled('period_month') || request()->filled('payment_status'))
            <a href="{{ route('sales-invoices.index') }}" class="inline-flex items-center min-h-[38px] px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Filtreyi temizle</a>
        @endif
    </form>

    <div x-data="{ atmacayaKopyalaOpen: false, atmacaText: '' }" class="bg-white rounded-xl shadow-sm overflow-hidden">
        @php
            $iconBtnClass = 'inline-flex items-center justify-center w-8 h-8 rounded-md text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-1 touch-manipulation';
        @endphp
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarih</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Müşteri</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fatura no</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fatura Takip No</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vade</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">KDV dahil toplam (TL)</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ödeme</th>
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
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                <a href="{{ route('sales-invoices.invoice-details', $inv) }}" class="text-slate-700 hover:text-slate-900 hover:underline" title="Vade tarihini düzenle">
                                    {{ $inv->due_date?->format('d.m.Y') ?? 'Vade gir' }}
                                </a>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                @php $payable = $inv->payableAmountTl(); @endphp
                                {{ $payable !== null ? number_format($payable, 2, ',', '.') . ' ₺' : '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                <x-sales-invoice-payment-badge :invoice="$inv" />
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-600">
                                {{ $inv->lines->count() }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                @php
                                    $invoiceDetailsLabel = $inv->our_invoice_number ? 'Fatura bilgisi düzenle' : 'Fatura bilgisi gir';
                                @endphp
                                <div class="inline-flex items-center justify-end gap-0.5">
                                    <x-sales-invoice-payment-action :invoice="$inv" variant="icon" />
                                    <a
                                        href="{{ route('sales-invoices.invoice-details', $inv) }}"
                                        class="{{ $iconBtnClass }}"
                                        title="{{ $invoiceDetailsLabel }}"
                                        aria-label="{{ $invoiceDetailsLabel }}"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <a
                                        href="{{ route('sales-invoices.show', $inv) }}"
                                        class="{{ $iconBtnClass }}"
                                        title="Detay"
                                        aria-label="Detay"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <button
                                        type="button"
                                        class="{{ $iconBtnClass }}"
                                        title="Atmaca’ya kopyala"
                                        aria-label="Atmaca’ya kopyala"
                                        @click="
                                            atmacaText = @js($atmacaTextRow);
                                            atmacayaKopyalaOpen = true;
                                            $nextTick(() => { if ($refs.globalAtmacaText) { $refs.globalAtmacaText.select(); document.execCommand('copy'); } });
                                        "
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-500">
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
