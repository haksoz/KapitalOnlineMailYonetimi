<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Ürün Düzenle">
        <x-slot name="left">
            <a href="{{ route('products.index') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-lg">
        <form action="{{ route('products.update', $product) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="space-y-4">
                <div>
                    <x-input-label for="name" value="Ad *" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $product->name)" required autofocus />
                </div>
                <div>
                    <x-input-label for="stock_code" value="Stok kodu" />
                    <x-text-input id="stock_code" name="stock_code" type="text" class="mt-1 block w-full" :value="old('stock_code', $product->stock_code)" />
                </div>
                <div>
                    <x-input-label for="service_provider_id" value="Servis sağlayıcı" />
                    <select id="service_provider_id" name="service_provider_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        <option value="">— Seçin —</option>
                        @foreach ($serviceProviders as $sp)
                            <option value="{{ $sp->id }}" @selected(old('service_provider_id', $product->service_provider_id) == $sp->id)>{{ $sp->name }} @if($sp->code)({{ $sp->code }})@endif</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="description" value="Açıklama" />
                    <textarea id="description" name="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('description', $product->description) }}</textarea>
                </div>
                {{-- Aylık Taahhütlü --}}
                <div class="pt-3 border-t border-blue-100">
                    <p class="text-sm font-semibold text-blue-700 mb-2">Aylık Taahhütlü</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="alis_usd_monthly_commitment" value="Alış USD" />
                            <x-text-input id="alis_usd_monthly_commitment" name="alis_usd_monthly_commitment" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('alis_usd_monthly_commitment', $product->alis_usd_monthly_commitment)" placeholder="0.00" />
                        </div>
                        <div>
                            <x-input-label for="satis_usd_monthly_commitment" value="Satış USD" />
                            <x-text-input id="satis_usd_monthly_commitment" name="satis_usd_monthly_commitment" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('satis_usd_monthly_commitment', $product->satis_usd_monthly_commitment)" placeholder="0.00" />
                        </div>
                    </div>
                </div>

                {{-- Aylık Taahhütsüz --}}
                <div class="pt-3 border-t border-gray-100">
                    <p class="text-sm font-semibold text-gray-700 mb-2">Aylık Taahhütsüz</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="alis_usd_monthly_no_commitment" value="Alış USD" />
                            <x-text-input id="alis_usd_monthly_no_commitment" name="alis_usd_monthly_no_commitment" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('alis_usd_monthly_no_commitment', $product->alis_usd_monthly_no_commitment)" placeholder="0.00" />
                        </div>
                        <div>
                            <x-input-label for="satis_usd_monthly_no_commitment" value="Satış USD" />
                            <x-text-input id="satis_usd_monthly_no_commitment" name="satis_usd_monthly_no_commitment" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('satis_usd_monthly_no_commitment', $product->satis_usd_monthly_no_commitment)" placeholder="0.00" />
                        </div>
                    </div>
                </div>

                {{-- Yıllık Taahhütlü --}}
                <div class="pt-3 border-t border-emerald-100">
                    <p class="text-sm font-semibold text-emerald-700 mb-2">Yıllık Taahhütlü</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="alis_usd_yearly_commitment" value="Alış USD" />
                            <x-text-input id="alis_usd_yearly_commitment" name="alis_usd_yearly_commitment" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('alis_usd_yearly_commitment', $product->alis_usd_yearly_commitment)" placeholder="0.00" />
                        </div>
                        <div>
                            <x-input-label for="satis_usd_yearly_commitment" value="Satış USD" />
                            <x-text-input id="satis_usd_yearly_commitment" name="satis_usd_yearly_commitment" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('satis_usd_yearly_commitment', $product->satis_usd_yearly_commitment)" placeholder="0.00" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <x-primary-button>Güncelle</x-primary-button>
                <a href="{{ route('products.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">İptal</a>
            </div>
        </form>
    </div>
</x-app-layout>
