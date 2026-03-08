<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('sales-invoices.index') }}" class="text-gray-500 hover:text-gray-700">&larr;</a>
            <h1 class="text-xl font-semibold text-gray-800">Fatura bilgisi — #{{ $salesInvoice->id }}</h1>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="mb-4">
        <p class="text-sm text-gray-600">
            Müşteri: <strong>{{ $salesInvoice->customerCari?->short_name ?: $salesInvoice->customerCari?->name ?? '—' }}</strong>
            @if ($salesInvoice->total_amount_tl !== null)
                · Toplam: {{ number_format((float) $salesInvoice->total_amount_tl, 2, ',', '.') }} ₺
            @endif
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-lg">
        <form action="{{ route('sales-invoices.update-invoice-details', $salesInvoice) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="space-y-4">
                <div>
                    <x-input-label for="our_invoice_number" value="Fatura numarası (bizim)" />
                    <x-text-input id="our_invoice_number" name="our_invoice_number" type="text"
                        class="mt-1 block w-full" :value="old('our_invoice_number', $salesInvoice->our_invoice_number)"
                        required maxlength="64" />
                    <x-input-error :messages="$errors->get('our_invoice_number')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="our_invoice_date" value="Fatura tarihi" />
                    <x-text-input id="our_invoice_date" name="our_invoice_date" type="date"
                        class="mt-1 block w-full" :value="old('our_invoice_date', $salesInvoice->our_invoice_date?->format('Y-m-d'))"
                        required />
                    <x-input-error :messages="$errors->get('our_invoice_date')" class="mt-1" />
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded-lg font-semibold text-sm hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                    Kaydet
                </button>
                <a href="{{ route('sales-invoices.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                    İptal
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
