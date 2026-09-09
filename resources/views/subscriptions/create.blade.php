<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Yeni Abonelik">
        <x-slot name="left">
            <a href="{{ route('subscriptions.index') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-xl">
        <form action="{{ route('subscriptions.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <x-input-label for="customer_cari_id" value="Müşteri (Cari) *" />
                    <select id="customer_cari_id" name="customer_cari_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                        <option value="">— Seçin —</option>
                        @foreach ($customerCaris as $c)
                            <option value="{{ $c->id }}" @selected(old('customer_cari_id') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="provider_cari_id" value="Tedarikçi (Cari)" />
                    <select id="provider_cari_id" name="provider_cari_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        <option value="">— Seçin —</option>
                        @foreach ($providerCaris as $c)
                            <option value="{{ $c->id }}" @selected(old('provider_cari_id') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
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
                <div>
                    <x-input-label for="product_id" value="Ürün" />
                    <x-searchable-product-select :products="$products" :selected="old('product_id')" />
                    <p class="mt-1 text-xs text-gray-500">Yazarak ara; yeni ürünler üstte listelenir.</p>
                </div>
                <input type="hidden" id="currency" name="currency" value="{{ old('currency', 'USD') }}">
                <div>
                    <x-input-label value="Para birimi" />
                    <p id="currency_display" class="mt-1 text-sm font-medium text-gray-900">USD</p>
                    <p class="mt-1 text-xs text-gray-500">Üründen gelir; abonelikte sabitlenir.</p>
                </div>
                <div>
                    <x-input-label for="quantity" value="Ürün adeti *" />
                    <x-text-input id="quantity" name="quantity" type="number" min="1" class="mt-1 block w-full" :value="old('quantity', 1)" required />
                </div>
                <div>
                    <x-input-label for="sozlesme_no" value="Sözleşme no (tedarikçi abonelik no) *" />
                    <x-text-input id="sozlesme_no" name="sozlesme_no" type="text" class="mt-1 block w-full" :value="old('sozlesme_no')" required />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="taahhut_tipi" value="Taahhüt tipi *" />
                        <select id="taahhut_tipi" name="taahhut_tipi" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                            <option value="monthly_commitment" @selected(old('taahhut_tipi', 'monthly_commitment') === 'monthly_commitment')>Aylık taahhütlü</option>
                            <option value="monthly_no_commitment" @selected(old('taahhut_tipi') === 'monthly_no_commitment')>Aylık taahhütsüz</option>
                            <option value="annual_commitment" @selected(old('taahhut_tipi') === 'annual_commitment')>Yıllık taahhütlü</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="faturalama_periyodu" value="Faturalama periyodu *" />
                        <select id="faturalama_periyodu" name="faturalama_periyodu" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                            <option value="monthly" @selected(old('faturalama_periyodu', 'monthly') === 'monthly')>Aylık</option>
                            <option value="yearly" @selected(old('faturalama_periyodu') === 'yearly')>Yıllık</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="baslangic_tarihi" value="Başlangıç tarihi *" />
                        <x-text-input id="baslangic_tarihi" name="baslangic_tarihi" type="date" class="mt-1 block w-full" :value="old('baslangic_tarihi')" required />
                    </div>
                    <div>
                        <x-input-label for="bitis_tarihi" value="Bitiş tarihi" />
                        <x-text-input id="bitis_tarihi" name="bitis_tarihi" type="date" class="mt-1 block w-full" :value="old('bitis_tarihi')" />
                        <p id="bitis_tarihi_onerisi" class="mt-1 text-xs text-gray-500 hidden">Taahhüt tipi ve başlangıç tarihine göre öneri uygulanacak.</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="usd_birim_alis" id="label_birim_alis" value="USD birim alış (sabit)" />
                        <x-text-input id="usd_birim_alis" name="usd_birim_alis" type="number" step="0.0001" min="0" class="mt-1 block w-full" :value="old('usd_birim_alis')" placeholder="0,0000" />
                        <p id="hint_birim_alis" class="mt-1 text-xs text-gray-500">Abonelik oluşturulduğunda sabitlenir; beklenen TL hesabında kullanılır.</p>
                    </div>
                    <div>
                        <x-input-label for="usd_birim_satis" id="label_birim_satis" value="USD birim satış (sabit)" />
                        <x-text-input id="usd_birim_satis" name="usd_birim_satis" type="number" step="0.0001" min="0" class="mt-1 block w-full" :value="old('usd_birim_satis')" placeholder="0,0000" />
                        <p id="hint_birim_satis" class="mt-1 text-xs text-gray-500">Abonelik oluşturulduğunda sabitlenir.</p>
                    </div>
                </div>
                <div class="max-w-xs">
                    <x-input-label for="vat_rate" value="KDV (%) *" />
                    <x-text-input id="vat_rate" name="vat_rate" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('vat_rate', 20)" required />
                    <p class="mt-1 text-xs text-gray-500">Ön tanımlı %20; değiştirilebilir. Siparişlerde kullanılır.</p>
                </div>
                <div class="max-w-xs">
                    <x-input-label for="odeme_vadesi_gun" value="Ödeme vadesi (gün) *" />
                    <x-text-input id="odeme_vadesi_gun" name="odeme_vadesi_gun" type="number" min="0" max="3650" class="mt-1 block w-full" :value="old('odeme_vadesi_gun')" required />
                    <p class="mt-1 text-xs text-gray-500">Fatura tarihinden kaç gün sonra vade. 0 = fatura günü. Satış faturasına otomatik yazılır.</p>
                    <x-input-error :messages="$errors->get('odeme_vadesi_gun')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="durum" value="Durum *" />
                    <select id="durum" name="durum" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                        <option value="active" @selected(old('durum', 'active') === 'active')>Aktif</option>
                        <option value="cancelled" @selected(old('durum') === 'cancelled')>İptal</option>
                        <option value="pending" @selected(old('durum') === 'pending')>Beklemede</option>
                    </select>
                </div>
                <div>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="auto_renew" value="1" {{ old('auto_renew') ? 'checked' : '' }} class="rounded border-gray-300 text-slate-600 focus:ring-slate-500">
                        <span class="text-sm font-medium text-gray-700">Otomatik yenileme</span>
                    </label>
                    <p class="mt-1 text-xs text-gray-500">Açık ise ileride bitiş tarihi taahhüt tipine göre otomatik uzatılacak; kapalı ise abonelik bitişte sonlanacak.</p>
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <x-primary-button>Kaydet</x-primary-button>
                <a href="{{ route('subscriptions.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">İptal</a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var baslangic = document.getElementById('baslangic_tarihi');
            var bitis = document.getElementById('bitis_tarihi');
            var taahhut = document.getElementById('taahhut_tipi');
            var oneriText = document.getElementById('bitis_tarihi_onerisi');
            var currencyInput = document.getElementById('currency');
            var currencyDisplay = document.getElementById('currency_display');
            var alisInput = document.getElementById('usd_birim_alis');
            var satisInput = document.getElementById('usd_birim_satis');
            var selectedProduct = null;

            function updateCurrencyUi(currency) {
                var isTry = currency === 'TRY';
                var unit = isTry ? 'TL' : 'USD';
                currencyInput.value = currency;
                currencyDisplay.textContent = unit;
                document.getElementById('label_birim_alis').textContent = unit + ' birim alış (sabit)';
                document.getElementById('label_birim_satis').textContent = unit + ' birim satış (sabit)';
                document.getElementById('hint_birim_alis').textContent = isTry
                    ? 'Sabit TL; kur uygulanmaz. Destek hizmetinde alış 0 olabilir.'
                    : 'Abonelik oluşturulduğunda sabitlenir; beklenen TL hesabında kullanılır.';
                document.getElementById('hint_birim_satis').textContent = isTry
                    ? 'Sabit TL; dönem tutarı kurdan etkilenmez.'
                    : 'Abonelik oluşturulduğunda sabitlenir.';
            }

            function applyProductPrices(fillPrices) {
                if (!selectedProduct) {
                    updateCurrencyUi(currencyInput.value || 'USD');
                    return;
                }

                updateCurrencyUi(selectedProduct.currency || 'USD');

                if (!fillPrices) {
                    return;
                }

                var tip = taahhut.value;
                var alis = null;
                var satis = null;
                if (tip === 'monthly_no_commitment') {
                    alis = selectedProduct.alis_monthly_no_commitment;
                    satis = selectedProduct.satis_monthly_no_commitment;
                } else if (tip === 'annual_commitment') {
                    alis = selectedProduct.alis_yearly_commitment;
                    satis = selectedProduct.satis_yearly_commitment;
                } else {
                    alis = selectedProduct.alis_monthly_commitment;
                    satis = selectedProduct.satis_monthly_commitment;
                }

                if (alis !== null && alis !== '') {
                    alisInput.value = parseFloat(alis).toFixed(4);
                }
                if (satis !== null && satis !== '') {
                    satisInput.value = parseFloat(satis).toFixed(4);
                }
            }

            function suggestBitis() {
                var startVal = baslangic && baslangic.value;
                var tip = taahhut && taahhut.value;
                if (!startVal || !tip || !bitis) return;

                if (bitis.value) {
                    return;
                }
                var d = new Date(startVal + 'T12:00:00');
                if (isNaN(d.getTime())) return;
                if (tip === 'monthly_no_commitment') {
                    d.setMonth(d.getMonth() + 1);
                } else {
                    d.setFullYear(d.getFullYear() + 1);
                }
                var y = d.getFullYear();
                var m = String(d.getMonth() + 1).padStart(2, '0');
                var day = String(d.getDate()).padStart(2, '0');
                bitis.value = y + '-' + m + '-' + day;
                oneriText && oneriText.classList.remove('hidden') && (oneriText.textContent = 'Taahhüt tipine göre önerilen bitiş tarihi uygulandı. İsterseniz değiştirebilirsiniz.');
            }

            if (baslangic) baslangic.addEventListener('change', suggestBitis);
            if (taahhut) {
                taahhut.addEventListener('change', function () {
                    suggestBitis();
                    applyProductPrices(true);
                });
            }

            window.addEventListener('subscription-product-changed', function (event) {
                selectedProduct = event.detail && event.detail.product ? event.detail.product : null;
                applyProductPrices(true);
            });

            applyProductPrices(false);
        });
    </script>
</x-app-layout>
