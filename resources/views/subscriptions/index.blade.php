<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 min-w-0">
            <h1 class="text-lg sm:text-xl font-semibold text-gray-800 truncate">Abonelikler</h1>
            <a href="{{ route('subscriptions.create') }}" class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 bg-slate-800 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition shrink-0 touch-manipulation">
                Yeni Abonelik
            </a>
        </div>
    </x-slot>

    <x-flash-messages />

    <form method="GET" action="{{ route('subscriptions.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
        <div class="min-w-[180px]">
            <x-input-label for="customer_cari_id" value="Müşteri (Cari)" />
            <select id="customer_cari_id" name="customer_cari_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                <option value="">— Tümü —</option>
                @foreach ($caris as $c)
                    <option value="{{ $c->id }}" @selected(request('customer_cari_id') == $c->id)>{{ $c->short_name ?: $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[140px]">
            <x-input-label for="durum" value="Durum" />
            <select id="durum" name="durum" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                <option value="">— Tümü —</option>
                <option value="active" @selected(request('durum') === 'active')>Aktif</option>
                <option value="cancelled" @selected(request('durum') === 'cancelled')>İptal</option>
                <option value="pending" @selected(request('durum') === 'pending')>Beklemede</option>
            </select>
        </div>
        <div class="pb-1">
            <x-primary-button type="submit">Filtrele</x-primary-button>
        </div>
    </form>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sözleşme No</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Müşteri</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tedarikçi</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ürün</th>
                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Adet</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Başlangıç</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bitiş</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durum</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Otomatik</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">İşlem</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($subscriptions as $sub)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $sub->sozlesme_no }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $sub->customerCari?->short_name ?: $sub->customerCari?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $sub->providerCari?->short_name ?: $sub->providerCari?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $sub->product?->name ?? '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-gray-600">{{ $sub->quantity ?? 1 }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">{{ $sub->baslangic_tarihi?->format('d.m.Y') ?? '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">{{ $sub->bitis_tarihi?->format('d.m.Y') ?? '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                @php
                                    $durumLabels = [
                                        'active' => 'Aktif',
                                        'cancelled' => 'İptal',
                                        'pending' => 'İptal planlandı',
                                    ];
                                @endphp
                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full
                                    {{ $sub->durum === 'active'
                                        ? 'bg-green-100 text-green-800'
                                        : ($sub->durum === 'cancelled'
                                            ? 'bg-red-100 text-red-800'
                                            : 'bg-amber-100 text-amber-800') }}">
                                    {{ $durumLabels[$sub->durum] ?? $sub->durum }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">{{ $sub->auto_renew ? 'Açık' : 'Kapalı' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                <a href="{{ route('subscriptions.show', $sub) }}" class="text-slate-600 hover:text-slate-900 font-medium">Detay</a>
                                <a href="{{ route('subscriptions.edit', $sub) }}" class="ml-3 text-slate-600 hover:text-slate-900 font-medium">Düzenle</a>
                                @if ($sub->durum === 'active')
                                    <form action="{{ route('subscriptions.cancel', $sub) }}" method="POST" class="inline-block ml-3" onsubmit="return confirm('Bu aboneliği iptal etmek istediğinize emin misiniz? İptal talimatı, aboneliğin bitiş tarihinde devreye girecek ve otomatik yenileme kapatılacaktır.');">
                                        @csrf
                                        <button type="submit" class="text-amber-600 hover:text-amber-800 font-medium">İptal et</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-sm text-gray-500">Henüz abonelik eklenmemiş.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($subscriptions->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                {{ $subscriptions->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
