<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 min-w-0">
            <div class="flex items-center gap-2">
                <a href="{{ route('subscriptions.index') }}" class="text-gray-500 hover:text-gray-700">&larr;</a>
                <h1 class="text-lg sm:text-xl font-semibold text-gray-800 truncate">Abonelik — {{ $subscription->sozlesme_no }}</h1>
            </div>
            <div class="flex items-center gap-2">
                @if ($subscription->durum === 'active')
                    <form action="{{ route('subscriptions.cancel', $subscription) }}" method="POST" class="inline" onsubmit="return confirm('Bu aboneliği iptal etmek istediğinize emin misiniz? İptal talimatı, aboneliğin bitiş tarihinde devreye girecek ve otomatik yenileme kapatılacaktır.');">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center min-h-[40px] px-4 py-2 bg-white border border-amber-300 rounded-lg font-semibold text-xs text-amber-700 uppercase tracking-widest hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">İptal et</button>
                    </form>
                @endif
                <a href="{{ route('subscriptions.show-update-quantity', $subscription) }}" class="inline-flex items-center justify-center min-h-[40px] px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">Adet güncelle</a>
                <a href="{{ route('subscriptions.edit', $subscription) }}" class="inline-flex items-center justify-center min-h-[40px] px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">Düzenle</a>
            </div>
        </div>
    </x-slot>

    <x-flash-messages />

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

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h2 class="text-sm font-semibold text-gray-700">Bekleyen ve faturalanan siparişlerin özeti</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dönem</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durum</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Beklenen alış (TL)</th>
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
                                    {{ $pb->expected_alis_tl !== null ? number_format((float) $pb->expected_alis_tl, 2, ',', '.') : '—' }}
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
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right {{ $pb->fee_difference_tl !== null && (float) $pb->fee_difference_tl < 0 ? 'text-red-600' : 'text-gray-700' }}">
                                    {{ $pb->fee_difference_tl !== null ? number_format((float) $pb->fee_difference_tl, 2, ',', '.') : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">
                                    Bu aboneliğe ait bekleyen veya faturalanmış sipariş bulunmuyor.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
