<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Ürün Detayı">
        <x-slot name="right">
            <div class="flex gap-2">
                <a href="{{ route('products.edit', $product) }}" class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 bg-slate-700 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition">
                    Düzenle
                </a>
                <a href="{{ route('products.index') }}" class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 bg-white border border-gray-300 rounded-lg font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition">
                    Geri
                </a>
            </div>
        </x-slot>
    </x-page-toolbar>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Temel Bilgiler --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">Temel Bilgiler</h3>
            <div class="space-y-4">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Ürün Adı</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $product->name }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Stok Kodu</p>
                    <p class="text-base text-gray-700">{{ $product->stock_code ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Servis Sağlayıcı</p>
                    <p class="text-base text-gray-700">{{ $product->serviceProvider?->name ?? '—' }}</p>
                </div>
                @if($product->description)
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Açıklama</p>
                    <p class="text-sm text-gray-600 leading-relaxed">{{ $product->description }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Fiyatlar --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Aylık Taahhütlü --}}
            <div class="bg-white rounded-xl shadow-sm border border-blue-200 p-6">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-blue-100">
                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-semibold">AYLIK TAAHÜTLÜ</span>
                </div>
                <div class="grid grid-cols-3 gap-6">
                    <div class="text-center">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Alış USD</p>
                        <p class="text-xl font-semibold text-gray-700">
                            {{ $product->alis_usd_monthly_commitment ? number_format($product->alis_usd_monthly_commitment, 2) : '—' }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Satış USD</p>
                        <p class="text-xl font-semibold text-emerald-600">
                            {{ $product->satis_usd_monthly_commitment ? number_format($product->satis_usd_monthly_commitment, 2) : '—' }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Kar %</p>
                        @if($product->profit_percentage_monthly_commitment !== null)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $product->profit_percentage_monthly_commitment > 0 ? 'bg-green-100 text-green-800' : ($product->profit_percentage_monthly_commitment < 0 ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                                {{ $product->profit_percentage_monthly_commitment > 0 ? '+' : '' }}{{ number_format($product->profit_percentage_monthly_commitment, 1) }}%
                            </span>
                        @else
                            <p class="text-xl text-gray-400">—</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Aylık Taahhütsüz --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100">
                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-semibold">AYLIK TAAHÜTSÜZ</span>
                </div>
                <div class="grid grid-cols-3 gap-6">
                    <div class="text-center">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Alış USD</p>
                        <p class="text-xl font-semibold text-gray-700">
                            {{ $product->alis_usd_monthly_no_commitment ? number_format($product->alis_usd_monthly_no_commitment, 2) : '—' }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Satış USD</p>
                        <p class="text-xl font-semibold text-emerald-600">
                            {{ $product->satis_usd_monthly_no_commitment ? number_format($product->satis_usd_monthly_no_commitment, 2) : '—' }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Kar %</p>
                        @if($product->profit_percentage_monthly_no_commitment !== null)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $product->profit_percentage_monthly_no_commitment > 0 ? 'bg-green-100 text-green-800' : ($product->profit_percentage_monthly_no_commitment < 0 ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                                {{ $product->profit_percentage_monthly_no_commitment > 0 ? '+' : '' }}{{ number_format($product->profit_percentage_monthly_no_commitment, 1) }}%
                            </span>
                        @else
                            <p class="text-xl text-gray-400">—</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Yıllık Taahhütlü --}}
            <div class="bg-white rounded-xl shadow-sm border border-emerald-200 p-6">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-emerald-100">
                    <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-xs font-semibold">YILLIK TAAHÜTLÜ</span>
                </div>
                <div class="grid grid-cols-3 gap-6">
                    <div class="text-center">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Alış USD</p>
                        <p class="text-xl font-semibold text-gray-700">
                            {{ $product->alis_usd_yearly_commitment ? number_format($product->alis_usd_yearly_commitment, 2) : '—' }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Satış USD</p>
                        <p class="text-xl font-semibold text-emerald-600">
                            {{ $product->satis_usd_yearly_commitment ? number_format($product->satis_usd_yearly_commitment, 2) : '—' }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Kar %</p>
                        @if($product->profit_percentage_yearly_commitment !== null)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $product->profit_percentage_yearly_commitment > 0 ? 'bg-green-100 text-green-800' : ($product->profit_percentage_yearly_commitment < 0 ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                                {{ $product->profit_percentage_yearly_commitment > 0 ? '+' : '' }}{{ number_format($product->profit_percentage_yearly_commitment, 1) }}%
                            </span>
                        @else
                            <p class="text-xl text-gray-400">—</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($product->priceHistories->isNotEmpty())
    <div class="mt-6 bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700">Son Fiyat Değişimleri</h2>
            <a href="{{ route('admin.activity-logs.index', ['product_id' => $product->id]) }}" class="text-xs font-medium text-slate-600 hover:text-slate-900">Tüm tarihçe &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarih</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alan</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Eski</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Yeni</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kullanıcı</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($product->priceHistories as $history)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $history->created_at?->format('d.m.Y H:i') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $history->fieldLabel() }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">{{ $history->old_value !== null ? number_format((float) $history->old_value, 4, ',', '.') : '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">{{ $history->new_value !== null ? number_format((float) $history->new_value, 4, ',', '.') : '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $history->changedBy?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Metadata --}}
    <div class="mt-6 bg-gray-50 rounded-lg p-4 text-xs text-gray-500">
        <div class="flex justify-between">
            <span>Oluşturulma: {{ $product->created_at?->format('d.m.Y H:i') }}</span>
            <span>Son Güncelleme: {{ $product->updated_at?->format('d.m.Y H:i') }}</span>
        </div>
    </div>
</x-app-layout>
