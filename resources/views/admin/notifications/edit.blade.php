<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Bildirim Yönetimi">
        <x-slot name="left">
            <a href="{{ route('admin.mail-settings.edit') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation" aria-label="Mail yönetimine dön">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    @include('admin.notifications.partials.tabs')

    <div class="mb-4 max-w-3xl space-y-2">
        <p class="text-sm text-gray-600">
            Abonelik, sipariş ve fatura olayları aynı kural listesinden yönetilir.
            Mail yalnızca e-posta adresi dolu ve bildirimi açık carilere gider.
            Konu ve içerik metinleri <a href="{{ route('admin.notifications.templates.index') }}" class="text-slate-700 font-medium underline">Şablonlar</a> sekmesindedir.
        </p>
        <div class="text-xs text-gray-600 bg-amber-50 border border-amber-100 rounded-lg p-3">
            <p class="font-medium text-gray-700 mb-2">Aşamalar — aynı faturaya aynı anda yalnızca biri gider</p>
            <ol class="list-decimal list-inside space-y-1">
                <li><strong>Fatura vadesi yaklaşıyor</strong> — vadeden kuraldaki gün kadar önce başlar, vade gününe kadar.</li>
                <li><strong>Fatura vadesi geçti</strong> — vade ertesinden itibaren. Faiz kuralı aktifse, vade + {{ $interestStartDays }} gün dolduğu gün durur.</li>
                <li><strong>Faiz uygulaması ve kapatma</strong> — vade + {{ $interestStartDays }} günden sonra gecikmenin yerini alır. Bir kez gittiyse gecikme o faturaya bir daha gitmez.</li>
            </ol>
        </div>
    </div>

    <div class="max-w-3xl space-y-3">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-gray-800">Kurallar</h2>
            <div class="flex items-center gap-2">
                <form action="{{ route('admin.notifications.rules.reorder-process') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">Sürece göre sırala</button>
                </form>
                <a href="{{ route('admin.notifications.rules.create') }}" class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">Yeni kural</a>
            </div>
        </div>
        <p class="text-xs text-gray-500">Liste abonelik → sipariş → fatura sürecine göre durur. Oklarla yerini değiştirebilirsiniz.</p>
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Sıra</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ad</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Olay</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktif</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @php $previousGroup = null; @endphp
                    @forelse ($rules as $rule)
                        @php $group = $rule->event_type?->processGroup() ?? ''; @endphp
                        @if ($group !== '' && $group !== $previousGroup)
                            <tr class="bg-slate-50">
                                <td colspan="5" class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-600">{{ $group }} süreci</td>
                            </tr>
                            @php $previousGroup = $group; @endphp
                        @endif
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                <form action="{{ route('admin.notifications.rules.move', $rule) }}" method="POST" class="inline-flex items-center gap-1">
                                    @csrf
                                    <button
                                        type="submit"
                                        name="direction"
                                        value="up"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-40"
                                        @disabled($loop->first)
                                        aria-label="Yukarı taşı"
                                    >↑</button>
                                    <button
                                        type="submit"
                                        name="direction"
                                        value="down"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-40"
                                        @disabled($loop->last)
                                        aria-label="Aşağı taşı"
                                    >↓</button>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $rule->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $rule->event_type?->label() }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if ($rule->is_enabled)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Aktif</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Pasif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ route('admin.notifications.rules.edit', $rule) }}" class="text-slate-600 hover:text-slate-900 font-medium">Düzenle</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">Henüz kural yok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
