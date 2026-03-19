<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Abonelik — {{ $subscription->sozlesme_no }}">
        <x-slot name="left">
            <a href="{{ route('subscriptions.index') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
        <x-slot name="right">
            <div class="flex items-center gap-2 flex-wrap justify-end">
                @if ($subscription->durum === 'active')
                    <form action="{{ route('subscriptions.cancel', $subscription) }}" method="POST" class="inline" onsubmit="return confirm('Bu aboneliği iptal etmek istediğinize emin misiniz? İptal talimatı, aboneliğin bitiş tarihinde devreye girecek ve otomatik yenileme kapatılacaktır.');">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center min-h-[40px] px-4 py-2 bg-white border border-amber-300 rounded-lg font-semibold text-xs text-amber-700 uppercase tracking-widest hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                            İptal et
                        </button>
                    </form>
                @endif
                <a href="{{ route('subscriptions.show-update-quantity', $subscription) }}" class="inline-flex items-center justify-center min-h-[40px] px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                    Adet güncelle
                </a>
                <a href="{{ route('subscriptions.edit', $subscription) }}" class="inline-flex items-center justify-center min-h-[40px] px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                    Düzenle
                </a>
            </div>
        </x-slot>
    </x-page-toolbar>

    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Abonelik bilgileri</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 text-sm">
                <div>
                    <dt class="text-gray-500">Müşteri</dt>
                    <dd class="font-medium text-gray-900">{{ $subscription->customerCari?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Tedarikçi</dt>
                    <dd class="font-medium text-gray-900">{{ $subscription->providerCari?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Servis sağlayıcı</dt>
                    <dd class="font-medium text-gray-900">{{ $subscription->serviceProvider?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Ürün</dt>
                    <dd class="font-medium text-gray-900">{{ $subscription->product?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Ürün adeti</dt>
                    <dd class="font-medium text-gray-900">{{ $subscription->quantity ?? 1 }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Başlangıç / Bitiş</dt>
                    <dd class="font-medium text-gray-900">{{ $subscription->baslangic_tarihi?->format('d.m.Y') ?? '—' }} — {{ $subscription->bitis_tarihi?->format('d.m.Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Durum</dt>
                    <dd>
                        @php
                            $durumLabels = [
                                'active' => 'Aktif',
                                'cancelled' => 'İptal',
                                'pending' => 'İptal planlandı',
                            ];
                        @endphp
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full
                            {{ $subscription->durum === 'active'
                                ? 'bg-green-100 text-green-800'
                                : ($subscription->durum === 'cancelled'
                                    ? 'bg-red-100 text-red-800'
                                    : 'bg-amber-100 text-amber-800') }}">
                            {{ $durumLabels[$subscription->durum] ?? $subscription->durum }}
                        </span>
                        @if ($subscription->durum === 'pending' && $subscription->planned_cancel_date)
                            <span class="ml-2 text-xs text-gray-500">
                                ({{ $subscription->planned_cancel_date->format('d.m.Y') }} tarihinde iptal edilecek)
                            </span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Otomatik yenileme</dt>
                    <dd class="font-medium text-gray-900">{{ $subscription->auto_renew ? 'Açık' : 'Kapalı' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">USD birim alış (sabit)</dt>
                    <dd class="font-medium text-gray-900">{{ $subscription->usd_birim_alis !== null ? number_format((float) $subscription->usd_birim_alis, 4, ',', '.') : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">USD birim satış (sabit)</dt>
                    <dd class="font-medium text-gray-900">{{ $subscription->usd_birim_satis !== null ? number_format((float) $subscription->usd_birim_satis, 4, ',', '.') : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Yüzde kâr</dt>
                    <dd class="font-medium text-gray-900">
                        @php
                            $margin = null;
                            if ($subscription->usd_birim_alis !== null && (float) $subscription->usd_birim_alis > 0 && $subscription->usd_birim_satis !== null) {
                                $margin = ((float) $subscription->usd_birim_satis - (float) $subscription->usd_birim_alis) / (float) $subscription->usd_birim_alis * 100;
                            }
                        @endphp
                        {{ $margin !== null ? number_format($margin, 2, ',', '.') . ' %' : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">KDV (%)</dt>
                    <dd class="font-medium text-gray-900">{{ $subscription->vat_rate !== null ? number_format((float) $subscription->vat_rate, 2, ',', '.') : '20' }}%</dd>
                </div>
            </dl>
        </div>

        @if ($subscription->quantityChanges->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <h2 class="px-4 py-3 text-sm font-semibold text-gray-700 border-b border-gray-200">Adet güncellemeleri</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarih</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Adet</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($subscription->quantityChanges as $change)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $change->effective_date?->format('d.m.Y') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">{{ $change->new_quantity }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm overflow-hidden" x-data="{
            totals: null,
            loading: false,
            error: null,
            async loadTotals() {
                this.loading = true;
                this.error = null;
                this.totals = null;
                try {
                    const r = await fetch('{{ route('subscriptions.order-summary-totals', $subscription) }}', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!r.ok) throw new Error('Hesaplanamadı');
                    this.totals = await r.json();
                } catch (e) {
                    this.error = e.message || 'Bir hata oluştu';
                }
                this.loading = false;
            },
            fmt(n) { return n == null ? '—' : Number(n).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
        }">
            <div class="px-4 py-3 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h2 class="text-sm font-semibold text-gray-700">Bekleyen ve faturalanan siparişlerin özeti</h2>
                <button type="button" @click="loadTotals()" :disabled="loading"
                    class="inline-flex items-center justify-center min-h-[38px] px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 disabled:opacity-50">
                    <span x-show="!loading">Finansal Toplamlar</span>
                    <span x-show="loading" x-cloak>Hesaplanıyor…</span>
                </button>
            </div>
            <div x-show="totals || error" x-cloak class="px-4 py-3 border-b border-gray-200 bg-gray-50"
                x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <p x-show="error" class="text-sm text-red-600" x-text="error"></p>
                <dl x-show="totals && !error" class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Beklenen satış (TL)</dt>
                        <dd class="font-medium text-gray-900" x-text="fmt(totals?.expected_satis_tl)"></dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Gerçekleşen alış (TL)</dt>
                        <dd class="font-medium text-gray-900" x-text="fmt(totals?.actual_alis_tl)"></dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Gerçekleşen satış (TL)</dt>
                        <dd class="font-medium text-gray-900" x-text="fmt(totals?.actual_satis_tl)"></dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Fark (TL)</dt>
                        <dd class="font-medium" :class="totals && totals.fark_tl > 0 ? 'text-red-600' : 'text-gray-900'" x-text="fmt(totals?.fark_tl)"></dd>
                    </div>
                </dl>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dönem</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durum</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Beklenen satış (TL)</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tedarik fatura no</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Gerçekleşen alış (TL)</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Gerçekleşen satış (TL)</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Fark (TL)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($orderSummaries as $pb)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $pb->period_start?->format('d.m.Y') }} — {{ $pb->period_end?->format('d.m.Y') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    @if ($pb->status === 'invoiced')
                                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800">Faturalandı</span>
                                    @elseif ($pb->status === 'cancelled')
                                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-800">İptal</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-amber-100 text-amber-800">Bekleyen</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">
                                    {{ $pb->expected_satis_tl !== null ? number_format((float) $pb->expected_satis_tl, 2, ',', '.') : '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                    {{ $pb->supplier_invoice_number ?? '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">
                                    {{ $pb->actual_alis_tl !== null ? number_format((float) $pb->actual_alis_tl, 2, ',', '.') : '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">
                                    {{ $pb->actual_satis_tl !== null ? number_format((float) $pb->actual_satis_tl, 2, ',', '.') : '—' }}
                                </td>
                                @php
                                    $farkTl = $pb->fee_difference_tl;
                                    if ($farkTl === null && $pb->expected_satis_tl !== null && $pb->expected_satis_tl !== '' && $pb->actual_satis_tl !== null && $pb->actual_satis_tl !== '') {
                                        $farkTl = (float) $pb->expected_satis_tl - (float) $pb->actual_satis_tl;
                                    }
                                @endphp
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right {{ $farkTl !== null && (float) $farkTl > 0 ? 'text-red-600' : 'text-gray-700' }}">
                                    {{ $farkTl !== null ? number_format((float) $farkTl, 2, ',', '.') : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
                                    Bu aboneliğe ait bekleyen veya faturalanmış sipariş bulunmuyor.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($orderSummaries->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">
                    {{ $orderSummaries->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
