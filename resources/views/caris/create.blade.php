<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Yeni Cari">
        <x-slot name="left">
            <a href="{{ route('caris.index') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-xl">
        <form action="{{ route('caris.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <x-input-label for="name" value="Ad / Ünvan *" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                </div>

                <div>
                    <x-input-label for="short_name" value="Kısa Ad / Ünvan" />
                    <x-text-input id="short_name" name="short_name" type="text" class="mt-1 block w-full" :value="old('short_name')" />
                    <p class="mt-1 text-xs text-gray-500">Tablolarda kullanılacak kısa ad. Boş bırakılırsa tam ad gösterilir.</p>
                </div>

                <div>
                    <x-input-label for="email" value="E-posta" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" />
                    <p class="mt-1 text-xs text-gray-500">Müşteri bildirimleri bu adrese gönderilebilir. İsteğe bağlı.</p>
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="country_code" value="Ülke Kodu" />
                        <x-text-input id="country_code" name="country_code" type="text" class="mt-1 block w-full uppercase" maxlength="2" :value="old('country_code', 'TR')" />
                        <p class="mt-1 text-xs text-gray-500">ISO 2 haneli ülke kodu (TR, DE, US vb.)</p>
                    </div>

                    <div>
                        <x-input-label for="tax_number" value="Vergi / Kimlik No" />
                        <x-text-input id="tax_number" name="tax_number" type="text" class="mt-1 block w-full" :value="old('tax_number')" />
                        <p class="mt-1 text-xs text-gray-500">VKN, VAT, EIN vb. boş bırakılabilir.</p>
                    </div>
                </div>

                <div>
                    <x-input-label for="cari_type" value="Cari Tipi" />
                    <select id="cari_type" name="cari_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        <option value="">Seçilmedi</option>
                        <option value="customer" @selected(old('cari_type') === 'customer')>Müşteri</option>
                        <option value="supplier" @selected(old('cari_type') === 'supplier')>Tedarikçi</option>
                        <option value="both" @selected(old('cari_type') === 'both')>Müşteri + Tedarikçi</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <x-primary-button>Kaydet</x-primary-button>
                <a href="{{ route('caris.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">İptal</a>
            </div>
        </form>
    </div>
</x-app-layout>

