<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg sm:text-xl font-semibold text-gray-800 truncate">Simülasyon</h1>
    </x-slot>

    <x-flash-messages />

    <div class="mb-4">
        <p class="text-sm text-gray-600">
            Programın çalışırlığını test etmek için uygulamanın &quot;bugün&quot; dediği tarihi değiştirebilirsiniz.
            Simülasyon açıkken tüm sayfalar ve tetiklenecek işlemler bu tarihe göre çalışır. İşlemleri tetiklemek için aşağıdaki butonları kullanın.
        </p>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Simülasyon tarihi</h2>
            @if ($isActive)
                <p class="text-sm text-gray-700 mb-3">
                    <span class="font-medium">Etkin tarih:</span> {{ \Carbon\Carbon::parse($effectiveToday)->locale('tr')->translatedFormat('d F Y (l)') }}
                </p>
                <form action="{{ route('simulation.clear-date') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-amber-600 text-white rounded-lg font-semibold text-sm hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                        Simülasyonu kapat
                    </button>
                </form>
            @else
                <p class="text-sm text-gray-600 mb-3">Gerçek tarih kullanılıyor.</p>
            @endif
            <form action="{{ route('simulation.set-date') }}" method="POST" class="flex flex-wrap items-end gap-3 mt-4">
                @csrf
                <div>
                    <label for="simulation-date" class="block text-sm font-medium text-gray-700 mb-1">Tarih</label>
                    <input type="date" id="simulation-date" name="date" value="{{ old('date', $simulationDate ?? now()->format('Y-m-d')) }}" required
                           class="rounded-lg border-gray-300 shadow-sm focus:ring-slate-500 focus:border-slate-500 text-sm">
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded-lg font-semibold text-sm hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                    Ayarla
                </button>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Sistemin otomatik yaptıklarını tetikle</h2>
            <p class="text-sm text-gray-600 mb-4">
                Simülasyon açıksa işlemler simülasyon tarihine göre, kapalıysa gerçek bugüne göre çalışır.
            </p>
            <div class="flex flex-wrap gap-3">
                <form action="{{ route('simulation.run-enqueue') }}" method="POST" class="inline" onsubmit="return confirm('Dönem başı gelen abonelikler için sipariş kayıtları eklenecek. Devam?');">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-slate-700 text-white rounded-lg font-semibold text-sm hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                        Dönem başı siparişleri ekle
                    </button>
                </form>
                <form action="{{ route('simulation.run-renewals') }}" method="POST" class="inline" onsubmit="return confirm('Bitiş tarihi geçmiş aboneliklerin bitiş tarihi bir dönem ileri alınacak. Devam?');">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-slate-700 text-white rounded-lg font-semibold text-sm hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                        Abonelik yenilemelerini işle
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
