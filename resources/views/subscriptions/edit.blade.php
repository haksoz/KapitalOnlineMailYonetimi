<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Abonelik Düzenle">
        <x-slot name="left">
            <a href="{{ route('subscriptions.show', $subscription) }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-xl">
        <form action="{{ route('subscriptions.update', $subscription) }}" method="POST" x-data="{
            endDate: '{{ old('bitis_tarihi', $subscription->bitis_tarihi?->format('Y-m-d')) }}',
            canEnableAutoRenew: true,
            recalcAutoRenew() {
                if (!this.endDate) {
                    this.canEnableAutoRenew = true;
                    return;
                }
                const d = new Date(this.endDate + 'T00:00:00');
                const today = new Date();
                today.setHours(0,0,0,0);
                this.canEnableAutoRenew = d.getTime() > today.getTime();
            },
            init() {
                this.recalcAutoRenew();
            }
        }" x-init="recalcAutoRenew()">
            @csrf
            @method('PATCH')
            <div class="space-y-4">
                <div>
                    <x-input-label for="customer_cari_id" value="Müşteri (Cari) *" />
                    <select id="customer_cari_id" name="customer_cari_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                        <option value="">— Seçin —</option>
                        @foreach ($customerCaris as $c)
                            <option value="{{ $c->id }}" @selected(old('customer_cari_id', $subscription->customer_cari_id) == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="provider_cari_id" value="Tedarikçi (Cari)" />
                    <select id="provider_cari_id" name="provider_cari_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        <option value="">— Seçin —</option>
                        @foreach ($providerCaris as $c)
                            <option value="{{ $c->id }}" @selected(old('provider_cari_id', $subscription->provider_cari_id) == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="service_provider_id" value="Servis sağlayıcı" />
                    <select id="service_provider_id" name="service_provider_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        <option value="">— Seçin —</option>
                        @foreach ($serviceProviders as $sp)
                            <option value="{{ $sp->id }}" @selected(old('service_provider_id', $subscription->service_provider_id) == $sp->id)>{{ $sp->name }} @if($sp->code)({{ $sp->code }})@endif</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="product_id" value="Ürün" />
                    <x-searchable-product-select :products="$products" :selected="old('product_id', $subscription->product_id)" />
                    <p class="mt-1 text-xs text-gray-500">Yazarak ara; yeni ürünler üstte listelenir.</p>
                </div>
                <div>
                    <x-input-label value="Para birimi" />
                    <p id="currency_display" class="mt-1 text-sm font-medium text-gray-900">{{ ($subscription->currency ?? 'USD') === 'TRY' ? 'TL' : 'USD' }}</p>
                    <p class="mt-1 text-xs text-gray-500">Abonelikte kilitlidir. Ürün değişirse yeni ürünün para birimi uygulanır.</p>
                </div>
                <div>
                    <x-input-label value="Ürün adeti" />
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $subscription->quantity ?? 1 }}</p>
                    <a href="{{ route('subscriptions.show-update-quantity', $subscription) }}" class="text-sm text-slate-600 hover:text-slate-900 font-medium">Adet güncelle</a>
                </div>
                <div>
                    <x-input-label for="sozlesme_no" value="Sözleşme no (tedarikçi abonelik no) *" />
                    <x-text-input id="sozlesme_no" name="sozlesme_no" type="text" class="mt-1 block w-full" :value="old('sozlesme_no', $subscription->sozlesme_no)" required />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="taahhut_tipi" value="Taahhüt tipi *" />
                        <select id="taahhut_tipi" name="taahhut_tipi" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                            <option value="monthly_commitment" @selected(old('taahhut_tipi', $subscription->taahhut_tipi) === 'monthly_commitment')>Aylık taahhütlü</option>
                            <option value="monthly_no_commitment" @selected(old('taahhut_tipi', $subscription->taahhut_tipi) === 'monthly_no_commitment')>Aylık taahhütsüz</option>
                            <option value="annual_commitment" @selected(old('taahhut_tipi', $subscription->taahhut_tipi) === 'annual_commitment')>Yıllık taahhütlü</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="faturalama_periyodu" value="Faturalama periyodu *" />
                        <select id="faturalama_periyodu" name="faturalama_periyodu" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                            <option value="monthly" @selected(old('faturalama_periyodu', $subscription->faturalama_periyodu) === 'monthly')>Aylık</option>
                            <option value="yearly" @selected(old('faturalama_periyodu', $subscription->faturalama_periyodu) === 'yearly')>Yıllık</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="baslangic_tarihi" value="Başlangıç tarihi *" />
                        <x-text-input id="baslangic_tarihi" name="baslangic_tarihi" type="date" class="mt-1 block w-full" :value="old('baslangic_tarihi', $subscription->baslangic_tarihi?->format('Y-m-d'))" required />
                    </div>
                    <div>
                        <x-input-label for="bitis_tarihi" value="Bitiş tarihi" />
                        <x-text-input id="bitis_tarihi" name="bitis_tarihi" type="date" class="mt-1 block w-full" x-model="endDate" @input="recalcAutoRenew()" :value="old('bitis_tarihi', $subscription->bitis_tarihi?->format('Y-m-d'))" />
                        <p id="bitis_tarihi_onerisi" class="mt-1 text-xs text-gray-500 hidden">Taahhüt tipi ve başlangıç tarihine göre öneri uygulanacak.</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="usd_birim_alis" id="label_birim_alis" :value="(($subscription->currency ?? 'USD') === 'TRY' ? 'TL birim alış (sabit)' : 'USD birim alış (sabit)')" />
                        <x-text-input id="usd_birim_alis" name="usd_birim_alis" type="number" step="0.0001" min="0" class="mt-1 block w-full" :value="old('usd_birim_alis', $subscription->usd_birim_alis)" placeholder="0,0000" />
                        <p class="mt-1 text-xs text-gray-500">Değiştirilmediği sürece abonelikte sabit kalır.</p>
                    </div>
                    <div>
                        <x-input-label for="usd_birim_satis" id="label_birim_satis" :value="(($subscription->currency ?? 'USD') === 'TRY' ? 'TL birim satış (sabit)' : 'USD birim satış (sabit)')" />
                        <x-text-input id="usd_birim_satis" name="usd_birim_satis" type="number" step="0.0001" min="0" class="mt-1 block w-full" :value="old('usd_birim_satis', $subscription->usd_birim_satis)" placeholder="0,0000" />
                        <p class="mt-1 text-xs text-gray-500">Değiştirilmediği sürece abonelikte sabit kalır.</p>
                    </div>
                </div>
                <div class="flex items-end gap-2">
                    <div class="w-40">
                        <x-input-label for="margin_percentage" value="Kar marjı (%)" />
                        <x-text-input id="margin_percentage" type="number" step="0.01" min="0" class="mt-1 block w-full" placeholder="0.00" />
                    </div>
                    <button type="button" onclick="applyMargin('usd_birim_alis', 'margin_percentage', 'usd_birim_satis')" class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 whitespace-nowrap">
                        Uygula
                    </button>
                </div>
                <div class="max-w-xs">
                    <x-input-label for="vat_rate" value="KDV (%) *" />
                    <x-text-input id="vat_rate" name="vat_rate" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('vat_rate', $subscription->vat_rate ?? 20)" required />
                    <p class="mt-1 text-xs text-gray-500">Siparişlerde kullanılır.</p>
                </div>
                <div>
                    <x-input-label for="durum" value="Durum *" />
                    <select id="durum" name="durum" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                        <option value="active" @selected(old('durum', $subscription->durum) === 'active')>Aktif</option>
                        <option value="cancelled" @selected(old('durum', $subscription->durum) === 'cancelled')>İptal</option>
                        <option value="pending" @selected(old('durum', $subscription->durum) === 'pending')>Beklemede</option>
                    </select>
                </div>
                <div>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            name="auto_renew"
                            value="1"
                            {{ old('auto_renew', $subscription->auto_renew) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-slate-600 focus:ring-slate-500"
                            :disabled="!canEnableAutoRenew"
                            @click="if (!canEnableAutoRenew) { $event.preventDefault(); }"
                        >
                        <span class="text-sm font-medium text-gray-700">Otomatik yenileme</span>
                    </label>
                    <p class="mt-1 text-xs text-gray-500">Açık ise ileride bitiş tarihi taahhüt tipine göre otomatik uzatılacak; kapalı ise abonelik bitişte sonlanacak.</p>
                    <p class="mt-1 text-xs text-amber-700" x-show="!canEnableAutoRenew">
                        Otomatik yenileme, bitiş tarihi geçmiş/bugün olan abonelikte açılamaz. Açmak için bitiş tarihini bugünden ileri bir güne güncelleyin.
                    </p>
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <x-primary-button>Güncelle</x-primary-button>
                <a href="{{ route('subscriptions.show', $subscription) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">İptal</a>
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
            document.getElementById(satisId).value = satis.toFixed(4);
        }

        document.addEventListener('DOMContentLoaded', function () {
            var baslangic = document.getElementById('baslangic_tarihi');
            var bitis = document.getElementById('bitis_tarihi');
            var taahhut = document.getElementById('taahhut_tipi');
            var oneriText = document.getElementById('bitis_tarihi_onerisi');

            function suggestBitis() {
                var startVal = baslangic && baslangic.value;
                var tip = taahhut && taahhut.value;
                if (!startVal || !tip || !bitis) return;

                // Kullanıcı zaten özel bir bitiş tarihi girdiyse, otomatik olarak değiştirmeyelim.
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
            if (taahhut) taahhut.addEventListener('change', suggestBitis);

            var currencyDisplay = document.getElementById('currency_display');
            var labelAlis = document.getElementById('label_birim_alis');
            var labelSatis = document.getElementById('label_birim_satis');
            var lockedCurrency = @json(($subscription->currency ?? 'USD') === 'TRY' ? 'TRY' : 'USD');

            function syncCurrencyUi(currency) {
                var unit = currency === 'TRY' ? 'TL' : 'USD';
                if (currencyDisplay) currencyDisplay.textContent = unit;
                if (labelAlis) labelAlis.textContent = unit + ' birim alış (sabit)';
                if (labelSatis) labelSatis.textContent = unit + ' birim satış (sabit)';
            }

            window.addEventListener('subscription-product-changed', function (event) {
                var product = event.detail && event.detail.product ? event.detail.product : null;
                syncCurrencyUi(product ? (product.currency || 'USD') : lockedCurrency);
            });

            syncCurrencyUi(lockedCurrency);
        });
    </script>
</x-app-layout>
