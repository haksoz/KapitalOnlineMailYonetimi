<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Servis Sağlayıcı Düzenle">
        <x-slot name="left">
            <a href="{{ route('service-providers.index') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-lg">
        <form action="{{ route('service-providers.update', $serviceProvider) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="space-y-4">
                <div>
                    <x-input-label for="name" value="Ad *" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $serviceProvider->name)" required autofocus />
                </div>
                <div>
                    <x-input-label for="code" value="Kod (kısa ad)" />
                    <x-text-input id="code" name="code" type="text" class="mt-1 block w-full lowercase" :value="old('code', $serviceProvider->code)" />
                    <p class="mt-1 text-xs text-gray-500">Örn: microsoft, hostinger, godaddy (benzersiz kısa kod).</p>
                </div>
                <div>
                    <x-input-label value="Hizmet Tipleri" />
                    @php $currentTypes = old('service_types', $serviceProvider->service_types ?? []); @endphp
                    <p class="mt-1 text-xs text-gray-500 mb-2">Bu sağlayıcının sunduğu hizmet türlerini işaretleyin (birden fazla seçilebilir).</p>
                    <div class="mt-2 space-y-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="service_types[]" value="mail" {{ in_array('mail', $currentTypes) ? 'checked' : '' }} class="rounded border-gray-300 text-slate-600 focus:ring-slate-500">
                            <span class="text-sm text-gray-700">Mail</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer block">
                            <input type="checkbox" name="service_types[]" value="domain" {{ in_array('domain', $currentTypes) ? 'checked' : '' }} class="rounded border-gray-300 text-slate-600 focus:ring-slate-500">
                            <span class="text-sm text-gray-700">Domain</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer block">
                            <input type="checkbox" name="service_types[]" value="hosting" {{ in_array('hosting', $currentTypes) ? 'checked' : '' }} class="rounded border-gray-300 text-slate-600 focus:ring-slate-500">
                            <span class="text-sm text-gray-700">Hosting</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer block">
                            <input type="checkbox" name="service_types[]" value="other" {{ in_array('other', $currentTypes) ? 'checked' : '' }} class="rounded border-gray-300 text-slate-600 focus:ring-slate-500">
                            <span class="text-sm text-gray-700">Diğer</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <x-primary-button>Güncelle</x-primary-button>
                <a href="{{ route('service-providers.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">İptal</a>
            </div>
        </form>
    </div>
</x-app-layout>
