<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Teklifler">
        <x-slot name="right">
            <a href="{{ route('quotes.create', ['type' => 'optional']) }}" class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 bg-white border border-gray-300 rounded-lg font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition">
                Birim Fiyat Teklifi
            </a>
            <a href="{{ route('quotes.create', ['type' => 'firm']) }}" class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 bg-slate-800 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition">
                Kesin Teklif
            </a>
        </x-slot>
    </x-page-toolbar>

    <form method="GET" action="{{ route('quotes.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
        <div class="min-w-[220px] flex-1">
            <x-input-label for="search" value="Ara" />
            <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="request('search')" placeholder="Teklif no veya müşteri" />
        </div>
        <div class="min-w-[180px]">
            <x-input-label for="type" value="Tür" />
            <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                <option value="">— Tümü —</option>
                <option value="optional" @selected(request('type') === 'optional')>Birim Fiyat Teklifi</option>
                <option value="firm" @selected(request('type') === 'firm')>Kesin Teklif</option>
            </select>
        </div>
        <div class="min-w-[200px]">
            <x-input-label for="status" value="Durum" />
            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                <option value="">— Tümü —</option>
                @foreach (\App\Models\Quote::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ \App\Models\Quote::statusLabel($status) }}</option>
                @endforeach
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
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teklif</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Müşteri</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tür</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durum</th>
                        <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Kalem</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarih</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($quotes as $quote)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-3 whitespace-nowrap">
                                <a href="{{ route('quotes.show', $quote) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800 hover:underline">{{ $quote->quote_number }}</a>
                                <div class="text-xs text-gray-500">{{ $quote->currencyLabel() }}</div>
                            </td>
                            <td class="px-3 py-3 text-sm text-gray-900">{{ $quote->customerCari?->name ?? '—' }}</td>
                            <td class="px-3 py-3 text-sm text-gray-700">{{ \App\Models\Quote::typeLabel($quote->type) }}</td>
                            <td class="px-3 py-3 text-sm text-gray-700">
                                <div>{{ \App\Models\Quote::statusLabel($quote->status) }}</div>
                                @if ($quote->sent_at)
                                    <div class="text-xs text-gray-500 whitespace-nowrap">Gönderim {{ $quote->sent_at->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-sm text-right text-gray-700">{{ $quote->items_count }}</td>
                            <td class="px-3 py-3 text-sm text-gray-500 whitespace-nowrap">{{ $quote->created_at?->format('d.m.Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-sm text-gray-500">Henüz teklif yok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($quotes->hasPages())
            <div class="px-3 py-3 border-t border-gray-100">{{ $quotes->links() }}</div>
        @endif
    </div>
</x-app-layout>
