<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Beklenen alışı düzelt — #{{ $pendingBilling->id }}">
        <x-slot name="left">
            <a href="{{ route('pending-billings.index', array_merge(request()->only('status', 'customer_cari_id', 'period_year', 'period_month', 'has_supplier_invoice', 'per_page'), ['status' => 'invoiced'])) }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-xl space-y-4">
        <div class="text-sm text-gray-600 space-y-1">
            <p>
                <span class="font-medium text-gray-800">Müşteri:</span>
                {{ $pendingBilling->subscription->customerCari?->short_name ?: $pendingBilling->subscription->customerCari?->name ?? '—' }}
            </p>
            <p>
                <span class="font-medium text-gray-800">Sözleşme / Ürün:</span>
                {{ $pendingBilling->subscription->sozlesme_no }}
                @if ($pendingBilling->subscription->product)
                    — {{ $pendingBilling->subscription->product->name }}
                @endif
            </p>
            <p>
                <span class="font-medium text-gray-800">Dönem:</span>
                {{ $pendingBilling->period_start?->locale('tr')->translatedFormat('F Y') ?? '—' }}
            </p>
            @if ($pendingBilling->salesInvoiceLine && $pendingBilling->salesInvoiceLine->salesInvoice)
                <p>
                    <span class="font-medium text-gray-800">Fatura:</span>
                    <a href="{{ route('sales-invoices.show', $pendingBilling->salesInvoiceLine->salesInvoice) }}" class="text-slate-600 hover:text-slate-900">
                        #{{ $pendingBilling->salesInvoiceLine->salesInvoice->id }}
                    </a>
                </p>
            @endif
        </div>

        <form action="{{ route('admin.pending-billings.update-expected-purchase', $pendingBilling) }}" method="POST" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <x-input-label for="expected_alis_tl" value="Beklenen alış (TL) *" />
                <x-text-input
                    id="expected_alis_tl"
                    name="expected_alis_tl"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('expected_alis_tl', $pendingBilling->expected_alis_tl)"
                    required
                    inputmode="decimal"
                />
                <p class="mt-1 text-xs text-gray-500">
                    Kaydettiğinizde beklenen satış, abonelikteki USD alış/satış oranına göre yeniden hesaplanır; gerekirse sipariş farkı da güncellenir.
                </p>
                <x-input-error :messages="$errors->get('expected_alis_tl')" class="mt-1" />
            </div>

            <div>
                <x-input-label value="Beklenen satış (TL) (kaydet sonrası güncellenir)" />
                <p class="mt-1 text-sm text-gray-700">
                    {{ $pendingBilling->expected_satis_tl !== null ? number_format((float) $pendingBilling->expected_satis_tl, 2, ',', '.') . ' ₺' : '—' }}
                </p>
            </div>

            <div class="mt-6 flex gap-3">
                <x-primary-button>Kaydet</x-primary-button>
                <a href="{{ route('pending-billings.index', array_merge(request()->only('status', 'customer_cari_id', 'period_year', 'period_month', 'has_supplier_invoice', 'per_page'), ['status' => 'invoiced'])) }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                    İptal
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
