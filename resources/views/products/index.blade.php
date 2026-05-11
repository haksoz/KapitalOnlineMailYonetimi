<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Ürünler">
        <x-slot name="right">
            <a href="{{ route('products.create') }}" class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 bg-slate-800 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition w-full sm:w-auto touch-manipulation">
                Yeni Ürün
            </a>
        </x-slot>
    </x-page-toolbar>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ürün</th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                        <th scope="col" class="px-2 py-3 text-center text-xs font-medium text-blue-600 uppercase tracking-wider border-l border-gray-200">Aylık Taahhütlü</th>
                        <th scope="col" class="px-2 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-l border-gray-200">Aylık Taahhütsüz</th>
                        <th scope="col" class="px-2 py-3 text-center text-xs font-medium text-emerald-600 uppercase tracking-wider border-l border-gray-200">Yıllık Taahhütlü</th>
                        <th scope="col" class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider border-l border-gray-200">İşlem</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($products as $product)
                        <tr class="hover:bg-gray-50">
                            {{-- Ürün (Ad + Servis Sağlayıcı) --}}
                            <td class="px-3 py-3">
                                <a href="{{ route('products.show', $product) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800 hover:underline block">{{ $product->name }}</a>
                                <span class="text-xs text-gray-500">{{ $product->serviceProvider?->name ?? '—' }}</span>
                            </td>
                            {{-- Stok Kodu --}}
                            <td class="px-3 py-3 whitespace-nowrap text-xs text-gray-500">{{ $product->stock_code ?? '—' }}</td>
                            {{-- Aylık Taahhütlü --}}
                            <td class="px-2 py-3 text-right text-xs border-l border-gray-100">
                                <div class="space-y-1">
                                    <div class="flex justify-end items-center gap-1">
                                        <span class="text-gray-500">A:</span>
                                        <span class="text-gray-600">{{ $product->alis_usd_monthly_commitment ? number_format($product->alis_usd_monthly_commitment, 2) : '—' }}</span>
                                    </div>
                                    <div class="flex justify-end items-center gap-1">
                                        <span class="text-gray-500">S:</span>
                                        <span class="font-semibold text-blue-700">{{ $product->satis_usd_monthly_commitment ? number_format($product->satis_usd_monthly_commitment, 2) : '—' }}</span>
                                    </div>
                                    <div class="flex justify-end">
                                        @if($product->profit_percentage_monthly_commitment !== null)
                                            <span class="text-[10px] px-1.5 py-0.5 rounded {{ $product->profit_percentage_monthly_commitment > 0 ? 'bg-green-100 text-green-700' : ($product->profit_percentage_monthly_commitment < 0 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                                                {{ $product->profit_percentage_monthly_commitment > 0 ? '+' : '' }}{{ number_format($product->profit_percentage_monthly_commitment, 0) }}%
                                            </span>
                                        @else
                                            <span class="text-[10px] text-gray-400">—</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            {{-- Aylık Taahhütsüz --}}
                            <td class="px-2 py-3 text-right text-xs border-l border-gray-100">
                                <div class="space-y-1">
                                    <div class="flex justify-end items-center gap-1">
                                        <span class="text-gray-500">A:</span>
                                        <span class="text-gray-600">{{ $product->alis_usd_monthly_no_commitment ? number_format($product->alis_usd_monthly_no_commitment, 2) : '—' }}</span>
                                    </div>
                                    <div class="flex justify-end items-center gap-1">
                                        <span class="text-gray-500">S:</span>
                                        <span class="font-semibold text-gray-700">{{ $product->satis_usd_monthly_no_commitment ? number_format($product->satis_usd_monthly_no_commitment, 2) : '—' }}</span>
                                    </div>
                                    <div class="flex justify-end">
                                        @if($product->profit_percentage_monthly_no_commitment !== null)
                                            <span class="text-[10px] px-1.5 py-0.5 rounded {{ $product->profit_percentage_monthly_no_commitment > 0 ? 'bg-green-100 text-green-700' : ($product->profit_percentage_monthly_no_commitment < 0 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                                                {{ $product->profit_percentage_monthly_no_commitment > 0 ? '+' : '' }}{{ number_format($product->profit_percentage_monthly_no_commitment, 0) }}%
                                            </span>
                                        @else
                                            <span class="text-[10px] text-gray-400">—</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            {{-- Yıllık Taahhütlü --}}
                            <td class="px-2 py-3 text-right text-xs border-l border-gray-100">
                                <div class="space-y-1">
                                    <div class="flex justify-end items-center gap-1">
                                        <span class="text-gray-500">A:</span>
                                        <span class="text-gray-600">{{ $product->alis_usd_yearly_commitment ? number_format($product->alis_usd_yearly_commitment, 2) : '—' }}</span>
                                    </div>
                                    <div class="flex justify-end items-center gap-1">
                                        <span class="text-gray-500">S:</span>
                                        <span class="font-semibold text-emerald-700">{{ $product->satis_usd_yearly_commitment ? number_format($product->satis_usd_yearly_commitment, 2) : '—' }}</span>
                                    </div>
                                    <div class="flex justify-end">
                                        @if($product->profit_percentage_yearly_commitment !== null)
                                            <span class="text-[10px] px-1.5 py-0.5 rounded {{ $product->profit_percentage_yearly_commitment > 0 ? 'bg-green-100 text-green-700' : ($product->profit_percentage_yearly_commitment < 0 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                                                {{ $product->profit_percentage_yearly_commitment > 0 ? '+' : '' }}{{ number_format($product->profit_percentage_yearly_commitment, 0) }}%
                                            </span>
                                        @else
                                            <span class="text-[10px] text-gray-400">—</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            {{-- İşlem --}}
                            <td class="px-3 py-3 whitespace-nowrap text-right text-xs border-l border-gray-100">
                                <a href="{{ route('products.show', $product) }}" class="text-blue-600 hover:text-blue-800 font-medium">Detay</a>
                                <a href="{{ route('products.edit', $product) }}" class="text-slate-600 hover:text-slate-900 font-medium ml-2">Düzenle</a>
                                <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline-block ml-2" onsubmit="return confirm('Bu ürünü silmek istediğinize emin misiniz?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Sil</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">Henüz ürün eklenmemiş.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($products->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
