<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 min-w-0">
            <h1 class="text-lg sm:text-xl font-semibold text-gray-800 truncate">Abonelik yenileme geçmişi</h1>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="mb-4">
        <p class="text-sm text-gray-600">
            Otomatik yenileme komutunun (<code class="text-xs bg-gray-100 px-1 rounded">subscriptions:process-renewals</code>) her çalıştırmasında buraya bir kayıt düşer. Cron veya scheduler ile tetiklenip tetiklenmediğini bu tablodan takip edebilirsiniz.
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Çalışma zamanı</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Baz tarih</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Yenilenen adet</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Yenilenen abonelikler</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                {{ $log->run_at->format('d.m.Y H:i:s') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                {{ $log->as_of_date ? $log->as_of_date->format('d.m.Y') : '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                {{ $log->renewed_count }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                @if ($log->renewed_ids && count($log->renewed_ids) > 0)
                                    @foreach ($log->renewed_ids as $id)
                                        <a href="{{ route('subscriptions.show', $id) }}" class="text-slate-600 hover:text-slate-900 font-medium">{{ $id }}</a>@if (! $loop->last), @endif
                                    @endforeach
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">
                                Henüz yenileme çalıştırması kaydı yok. Komut bir kez çalıştığında veya cron/scheduler tetiklediğinde burada görünecektir.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
