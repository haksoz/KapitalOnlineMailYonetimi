<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Alış faturası gir">
        <x-slot name="left">
            <a href="{{ route('pending-billings.index', ['status' => request('status', 'pending')]) }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    <div class="mb-4 p-3 bg-gray-50 rounded-lg text-sm text-gray-700">
        <p class="font-medium">{{ $pendingBilling->subscription->sozlesme_no }} — {{ $pendingBilling->period_start?->locale('tr')->translatedFormat('F Y') }}</p>
        <p class="text-gray-500">{{ $pendingBilling->subscription->customerCari?->name }} · {{ $pendingBilling->subscription->product?->name ?? '—' }}</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-lg">
        <form action="{{ route('pending-billings.store-supplier-invoice', $pendingBilling) }}" method="POST">
            @csrf
            <input type="hidden" name="status" value="{{ request('status', 'pending') }}">
            <div class="space-y-4">
                <div>
                    <x-input-label for="supplier_invoice_number" value="Alış fatura numarası *" />
                    <x-text-input id="supplier_invoice_number" name="supplier_invoice_number" type="text" class="mt-1 block w-full" :value="old('supplier_invoice_number', $pendingBilling->supplier_invoice_number)" required />
                    @error('supplier_invoice_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <x-input-label for="supplier_invoice_date" value="Fatura tarihi *" />
                    <x-text-input id="supplier_invoice_date" name="supplier_invoice_date" type="date" class="mt-1 block w-full" :value="old('supplier_invoice_date', $pendingBilling->supplier_invoice_date?->format('Y-m-d') ?? date('Y-m-d'))" required />
                    <p class="mt-1 text-xs text-gray-500">Adet güncellemesi yapılırsa geçerlilik tarihi olarak bu tarih kullanılır.</p>
                    @error('supplier_invoice_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <x-input-label for="quantity" value="Alış adeti *" />
                    <x-text-input id="quantity" name="quantity" type="number" min="1" class="mt-1 block w-full" :value="old('quantity', $pendingBilling->subscription->quantity)" required />
                    <p class="mt-1 text-xs text-gray-500">Farklıysa abonelik adeti fatura tarihi ile güncellenir.</p>
                    @error('quantity')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <x-input-label for="actual_alis_tl" value="Alış KDV hariç toplam (TL) *" />
                    <x-text-input id="actual_alis_tl" name="actual_alis_tl" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('actual_alis_tl', $pendingBilling->actual_alis_tl)" required />
                    @error('actual_alis_tl')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <x-primary-button type="submit">Kaydet</x-primary-button>
                <a href="{{ route('pending-billings.index', ['status' => request('status', 'pending')]) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">İptal</a>
            </div>
        </form>
    </div>
</x-app-layout>
