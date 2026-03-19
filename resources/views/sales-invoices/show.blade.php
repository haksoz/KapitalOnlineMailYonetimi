<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Faturalandırma #{{ $salesInvoice->id }}">
        <x-slot name="left">
            <a href="{{ route('sales-invoices.index') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    <div x-data="{ atmacayaKopyalaOpen: {{ request()->boolean('atmacaya') ? 'true' : 'false' }} }" class="space-y-6">
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
                @if ($salesInvoice->order_number)
                <div>
                    <dt class="text-gray-500">Fatura Takip No (FTN)</dt>
                    <dd class="font-medium text-gray-900">{{ $salesInvoice->order_number }}</dd>
                </div>
                @endif
                <div>
                    <dt class="text-gray-500">Toplam (TL)</dt>
                    <dd class="font-medium text-gray-900">{{ $salesInvoice->total_amount_tl !== null ? number_format((float) $salesInvoice->total_amount_tl, 2, ',', '.') . ' ₺' : '—' }}</dd>
                </div>
            </dl>
            @if ($salesInvoice->invoice_total_net_tl !== null)
                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Fatura KDV hariç toplamı (girdiğin)</dt>
                        <dd class="font-medium text-gray-900">
                            {{ number_format((float) $salesInvoice->invoice_total_net_tl, 2, ',', '.') }} ₺
                        </dd>
                    </div>
                    @if ($salesInvoice->invoice_total_diff_tl !== null)
                    <div>
                        <dt class="text-gray-500">Sistem toplamı ile fark</dt>
                        <dd class="font-medium {{ (float) $salesInvoice->invoice_total_diff_tl === 0.0 ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ number_format((float) $salesInvoice->invoice_total_diff_tl, 2, ',', '.') }} ₺
                        </dd>
                        @if ($salesInvoice->invoice_total_diff_reason)
                            <p class="mt-1 text-xs text-gray-600">
                                Açıklama: {{ $salesInvoice->invoice_total_diff_reason }}
                            </p>
                        @endif
                    </div>
                    @endif
                </div>
            @endif
            @if ($salesInvoice->notes)
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <dt class="text-gray-500 text-sm">Not</dt>
                    <dd class="text-sm text-gray-700">{{ $salesInvoice->notes }}</dd>
                </div>
            @endif

            <div class="mt-4">
                <button
                    type="button"
                    @click="atmacayaKopyalaOpen = true"
                    class="inline-flex items-center px-3 py-2 text-xs font-semibold rounded-lg border border-slate-300 text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2"
                >
                    Atmaca’ya kopyala
                </button>
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

        {{-- Atmaca formatı popup --}}
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
                        Aşağıdaki tabloyu Excel’e, ardından Atmaca’da “Excel’den yapıştır” alanına yapıştırabilirsiniz.
                        Format: <span class="font-mono">Açıklama [TAB] Adet [TAB] TL Fiyat [TAB] KDV [TAB] İndirim</span>
                    </p>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-400">“Kopyala”ya tıklayın; tüm satırlar panoya kopyalanır.</span>
                        <button
                            type="button"
                            @click="$refs.atmacaText && $refs.atmacaText.select(); document.execCommand('copy');"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-md bg-slate-800 text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2"
                        >
                            Kopyala
                        </button>
                    </div>
                    <textarea
                        x-ref="atmacaText"
                        readonly
                        class="w-full h-56 text-xs font-mono border-gray-300 rounded-lg shadow-sm focus:ring-slate-500 focus:border-slate-500"
                        onfocus="this.select();"
                    >@foreach ($salesInvoice->lines as $line)
@php
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
@endphp
{{ $desc . "\t" . $qty . "\t" . number_format($unit, 2, '.', '') . "\t" . $vat . "\t" . $discount }}
@endforeach</textarea>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
