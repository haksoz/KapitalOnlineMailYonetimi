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
                <div>
                    <x-input-label for="invoice_total_net_tl" value="Kestiğin faturanın KDV hariç toplamı (TL)" />
                    <x-text-input id="invoice_total_net_tl" name="invoice_total_net_tl" type="number" step="0.01" min="0"
                        class="mt-1 block w-full"
                        :value="old('invoice_total_net_tl', $salesInvoice->invoice_total_net_tl)" />
                    <p class="mt-1 text-xs text-gray-500">
                        Bu alana gerçek faturadaki KDV hariç toplamı girersen, sistem kendi hesapladığı toplam ile farkı kaydeder.
                    </p>
                    <x-input-error :messages="$errors->get('invoice_total_net_tl')" class="mt-1" />
                    @if ($salesInvoice->invoice_total_diff_tl !== null)
                        <p class="mt-1 text-xs {{ (float) $salesInvoice->invoice_total_diff_tl === 0.0 ? 'text-emerald-700' : 'text-amber-700' }}">
                            Sistem toplamı ile fatura toplamı farkı:
                            <strong>{{ number_format((float) $salesInvoice->invoice_total_diff_tl, 2, ',', '.') }} ₺</strong>
                        </p>
                    @endif
                </div>
                @if ($salesInvoice->invoice_total_diff_tl !== null && (float) $salesInvoice->invoice_total_diff_tl !== 0.0)
                <div>
                    <x-input-label for="invoice_total_diff_reason" value="Bu fark neden kaynaklanıyor?" />
                    <x-text-input id="invoice_total_diff_reason" name="invoice_total_diff_reason" type="text"
                        class="mt-1 block w-full"
                        :value="old('invoice_total_diff_reason', $salesInvoice->invoice_total_diff_reason)" maxlength="255" />
                    <p class="mt-1 text-xs text-gray-500">
                        Örneğin: vade farkı, kur yuvarlama, iskonto, manuel düzeltme vb. Boş bırakabilirsin.
                    </p>
                    <x-input-error :messages="$errors->get('invoice_total_diff_reason')" class="mt-1" />
                </div>
                @endif
                <div>
                    <x-input-label value="Fatura Takip No (FTN)" />
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $salesInvoice->order_number ?? '—' }}</p>
                    <p class="mt-1 text-xs text-gray-500">Otomatik atanır (FTN000001, FTN000002, …). Faturaya bastığınızda bu numarayı kullanarak sistemdeki fatura ile eşleştirme yapabilirsiniz. Henüz atanmadıysa kaydettiğinizde atanacaktır.</p>
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
