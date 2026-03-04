<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('subscriptions.show', $subscription) }}" class="text-gray-500 hover:text-gray-700">&larr;</a>
            <h1 class="text-xl font-semibold text-gray-800">Ürün adeti güncelle</h1>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-md">
        <p class="text-sm text-gray-600 mb-4">Abonelik: <strong>{{ $subscription->sozlesme_no }}</strong>. Mevcut adet: <strong>{{ $subscription->quantity ?? 1 }}</strong>.</p>
        <form action="{{ route('subscriptions.update-quantity', $subscription) }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <x-input-label for="new_quantity" value="Yeni adet *" />
                    <x-text-input id="new_quantity" name="new_quantity" type="number" min="1" class="mt-1 block w-full" :value="old('new_quantity', $subscription->quantity ?? 1)" required />
                </div>
                <div>
                    <x-input-label for="effective_date" value="Tarih *" />
                    <x-text-input id="effective_date" name="effective_date" type="date" class="mt-1 block w-full" :value="old('effective_date', now()->format('Y-m-d'))" required />
                    <p class="mt-1 text-xs text-gray-500">İşlemin yapıldığı / geçerli olduğu tarih.</p>
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <x-primary-button>Güncelle</x-primary-button>
                <a href="{{ route('subscriptions.show', $subscription) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">İptal</a>
            </div>
        </form>
    </div>
</x-app-layout>
