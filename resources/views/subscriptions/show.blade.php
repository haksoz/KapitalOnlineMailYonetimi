<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 min-w-0">
            <div class="flex items-center gap-2">
                <a href="{{ route('subscriptions.index') }}" class="text-gray-500 hover:text-gray-700">&larr;</a>
                <h1 class="text-lg sm:text-xl font-semibold text-gray-800 truncate">Abonelik — {{ $subscription->sozlesme_no }}</h1>
            </div>
            <div class="flex items-center gap-2">
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
                        @php $durumLabels = ['active' => 'Aktif', 'cancelled' => 'İptal', 'pending' => 'Beklemede']; @endphp
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full {{ $subscription->durum === 'active' ? 'bg-green-100 text-green-800' : ($subscription->durum === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                            {{ $durumLabels[$subscription->durum] ?? $subscription->durum }}
                        </span>
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
                    <dt class="text-gray-500">KDV (%)</dt>
                    <dd class="font-medium text-gray-900">{{ $subscription->vat_rate !== null ? number_format((float) $subscription->vat_rate, 2, ',', '.') : '20' }}%</dd>
                </div>
            </dl>
        </div>

        @if ($subscription->quantityChanges->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <h2 class="px-4 py-3 text-sm font-semibold text-gray-700 border-b border-gray-200">Adet değişim geçmişi</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarih</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Önceki adet</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Yeni adet</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($subscription->quantityChanges as $change)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $change->effective_date?->format('d.m.Y') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">{{ $change->previous_quantity }}</td>
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
                <h2 class="text-sm font-semibold text-gray-700">Aylık beklenen / gerçekleşen maliyet</h2>
                <form action="{{ route('subscriptions.create-projection', $subscription) }}" method="POST" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <select name="year" class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 text-sm" required>
                        @for ($y = now()->year; $y >= now()->year - 2; $y--)
                            <option value="{{ $y }}" @selected($y === now()->year)>{{ $y }}</option>
                        @endfor
                    </select>
                    <select name="month" class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 text-sm" required>
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($m === (int) now()->month)>{{ \Carbon\Carbon::createFromDate(2000, $m, 1)->translatedFormat('F') }}</option>
                        @endfor
                    </select>
                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-slate-800 text-white text-sm font-medium rounded-md hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">Bu ayı oluştur</button>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dönem</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Beklenen (TL)</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Gerçekleşen (TL)</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Fark (TL)</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durum</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($subscription->monthlyProjections as $proj)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $proj->year }}-{{ str_pad($proj->month, 2, '0', STR_PAD_LEFT) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">{{ number_format($proj->expected_total_try, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">{{ $proj->actual_total_try !== null ? number_format($proj->actual_total_try, 2, ',', '.') : '—' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right {{ $proj->difference_try !== null && (float)$proj->difference_try < 0 ? 'text-red-600' : 'text-gray-700' }}">
                                    {{ $proj->difference_try !== null ? number_format($proj->difference_try, 2, ',', '.') : '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    @if ($proj->status === 'invoiced')
                                        <span class="text-green-600 font-medium">Faturalandı</span>
                                    @else
                                        <span class="text-gray-500">Tahmini</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Henüz aylık maliyet kaydı yok. Yukarıdaki “Bu ayı oluştur” ile ekleyebilirsiniz.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
