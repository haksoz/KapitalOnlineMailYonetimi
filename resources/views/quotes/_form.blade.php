<script type="application/json" id="quote-form-config">@json($formConfig)</script>

<form
    method="POST"
    action="{{ $isEdit ? route('quotes.update', $quote) : route('quotes.store') }}"
    class="bg-white rounded-xl shadow-sm p-6 max-w-6xl space-y-6"
    x-data="{
        type: 'optional',
        lockedType: false,
        vatRate: '20',
        products: [],
        lines: [],
        commitmentTypes: [],
        commitmentLabels: {},
        formError: '',
        init() {
            const config = JSON.parse(document.getElementById('quote-form-config').textContent);
            this.commitmentTypes = config.commitmentTypes;
            this.commitmentLabels = config.commitmentLabels;
            this.type = config.type;
            this.lockedType = config.lockedType;
            this.vatRate = config.vatRate === null || config.vatRate === undefined ? '' : String(config.vatRate);
            this.products = config.products;
            const sourceLines = config.lines && config.lines.length ? config.lines : [this.blankLine()];
            this.lines = sourceLines.map((line) => this.normalizeLine(line));
            this.$watch('type', (value) => {
                if (value === 'firm' && this.vatRate === '') {
                    this.vatRate = '20';
                }
            });
        },
        blankLine() {
            return this.normalizeLine({
                key: 'line-' + Date.now() + '-' + Math.random().toString(16).slice(2),
                id: '',
                product_id: '',
                quantity: 1,
                taahhut_tipi: this.commitmentTypes[0] || 'monthly_commitment',
                birim_satis: '',
                margin: '',
                options: {},
            });
        },
        normalizeLine(line) {
            const options = {};
            for (const tip of this.commitmentTypes) {
                const current = line.options && line.options[tip] ? line.options[tip] : {};
                options[tip] = {
                    enabled: !!current.enabled,
                    birim_satis: current.birim_satis == null ? '' : String(current.birim_satis),
                    margin: current.margin == null ? '' : String(current.margin),
                };
            }
            return Object.assign({}, line, {
                margin: line.margin == null ? '' : String(line.margin),
                options,
            });
        },
        profitRate(alis, satis) {
            const cost = Number(alis);
            const sale = Number(satis);
            if (!Number.isFinite(cost) || cost <= 0 || satis === '' || satis === null || satis === undefined || !Number.isFinite(sale)) {
                return null;
            }
            return Math.round((((sale - cost) / cost) * 100 + Number.EPSILON) * 100) / 100;
        },
        profitLabel(alis, satis) {
            const rate = this.profitRate(alis, satis);
            return rate === null ? '—' : this.format(rate) + '%';
        },
        profitClass(alis, satis) {
            const rate = this.profitRate(alis, satis);
            if (rate === null) return '';
            if (rate > 0) return 'text-green-700 font-medium';
            if (rate < 0) return 'text-red-700 font-medium';
            return 'font-medium';
        },
        saleFromMargin(alis, margin) {
            const cost = Number(alis);
            const rate = Number(margin);
            if (alis === '' || margin === '' || margin === null || !Number.isFinite(cost) || cost < 0 || !Number.isFinite(rate)) {
                return null;
            }
            return (Math.round((cost * (1 + rate / 100) + Number.EPSILON) * 100) / 100).toFixed(2);
        },
        applyOptionMargin(line, tip) {
            const sale = this.saleFromMargin(this.catalogAlis(line, tip), line.options[tip].margin);
            if (sale === null) return;
            line.options[tip].enabled = true;
            line.options[tip].birim_satis = sale;
        },
        applyFirmMargin(line) {
            const sale = this.saleFromMargin(this.catalogAlis(line, line.taahhut_tipi), line.margin);
            if (sale === null) return;
            line.birim_satis = sale;
        },
        productOf(line) {
            return this.products.find((product) => String(product.id) === String(line.product_id)) || null;
        },
        quoteCurrency(exceptLine) {
            for (const line of this.lines) {
                if (line === exceptLine) continue;
                const product = this.productOf(line);
                if (product) return product.currency;
            }
            return '';
        },
        currencyText() {
            const currency = this.quoteCurrency(null);
            if (!currency) return 'Ürün seçilince belirlenir';
            return currency === 'TRY' ? 'TL' : 'USD';
        },
        productsFor(line) {
            const currency = this.quoteCurrency(line);
            if (!currency) return this.products;
            return this.products.filter((product) => product.currency === currency || String(product.id) === String(line.product_id));
        },
        tipHasPrice(product, tip) {
            const satis = product?.prices?.[tip]?.satis;
            return satis !== null && satis !== undefined && satis !== '';
        },
        moneyInput(value) {
            const number = Number(value);
            return Number.isFinite(number) ? number.toFixed(2) : '';
        },
        format(value) {
            const number = Number(value);
            if (!Number.isFinite(number)) return '—';
            return new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(number);
        },
        lineTotal(unit, qty) {
            const amount = Number(unit);
            const quantity = parseInt(qty, 10);
            if (!Number.isFinite(amount) || !quantity) return null;
            return Math.round((amount * quantity + Number.EPSILON) * 100) / 100;
        },
        syncSelect(el, value) {
            const wanted = value == null || value === '' ? '' : String(value);
            this.$nextTick(() => {
                if (el.value !== wanted) el.value = wanted;
            });
        },
        onProductChange(line, value) {
            const next = value == null ? '' : String(value);
            if (String(line.product_id || '') === next) return;
            line.product_id = next;
            this.applyProduct(line);
        },
        onFirmTipChange(line, value) {
            const next = value == null ? '' : String(value);
            if (String(line.taahhut_tipi || '') === next) return;
            line.taahhut_tipi = next;
            this.applyFirmTip(line);
        },
        applyProduct(line) {
            const product = this.productOf(line);
            if (!product) return;
            const currency = this.quoteCurrency(line);
            if (currency && product.currency !== currency) {
                line.product_id = '';
                this.formError = 'Teklifteki tüm ürünler aynı para biriminde olmalıdır.';
                return;
            }
            this.formError = '';
            if (this.type === 'firm') {
                if (!this.tipHasPrice(product, line.taahhut_tipi)) {
                    const next = this.commitmentTypes.find((tip) => this.tipHasPrice(product, tip));
                    if (next) line.taahhut_tipi = next;
                }
                const price = product.prices[line.taahhut_tipi];
                line.birim_satis = price && price.satis != null && price.satis !== '' ? this.moneyInput(price.satis) : '';
            } else {
                for (const tip of this.commitmentTypes) {
                    const has = this.tipHasPrice(product, tip);
                    line.options[tip].enabled = has;
                    line.options[tip].birim_satis = has ? this.moneyInput(product.prices[tip].satis) : '';
                }
            }
        },
        applyFirmTip(line) {
            const product = this.productOf(line);
            if (!product) return;
            const price = product.prices[line.taahhut_tipi];
            line.birim_satis = price && price.satis != null && price.satis !== '' ? this.moneyInput(price.satis) : '';
        },
        catalogAlis(line, tip) {
            const alis = this.productOf(line)?.prices?.[tip]?.alis;
            if (alis === null || alis === undefined || alis === '') return '';
            return String(alis);
        },
        addLine() {
            this.lines.push(this.blankLine());
        },
        removeLine(index) {
            if (this.lines.length === 1) return;
            this.lines.splice(index, 1);
        },
        firmNet() {
            let net = 0;
            for (const line of this.lines) {
                const total = this.lineTotal(line.birim_satis, line.quantity);
                if (total !== null) net += total;
            }
            return Math.round(net * 100) / 100;
        },
        firmVat() {
            const rate = Number(this.vatRate);
            if (!Number.isFinite(rate)) return 0;
            let vat = 0;
            for (const line of this.lines) {
                const total = this.lineTotal(line.birim_satis, line.quantity);
                if (total === null) continue;
                vat += Math.round((total * rate + Number.EPSILON) * 100) / 100;
            }
            return Math.round(vat * 100) / 100;
        },
        firmGross() {
            return Math.round((this.firmNet() + this.firmVat()) * 100) / 100;
        }
    }"
