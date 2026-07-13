<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Aktivite Logları">
        <x-slot name="left">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 mb-6">
        <form method="GET" action="{{ route('admin.activity-logs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <x-input-label for="product_id" value="Ürün ID" />
                <x-text-input id="product_id" name="product_id" type="number" class="mt-1 block w-full" :value="request('product_id')" />
            </div>
            <div>
                <x-input-label for="subscription_id" value="Abonelik ID" />
                <x-text-input id="subscription_id" name="subscription_id" type="number" class="mt-1 block w-full" :value="request('subscription_id')" />
            </div>
            <div>
                <x-input-label for="date_from" value="Başlangıç" />
                <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full" :value="request('date_from')" />
            </div>
            <div>
                <x-input-label for="date_to" value="Bitiş" />
                <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full" :value="request('date_to')" />
            </div>
            <div class="flex items-end gap-2 lg:col-span-4">
                <button type="submit" class="inline-flex items-center justify-center min-h-[38px] px-4 py-2 bg-slate-800 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition">
                    Filtrele
                </button>
                <a href="{{ route('admin.activity-logs.index') }}" class="inline-flex items-center justify-center min-h-[38px] px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition">
                    Sıfırla
                </a>
            </div>
        </form>
    </div>

    <div class="space-y-6">
        {{-- Ürün Fiyat Değişimleri --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <h2 class="px-4 py-3 text-sm font-semibold text-gray-700 border-b border-gray-200">Ürün Fiyat Değişimleri</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarih</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ürün</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alan</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Eski</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Yeni</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kullanıcı</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($productPriceLogs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $log->created_at?->format('d.m.Y H:i') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $log->product?->name ?? '—' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $log->fieldLabel() }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">{{ $log->old_value !== null ? number_format((float) $log->old_value, 4, ',', '.') : '—' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">{{ $log->new_value !== null ? number_format((float) $log->new_value, 4, ',', '.') : '—' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $log->changedBy?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">Kayıt bulunamadı.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($productPriceLogs->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                    {{ $productPriceLogs->links() }}
                </div>
            @endif
        </div>

        {{-- Abonelik Fiyat Değişimleri --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <h2 class="px-4 py-3 text-sm font-semibold text-gray-700 border-b border-gray-200">Abonelik Fiyat Değişimleri</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarih</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Abonelik</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alan</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Eski</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Yeni</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kullanıcı</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($subscriptionPriceLogs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $log->created_at?->format('d.m.Y H:i') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $log->subscription?->sozlesme_no ?? '—' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $log->fieldLabel() }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">{{ $log->old_value !== null ? number_format((float) $log->old_value, 4, ',', '.') : '—' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">{{ $log->new_value !== null ? number_format((float) $log->new_value, 4, ',', '.') : '—' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $log->changedBy?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">Kayıt bulunamadı.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($subscriptionPriceLogs->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                    {{ $subscriptionPriceLogs->links() }}
                </div>
            @endif
        </div>

        {{-- Abonelik Adet Değişimleri --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <h2 class="px-4 py-3 text-sm font-semibold text-gray-700 border-b border-gray-200">Abonelik Adet Değişimleri</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kayıt Tarihi</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Geçerlilik</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Abonelik</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Eski Adet</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Yeni Adet</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kullanıcı</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($subscriptionQuantityLogs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $log->created_at?->format('d.m.Y H:i') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $log->effective_date?->format('d.m.Y') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $log->subscription?->sozlesme_no ?? '—' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">{{ $log->previous_quantity }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">{{ $log->new_quantity }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $log->changedBy?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">Kayıt bulunamadı.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($subscriptionQuantityLogs->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                    {{ $subscriptionQuantityLogs->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
