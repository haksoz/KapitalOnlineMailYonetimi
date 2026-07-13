<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Yeni Ürün">
        <x-slot name="left">
            <a href="{{ route('products.index') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-lg">
        <form action="{{ route('products.store') }}" method="POST">
            @csrf
            <div class="space-y-6">
                <div>
                    <x-input-label for="name" value="Ürün Adı *" class="text-base" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full text-xl font-semibold" :value="old('name')" required autofocus />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="stock_code" value="Stok kodu" />
                        <x-text-input id="stock_code" name="stock_code" type="text" class="mt-1 block w-full" :value="old('stock_code')" />
                    </div>
                    <div>
                        <x-input-label for="service_provider_id" value="Servis sağlayıcı" />
                        <select id="service_provider_id" name="service_provider_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">— Seçin —</option>
                            @foreach ($serviceProviders as $sp)
                                <option value="{{ $sp->id }}" @selected(old('service_provider_id') == $sp->id)>{{ $sp->name }} @if($sp->code)({{ $sp->code }})@endif</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                {{-- Aylık Taahhütlü --}}
                <div class="bg-white rounded-xl shadow-sm border border-blue-200 p-6">
                    <div class="flex items-center gap-2 mb-4 pb-2 border-b border-blue-100">
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-semibold">AYLIK TAAHÜTLÜ</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="alis_usd_monthly_commitment" value="Alış USD" />
                            <x-text-input id="alis_usd_monthly_commitment" name="alis_usd_monthly_commitment" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('alis_usd_monthly_commitment')" placeholder="0.00" />
                        </div>
                        <div>
                            <x-input-label for="satis_usd_monthly_commitment" value="Satış USD" />
                            <x-text-input id="satis_usd_monthly_commitment" name="satis_usd_monthly_commitment" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('satis_usd_monthly_commitment')" placeholder="0.00" />
                        </div>
                    </div>
                    <div class="mt-3 flex items-end gap-2">
                        <div class="w-40">
                            <x-input-label for="margin_monthly_commitment" value="Kar marjı (%)" />
                            <x-text-input id="margin_monthly_commitment" type="number" step="0.01" min="0" class="mt-1 block w-full" placeholder="0.00" />
                        </div>
                        <button type="button" onclick="applyMargin('alis_usd_monthly_commitment', 'margin_monthly_commitment', 'satis_usd_monthly_commitment')" class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 whitespace-nowrap">
                            Uygula
                        </button>
                    </div>
                </div>

                {{-- Aylık Taahhütsüz --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100">
                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-semibold">AYLIK TAAHÜTSÜZ</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="alis_usd_monthly_no_commitment" value="Alış USD" />
                            <x-text-input id="alis_usd_monthly_no_commitment" name="alis_usd_monthly_no_commitment" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('alis_usd_monthly_no_commitment')" placeholder="0.00" />
                        </div>
                        <div>
                            <x-input-label for="satis_usd_monthly_no_commitment" value="Satış USD" />
                            <x-text-input id="satis_usd_monthly_no_commitment" name="satis_usd_monthly_no_commitment" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('satis_usd_monthly_no_commitment')" placeholder="0.00" />
                        </div>
                    </div>
                    <div class="mt-3 flex items-end gap-2">
                        <div class="w-40">
                            <x-input-label for="margin_monthly_no_commitment" value="Kar marjı (%)" />
                            <x-text-input id="margin_monthly_no_commitment" type="number" step="0.01" min="0" class="mt-1 block w-full" placeholder="0.00" />
                        </div>
                        <button type="button" onclick="applyMargin('alis_usd_monthly_no_commitment', 'margin_monthly_no_commitment', 'satis_usd_monthly_no_commitment')" class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 whitespace-nowrap">
                            Uygula
                        </button>
                    </div>
                </div>

                {{-- Yıllık Taahhütlü --}}
                <div class="bg-white rounded-xl shadow-sm border border-emerald-200 p-6">
                    <div class="flex items-center gap-2 mb-4 pb-2 border-b border-emerald-100">
                        <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-xs font-semibold">YILLIK TAAHÜTLÜ</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="alis_usd_yearly_commitment" value="Alış USD" />
                            <x-text-input id="alis_usd_yearly_commitment" name="alis_usd_yearly_commitment" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('alis_usd_yearly_commitment')" placeholder="0.00" />
                        </div>
                        <div>
                            <x-input-label for="satis_usd_yearly_commitment" value="Satış USD" />
                            <x-text-input id="satis_usd_yearly_commitment" name="satis_usd_yearly_commitment" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('satis_usd_yearly_commitment')" placeholder="0.00" />
                        </div>
                    </div>
                    <div class="mt-3 flex items-end gap-2">
                        <div class="w-40">
                            <x-input-label for="margin_yearly_commitment" value="Kar marjı (%)" />
                            <x-text-input id="margin_yearly_commitment" type="number" step="0.01" min="0" class="mt-1 block w-full" placeholder="0.00" />
                        </div>
                        <button type="button" onclick="applyMargin('alis_usd_yearly_commitment', 'margin_yearly_commitment', 'satis_usd_yearly_commitment')" class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 whitespace-nowrap">
                            Uygula
                        </button>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <x-primary-button>Kaydet</x-primary-button>
                <a href="{{ route('products.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">İptal</a>
            </div>
        </form>
    </div>

    <script>
        function applyMargin(alisId, marginId, satisId) {
            const alis = parseFloat(document.getElementById(alisId).value);
            const margin = parseFloat(document.getElementById(marginId).value);
            if (isNaN(alis) || isNaN(margin)) {
                return;
            }
            const satis = alis * (1 + margin / 100);
            document.getElementById(satisId).value = satis.toFixed(2);
        }
    </script>
</x-app-layout>