>
    @csrf
    @if ($isEdit)
        @method('PATCH')
    @endif

    <div class="space-y-4">
        <div class="flex flex-col gap-4 rounded-lg border border-gray-200 p-4 sm:flex-row sm:items-start sm:justify-between">
            <section>
                <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Teklif bilgileri</h2>
                <div class="mt-3 space-y-3">
                    @if ($isEdit)
                        <p class="text-sm"><span class="text-gray-500">No</span> <span class="font-semibold text-gray-900">{{ $quote->quote_number }}</span></p>
                    @else
                        <p class="text-sm text-gray-500">Numara kayıt sırasında verilir.</p>
                    @endif
                    <div x-show="!lockedType" class="space-y-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="type" value="optional" x-model="type" class="rounded-full border-gray-300 text-slate-600 focus:ring-slate-500">
                            <span class="text-sm font-medium text-gray-800">Birim Fiyat Teklifi</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="type" value="firm" x-model="type" class="rounded-full border-gray-300 text-slate-600 focus:ring-slate-500">
                            <span class="text-sm font-medium text-gray-800">Kesin Teklif</span>
                        </label>
                    </div>
                    <p x-show="lockedType" class="text-sm font-medium text-gray-800" x-text="type === 'firm' ? 'Kesin Teklif' : 'Birim Fiyat Teklifi'"></p>
                    <div>
                        <x-input-label value="Para birimi" />
                        <p class="mt-1 text-sm font-medium text-gray-900" x-text="currencyText()"></p>
                    </div>
                    <template x-if="type === 'firm'">
                        <div>
                            <x-input-label for="vat_rate" value="KDV (%) *" />
                            <x-text-input id="vat_rate" name="vat_rate" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" x-model="vatRate" />
                        </div>
                    </template>
                </div>
            </section>
            <section class="sm:w-56 sm:shrink-0 sm:text-right">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Tarih bilgileri</h2>
                <div class="mt-3 space-y-3">
                    @if ($isEdit)
                        <p class="text-sm"><span class="text-gray-500">Tarih</span> {{ $quote->created_at?->timezone('Europe/Istanbul')->format('d.m.Y') }}</p>
                    @endif
                    <div>
                        <x-input-label for="valid_until" value="Geçerlilik tarihi" />
                        <x-text-input id="valid_until" name="valid_until" type="date" class="mt-1 block w-full" :value="old('valid_until', $quote?->valid_until?->format('Y-m-d'))" />
                    </div>
                </div>
            </section>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <section class="rounded-lg border border-gray-200 p-4">
            @include('quotes._seller')
        </section>
        <section
            class="rounded-lg border border-gray-200 p-4"
            x-data="{
                tax: '',
                email: '',
                sync(el) {
                    const option = el.selectedOptions[0];
                    this.tax = option && option.dataset.tax ? option.dataset.tax : '';
                    this.email = option && option.dataset.email ? option.dataset.email : '';
                }
            }"
            x-init="$nextTick(() => sync($refs.buyer))"
        >
            <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Alıcı bilgileri</h2>
            <div class="mt-3">
                <x-input-label for="customer_cari_id" value="Müşteri *" />
                <select id="customer_cari_id" name="customer_cari_id" x-ref="buyer" @change="sync($event.target)" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                    <option value="">— Seçin —</option>
                    @foreach ($customerCaris as $cari)
                        <option value="{{ $cari->id }}" data-tax="{{ $cari->tax_number }}" data-email="{{ $cari->email }}" @selected(old('customer_cari_id', $quote?->customer_cari_id) == $cari->id)>
                            {{ $cari->name }}@if($cari->short_name) ({{ $cari->short_name }})@endif
                        </option>
                    @endforeach
                </select>
            </div>
            <dl class="mt-3 space-y-1 text-sm text-gray-700">
                <div class="flex gap-2"><dt class="shrink-0 text-gray-500">Vergi no</dt><dd x-text="tax || '—'"></dd></div>
                <div class="flex gap-2"><dt class="shrink-0 text-gray-500">E-posta</dt><dd class="break-all" x-text="email || '—'"></dd></div>
            </dl>
        </section>
        </div>
    </div>

    <div>
        <x-input-label for="notes" value="Müşteri notu" />
        <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('notes', $quote?->notes) }}</textarea>
        <p class="mt-1 text-xs text-gray-500">Müşteri belgesinde görünür.</p>
    </div>
    <div>
        <x-input-label for="internal_notes" value="İç not" />
        <textarea id="internal_notes" name="internal_notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('internal_notes', $quote?->internal_notes) }}</textarea>
        <p class="mt-1 text-xs text-gray-500">Yalnızca iç ekranda görünür. Müşteri belgesine yazılmaz.</p>
    </div>

    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-gray-800">Kalemler</h2>
            <button type="button" class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50" @click="addLine()">Kalem ekle</button>
        </div>
        <p x-show="type === 'optional'" class="text-xs text-gray-500">Taahhüt seçenekleri ayrı fiyatlanır. Adet, her taahhütün tutarını göstermek içindir. KDV ve genel toplam kesin teklifte hesaplanır.</p>
        <p x-show="formError" x-text="formError" class="text-sm text-red-700"></p>

        <template x-for="(line, index) in lines" :key="line.key">
            <div class="border border-gray-200 rounded-xl p-4 space-y-4">
                <input type="hidden" :name="`items[${index}][id]`" :value="line.id">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold text-gray-500" x-text="'Kalem ' + (index + 1)"></p>
                    <button type="button" class="text-xs font-semibold text-red-700 hover:text-red-900" x-show="lines.length > 1" @click="removeLine(index)">Kaldır</button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Ürün *</label>
                        <select class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" :name="`items[${index}][product_id]`" x-effect="syncSelect($el, line.product_id)" @change="onProductChange(line, $event.target.value)">
                            <option value="" :selected="!line.product_id">— Seçin —</option>
                            <template x-for="product in productsFor(line)" :key="product.id">
                                <option :value="String(product.id)" :selected="String(product.id) === String(line.product_id)" x-text="product.label"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Adet *</label>
                        <input type="number" min="1" step="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" :name="`items[${index}][quantity]`" x-model="line.quantity">
                    </div>
                </div>

                <template x-if="type === 'firm'">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Taahhüt *</label>
                        <select class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" :name="`items[${index}][taahhut_tipi]`" x-effect="syncSelect($el, line.taahhut_tipi)" @change="onFirmTipChange(line, $event.target.value)">
                            <template x-for="tip in commitmentTypes" :key="tip">
                                <option :value="tip" :selected="tip === line.taahhut_tipi" x-text="commitmentLabels[tip]"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Birim satış *</label>
                        <input type="number" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" :name="`items[${index}][birim_satis]`" x-model="line.birim_satis">
                        <p class="mt-1 text-xs text-gray-500">
                            Katalog alış:
                            <span x-text="catalogAlis(line, line.taahhut_tipi) ? format(catalogAlis(line, line.taahhut_tipi)) : '—'"></span>
                            · Satış tutarı:
                            <span x-text="lineTotal(line.birim_satis, line.quantity) === null ? '—' : format(lineTotal(line.birim_satis, line.quantity))"></span>
                            · Kâr:
                            <span :class="profitClass(catalogAlis(line, line.taahhut_tipi), line.birim_satis)" x-text="profitLabel(catalogAlis(line, line.taahhut_tipi), line.birim_satis)"></span>
                        </p>
                        <div class="mt-2 flex items-end gap-2">
                            <div class="w-28">
                                <label class="block text-xs font-medium text-gray-600">Kar marjı (%)</label>
                                <input type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" x-model="line.margin">
                            </div>
                            <button type="button" class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50" @click="applyFirmMargin(line)">Uygula</button>
                        </div>
                    </div>
                </div>
                </template>

                <template x-if="type === 'optional'">
                <div class="space-y-3">
                    <template x-for="tip in commitmentTypes" :key="line.key + '-' + tip">
                        <div class="border border-gray-100 rounded-lg p-3 space-y-3">
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-800">
                                <input type="checkbox" value="1" class="rounded border-gray-300 text-slate-600 focus:ring-slate-500" :name="`items[${index}][options][${tip}][enabled]`" x-model="line.options[tip].enabled">
                                <span x-text="commitmentLabels[tip]"></span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600">Birim satış</label>
                                    <input type="number" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" :name="`items[${index}][options][${tip}][birim_satis]`" x-model="line.options[tip].birim_satis" :disabled="!line.options[tip].enabled">
                                </div>
                                <div class="flex items-end gap-2">
                                    <div class="w-28">
                                        <label class="block text-xs font-medium text-gray-600">Kar marjı (%)</label>
                                        <input type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" x-model="line.options[tip].margin">
                                    </div>
                                    <button type="button" class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50" @click="applyOptionMargin(line, tip)">Uygula</button>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500">
                                Katalog alış:
                                <span x-text="catalogAlis(line, tip) ? format(catalogAlis(line, tip)) : '—'"></span>
                                · Müşteri tutarı:
                                <span x-text="!line.options[tip].enabled || lineTotal(line.options[tip].birim_satis, line.quantity) === null ? '—' : format(lineTotal(line.options[tip].birim_satis, line.quantity))"></span>
                                · Kâr:
                                <span :class="profitClass(catalogAlis(line, tip), line.options[tip].birim_satis)" x-text="profitLabel(catalogAlis(line, tip), line.options[tip].birim_satis)"></span>
                            </p>
                        </div>
                    </template>
                </div>
                </template>
            </div>
        </template>
    </div>

    <div x-show="type === 'firm'" x-cloak class="rounded-lg bg-slate-50 border border-slate-200 p-4 text-sm text-slate-800 space-y-1">
        <p>Önizleme satış net: <span class="font-semibold" x-text="format(firmNet())"></span> <span x-text="currencyText()"></span></p>
        <p>Önizleme KDV: <span class="font-semibold" x-text="format(firmVat())"></span></p>
        <p>Önizleme genel toplam: <span class="font-semibold" x-text="format(firmGross())"></span></p>
        <p class="text-xs text-slate-500">Kayıt sırasında satır tutarı ve KDV 2 haneye yuvarlanır. Alış ve kâr müşteri belgesinde yer almaz.</p>
    </div>

    @if ($isEdit)
        <label class="inline-flex items-start gap-2 cursor-pointer">
            <input type="checkbox" name="refresh_catalog" value="1" class="mt-1 rounded border-gray-300 text-slate-600 focus:ring-slate-500" @checked(old('refresh_catalog'))>
            <span>
                <span class="block text-sm font-medium text-gray-800">Alış fiyatını ve ürün adını katalogdan yenile</span>
                <span class="block text-xs text-gray-500">İşaretlenmezse kayıtlı alış fiyatı korunur. Satış fiyatı formdaki değerle güncellenir.</span>
            </span>
        </label>
    @endif

    <div class="flex gap-3">
        <x-primary-button>Kaydet</x-primary-button>
        <a href="{{ $isEdit ? route('quotes.show', $quote) : route('quotes.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">Vazgeç</a>
    </div>
</form>
