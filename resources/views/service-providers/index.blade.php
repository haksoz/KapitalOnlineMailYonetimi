<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 min-w-0">
            <h1 class="text-lg sm:text-xl font-semibold text-gray-800 truncate">Servis Sağlayıcılar</h1>
            <a href="{{ route('service-providers.create') }}" class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 bg-slate-800 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition shrink-0 touch-manipulation">
                Yeni Servis Sağlayıcı
            </a>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ad</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kod</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hizmet Tipleri</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">İşlem</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($serviceProviders as $serviceProvider)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $serviceProvider->name }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $serviceProvider->code ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                @if (!empty($serviceProvider->service_types))
                                    @php
                                        $labels = ['mail' => 'Mail', 'domain' => 'Domain', 'hosting' => 'Hosting', 'other' => 'Diğer'];
                                    @endphp
                                    {{ implode(', ', array_map(fn($t) => $labels[$t] ?? $t, $serviceProvider->service_types)) }}
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                <a href="{{ route('service-providers.edit', $serviceProvider) }}" class="text-slate-600 hover:text-slate-900 font-medium">Düzenle</a>
                                <form action="{{ route('service-providers.destroy', $serviceProvider) }}" method="POST" class="inline-block ml-3" onsubmit="return confirm('Bu servis sağlayıcıyı silmek istediğinize emin misiniz?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Sil</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">Henüz servis sağlayıcı eklenmemiş.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($serviceProviders->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                {{ $serviceProviders->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
