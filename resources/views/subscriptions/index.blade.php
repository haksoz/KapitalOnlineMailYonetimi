<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Abonelikler">
        <x-slot name="right">
            <a href="{{ route('subscriptions.create') }}" class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 bg-slate-800 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition w-full sm:w-auto touch-manipulation">
                Yeni Abonelik
            </a>
        </x-slot>
    </x-page-toolbar>

    <form method="GET" action="{{ route('subscriptions.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
        <div class="min-w-[240px] flex-1">
            <x-input-label for="search" value="Ara" />
            <x-text-input id="search" name="search" type="search" :value="request('search')" maxlength="255" placeholder="Sözleşme no, müşteri veya ürün adı" class="mt-1 block w-full" />
        </div>
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
        <div class="min-w-[180px]">
            <x-input-label for="taahhut_tipi" value="Taahhüt tipi" />
            <select id="taahhut_tipi" name="taahhut_tipi" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                <option value="">— Tümü —</option>
                <option value="monthly_commitment" @selected(request('taahhut_tipi') === 'monthly_commitment')>Aylık taahhütlü</option>
                <option value="monthly_no_commitment" @selected(request('taahhut_tipi') === 'monthly_no_commitment')>Aylık taahhütsüz</option>
                <option value="annual_commitment" @selected(request('taahhut_tipi') === 'annual_commitment')>Yıllık taahhütlü</option>
            </select>
        </div>
        @if (request()->filled('sort'))
            <input type="hidden" name="sort" value="{{ request('sort') }}">
            <input type="hidden" name="direction" value="{{ request('direction') === 'desc' ? 'desc' : 'asc' }}">
        @endif
        <div class="pb-1">
            <x-primary-button type="submit">Filtrele</x-primary-button>
            @if (request()->filled('search'))
                <a href="{{ route('subscriptions.index', request()->except(['search', 'page'])) }}" class="ml-2 text-sm text-gray-600 hover:underline">Aramayı temizle</a>
            @endif
        </div>
    </form>

    @php
        $sortUrl = function (string $column): string {
            $next = request('sort') === $column && request('direction') !== 'desc' ? 'desc' : 'asc';

            return route('subscriptions.index', array_merge(request()->except('page'), [
                'sort' => $column,
                'direction' => $next,
            ]));
        };
        $sortClass = function (string $column): string {
            $active = request('sort') === $column;

            return 'hover:text-gray-800 '.($active ? 'text-gray-900' : '');
        };
        $sortMark = function (string $column): string {
            if (request('sort') !== $column) {
                return '';
            }

            return request('direction') === 'desc' ? ' ↓' : ' ↑';
        };
    @endphp

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ $sortUrl('sozlesme') }}" class="{{ $sortClass('sozlesme') }}">Sözleşme{{ $sortMark('sozlesme') }}</a>
                            <span class="text-gray-300">/</span>
                            <a href="{{ $sortUrl('musteri') }}" class="{{ $sortClass('musteri') }}">Müşteri{{ $sortMark('musteri') }}</a>
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ $sortUrl('urun') }}" class="{{ $sortClass('urun') }}">Ürün{{ $sortMark('urun') }}</a>
                        </th>
                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ $sortUrl('adet') }}" class="{{ $sortClass('adet') }}">Adet{{ $sortMark('adet') }}</a>
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ $sortUrl('alis') }}" class="{{ $sortClass('alis') }}">Alış{{ $sortMark('alis') }}</a>
                            <span class="text-gray-300">/</span>
                            <a href="{{ $sortUrl('satis') }}" class="{{ $sortClass('satis') }}">Satış{{ $sortMark('satis') }}</a>
                            <span class="text-gray-300">/</span>
                            <a href="{{ $sortUrl('kar') }}" class="{{ $sortClass('kar') }}">Kar %{{ $sortMark('kar') }}</a>
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ $sortUrl('baslangic') }}" class="{{ $sortClass('baslangic') }}">Başlangıç{{ $sortMark('baslangic') }}</a>
                            <span class="text-gray-300">/</span>
                            <a href="{{ $sortUrl('bitis') }}" class="{{ $sortClass('bitis') }}">Bitiş{{ $sortMark('bitis') }}</a>
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ $sortUrl('durum') }}" class="{{ $sortClass('durum') }}">Durum{{ $sortMark('durum') }}</a>
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ $sortUrl('otomatik') }}" class="{{ $sortClass('otomatik') }}">Otomatik{{ $sortMark('otomatik') }}</a>
                        </th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">İşlem</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($subscriptions as $sub)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <div class="text-xs">
                                    <span class="text-gray-500">Sözleşme:</span> <span class="font-medium text-gray-900">{{ $sub->sozlesme_no }}</span><br>
                                    <span class="text-gray-500">Müşteri:</span> <span class="font-medium text-base">{{ $sub->customerCari?->short_name ?: $sub->customerCari?->name ?? '—' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $sub->product?->name ?? '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-gray-600">{{ $sub->quantity ?? 1 }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                @php
                                    $alis = $sub->usd_birim_alis;
                                    $satis = $sub->usd_birim_satis;
                                    $kar = null;
                                    if ($alis && $satis && $alis > 0) {
                                        $kar = (($satis - $alis) / $alis) * 100;
                                    }
                                    $sym = $sub->currencySymbol();
                                @endphp
                                <div class="text-xs">
                                    <span class="text-gray-500">Para:</span> {{ $sub->currencyLabel() }}<br>
                                    <span class="text-gray-500">Alış:</span> {{ $sym }}{{ $alis !== null ? number_format((float) $alis, 4) : '—' }}<br>
                                    <span class="text-gray-500">Satış:</span> {{ $sym }}{{ $satis !== null ? number_format((float) $satis, 4) : '—' }}<br>
                                    @if($kar !== null)
                                        <span class="text-gray-500">Kar:</span> <span class="{{ $kar >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($kar, 2) }}%</span>
                                    @else
                                        <span class="text-gray-500">Kar:</span> —
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <div class="text-xs">
                                    <span class="text-gray-500">Başlangıç:</span> {{ $sub->baslangic_tarihi?->format('d.m.Y') ?? '—' }}<br>
                                    <span class="text-gray-500">Bitiş:</span> {{ $sub->bitis_tarihi?->format('d.m.Y') ?? '—' }}
                                </div>
                            </td>
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
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full {{ $sub->auto_renew ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $sub->auto_renew ? 'Açık' : 'Kapalı' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                <a href="{{ route('subscriptions.show', $sub) }}" class="inline-flex items-center justify-center rounded-md p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2" title="Detay" aria-label="Detay">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <a href="{{ route('subscriptions.edit', $sub) }}" class="ml-1 inline-flex items-center justify-center rounded-md p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2" title="Düzenle" aria-label="Düzenle">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">Henüz abonelik eklenmemiş.</td>
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
