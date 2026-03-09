<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg sm:text-xl font-semibold text-gray-800 truncate">Tetikleyiciler</h1>
    </x-slot>

    <x-flash-messages />

    <div class="mb-4">
        <p class="text-sm text-gray-600">
            Sistemin otomatik yaptığı işlemleri elle tetikleyebilirsiniz. Tüm işlemler <strong>bugünün tarihi</strong> ({{ \Carbon\Carbon::parse($effectiveToday)->locale('tr')->translatedFormat('d F Y') }}) ile çalışır. Önce bitiş tarihlerini güncelle, ardından eksik dönemleri ekleyin.
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Tetikleyiciler</h2>
        <div class="flex flex-wrap gap-3">
            <form action="{{ route('triggers.run-renewals-up-to') }}" method="POST" class="inline" onsubmit="return confirm('Bitiş tarihi bugüne kadar geçmiş olan (otomatik yenilemeli) aboneliklerin bitiş tarihi bugünü geçene kadar uzatılacak. Devam?');">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-slate-700 text-white rounded-lg font-semibold text-sm hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                    Abonelik bitiş tarihlerini bugüne kadar güncelle
                </button>
            </form>
            <form action="{{ route('triggers.run-enqueue-missing') }}" method="POST" class="inline" onsubmit="return confirm('Bugüne kadar eksik kalan tüm dönemler için sipariş kayıtları eklenecek. Devam?');">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-slate-700 text-white rounded-lg font-semibold text-sm hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                    Bugüne kadar eksik dönemlerini (siparişleri) ekle
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
