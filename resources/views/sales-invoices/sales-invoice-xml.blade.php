<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('sales-invoices.index') }}" class="text-gray-500 hover:text-gray-700">&larr;</a>
            <h1 class="text-lg sm:text-xl font-semibold text-gray-800 truncate">Satış faturası XML eşleştir</h1>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="mb-4">
        <p class="text-sm text-gray-600">
            E-fatura XML’ini yükleyin. Sistem, <strong>fatura tarihi</strong> (dönem), <strong>müşteri VKN</strong> ve açıklamadaki <strong>*sözleşme no*</strong> ile fatura numarası henüz verilmemiş kayıtları listeler. KDV hariç toplamları karşılaştırıp doğru faturayı seçerek onaylayın.
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-lg">
        <form action="{{ route('sales-invoices.store-sales-invoice-xml') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="space-y-4">
                <div>
                    <x-input-label for="xml_file" value="XML dosyası" />
                    <input type="file" id="xml_file" name="xml_file" accept=".xml,application/xml,text/xml" class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                    <p class="mt-1 text-xs text-gray-500">İstersen XML dosyasını seçebilir veya aşağıya içeriğini yapıştırabilirsin.</p>
                    <x-input-error :messages="$errors->get('xml_file')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="xml_content" value="XML içeriği" />
                    <textarea
                        id="xml_content"
                        name="xml_content"
                        rows="8"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 text-sm font-mono"
                        placeholder="XML metnini buraya yapıştırabilirsin. Dosya seçiliyse dosya içeriği kullanılır."
                    >{{ old('xml_content') }}</textarea>
                    <x-input-error :messages="$errors->get('xml_content')" class="mt-1" />
                </div>
            </div>
            <div class="mt-6 flex flex-wrap gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded-lg font-semibold text-sm hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                    Yükle ve eşleşenleri listele
                </button>
                <a href="{{ route('sales-invoices.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                    İptal
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
