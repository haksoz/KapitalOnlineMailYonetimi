<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Cariler">
        <x-slot name="right">
            <a href="{{ route('caris.create') }}" class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 bg-slate-800 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition w-full sm:w-auto touch-manipulation">
                Yeni Cari
            </a>
        </x-slot>
    </x-page-toolbar>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kısa Ad / Ünvan</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E-posta</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ülke</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vergi No</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cari Tipi</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">İşlem</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($caris as $cari)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $cari->short_name ?: $cari->name }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                @if ($cari->email)
                                    <a href="mailto:{{ $cari->email }}" class="text-slate-600 hover:text-slate-900">{{ $cari->email }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                {{ $cari->country_code ?? 'TR' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                {{ $cari->tax_number ?: '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                @php
                                    $labels = [
                                        'customer' => 'Müşteri',
                                        'supplier' => 'Tedarikçi',
                                        'both' => 'Müşteri + Tedarikçi',
                                    ];
                                @endphp
                                {{ $labels[$cari->cari_type] ?? '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                <a href="{{ route('caris.edit', $cari) }}" class="text-slate-600 hover:text-slate-900 font-medium">Düzenle</a>
                                <form action="{{ route('caris.destroy', $cari) }}" method="POST" class="inline-block ml-3" onsubmit="return confirm('Bu cariyi silmek istediğinize emin misiniz?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Sil</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">Henüz cari eklenmemiş.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($caris->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                {{ $caris->links() }}
            </div>
        @endif
    </div>
</x-app-layout>

