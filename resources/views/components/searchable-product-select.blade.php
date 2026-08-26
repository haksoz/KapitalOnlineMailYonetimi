@props([
    'products' => [],
    'selected' => null,
    'name' => 'product_id',
    'id' => 'product_id',
])

@php
    $productOptions = collect($products)->map(function ($p) {
        $currency = $p->currency ?? 'USD';
        $label = $p->name
            .($p->stock_code ? ' ('.$p->stock_code.')' : '')
            .' — '.($currency === 'TRY' ? 'TL' : 'USD');

        return [
            'id' => (int) $p->id,
            'label' => $label,
            'name' => $p->name,
            'stock_code' => $p->stock_code,
            'currency' => $currency,
            'alis_monthly_commitment' => $p->alis_usd_monthly_commitment,
            'satis_monthly_commitment' => $p->satis_usd_monthly_commitment,
            'alis_monthly_no_commitment' => $p->alis_usd_monthly_no_commitment,
            'satis_monthly_no_commitment' => $p->satis_usd_monthly_no_commitment,
            'alis_yearly_commitment' => $p->alis_usd_yearly_commitment,
            'satis_yearly_commitment' => $p->satis_usd_yearly_commitment,
        ];
    })->values();

    $selectedId = old($name, $selected);
@endphp

<div
    class="relative mt-1"
    x-data="{
        open: false,
        query: '',
        selectedId: @js($selectedId !== null && $selectedId !== '' ? (int) $selectedId : null),
        products: @js($productOptions),
        get selected() {
            return this.products.find(p => p.id === this.selectedId) || null;
        },
        get filtered() {
            const q = this.query.trim().toLocaleLowerCase('tr');
            if (!q) return this.products;
            return this.products.filter(p => {
                const hay = ((p.name || '') + ' ' + (p.stock_code || '') + ' ' + (p.currency || '') + ' ' + (p.currency === 'TRY' ? 'tl' : 'usd')).toLocaleLowerCase('tr');
                return hay.includes(q);
            });
        },
        select(product) {
            this.selectedId = product ? product.id : null;
            this.query = '';
            this.open = false;
            this.$nextTick(() => {
                this.$refs.hidden.dispatchEvent(new Event('change', { bubbles: true }));
                window.dispatchEvent(new CustomEvent('subscription-product-changed', {
                    detail: { product: this.selected }
                }));
            });
        },
        clear() {
            this.select(null);
        },
        displayLabel() {
            return this.selected ? this.selected.label : '— Seçin —';
        }
    }"
    @click.outside="open = false"
>
    <input type="hidden" x-ref="hidden" id="{{ $id }}" name="{{ $name }}" :value="selectedId ?? ''">

    <button
        type="button"
        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-left shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
        @click="open = !open; if (open) { $nextTick(() => $refs.search.focus()) }"
        :aria-expanded="open.toString()"
    >
        <span class="block truncate text-sm" :class="selectedId ? 'text-gray-900' : 'text-gray-500'" x-text="displayLabel()"></span>
    </button>

    <div
        x-show="open"
        x-cloak
        class="absolute z-30 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg"
    >
        <div class="border-b border-gray-100 p-2">
            <input
                type="search"
                x-ref="search"
                x-model="query"
                placeholder="Ürün adı, stok kodu veya TL/USD ara…"
                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                @keydown.escape.prevent="open = false"
                @keydown.enter.prevent="if (filtered.length) select(filtered[0])"
            >
        </div>
        <ul class="max-h-60 overflow-y-auto py-1" role="listbox">
            <li>
                <button type="button" class="w-full px-3 py-2 text-left text-sm text-gray-500 hover:bg-gray-50" @click="clear()">
                    — Seçin —
                </button>
            </li>
            <template x-for="product in filtered" :key="product.id">
                <li>
                    <button
                        type="button"
                        class="w-full px-3 py-2 text-left text-sm hover:bg-slate-50"
                        :class="selectedId === product.id ? 'bg-slate-100 font-medium text-slate-900' : 'text-gray-800'"
                        @click="select(product)"
                    >
                        <span class="block truncate" x-text="product.label"></span>
                    </button>
                </li>
            </template>
            <li x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-500">
                Eşleşen ürün yok.
            </li>
        </ul>
    </div>
</div>
