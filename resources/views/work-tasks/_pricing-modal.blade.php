@php
    $snapshot = $task->snapshot;
    $product = $task->subject instanceof \App\Models\Subscription ? $task->subject->product : null;
    $catalog = $product?->pricesForCommitment($snapshot['commitment']);
    $cost = $task->verified_cost ?? $catalog['alis'] ?? '';
    $sale = $task->proposed_sale ?? \App\Services\QuoteMath::saleKeepingProfit($cost, $snapshot['cost'], $snapshot['sale']) ?? $catalog['satis'] ?? '';
@endphp
<x-modal :name="'pricing-'.$task->id" focusable>
    <form method="POST" action="{{ route('work-tasks.update', $task) }}" class="p-6 space-y-4"
        x-data="renewalPricingModal(@js(['id' => $task->id, 'cost' => $cost, 'sale' => $sale, 'originalCost' => $snapshot['cost'], 'originalSale' => $snapshot['sale']]))"
        @submit.prevent="save()" role="dialog" aria-modal="true" aria-labelledby="pricing-title-{{ $task->id }}">
        @csrf @method('PATCH')
        <input type="hidden" name="action" value="pricing">
        <h2 id="pricing-title-{{ $task->id }}" class="text-lg font-semibold">Yeni dönem fiyat kontrolü</h2>
        <p class="text-sm text-gray-600">{{ $task->customerCari?->name }} · {{ $snapshot['product_name'] }} · {{ $snapshot['quantity'] }} lisans</p>
        <p class="text-sm">{{ \App\Models\Quote::commitmentLabel($snapshot['commitment']) }} · {{ $snapshot['currency'] }} · KDV hariç</p>
        <p class="text-sm text-gray-600">Mevcut alış: {{ \App\Services\QuoteMath::display($snapshot['cost']) }} · Mevcut satış: {{ \App\Services\QuoteMath::display($snapshot['sale']) }}</p>
        <div role="alert" x-show="error" x-cloak x-text="error" class="rounded-lg bg-red-50 p-3 text-sm text-red-800"></div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <label class="text-sm">Yeni alış<input name="verified_cost" type="number" min="0" step="0.0001" required x-model="cost" @input="calculateSale()" class="mt-1 block w-full rounded-md border-gray-300"></label>
            <label class="text-sm">Kâr marjı (%)<input type="number" min="-100" step="0.01" x-model="rate" @input="rateChanged()" class="mt-1 block w-full rounded-md border-gray-300"></label>
            <label class="text-sm">Yeni satış<input name="proposed_sale" type="number" min="0" step="0.01" required x-model="sale" @input="saleChanged()" @blur="roundSale()" class="mt-1 block w-full rounded-md border-gray-300"></label>
        </div>
        <button type="button" @click="useOriginalRate()" :disabled="originalMultiplier === null" class="text-sm text-blue-700 underline disabled:opacity-50">Mevcut kâr oranını uygula</button>
        <label class="block text-sm"><input name="cost_confirmed" type="checkbox" value="1" required> Alışı doğruladım; ürün alış fiyatını güncelle.</label>
        <p class="text-xs text-gray-500">Kaydettikten sonra bu işi listeden seçerek aynı müşterinin diğer işleriyle ortak teklif oluşturabilirsiniz.</p>
        <div class="flex justify-end gap-3">
            <button type="button" @click="$dispatch('close-modal', 'pricing-{{ $task->id }}')" :disabled="saving" class="rounded-md border px-4 py-2 text-sm">Kapat</button>
            <button type="submit" :disabled="saving" x-text="saving ? 'Kaydediliyor…' : 'Fiyat kontrolünü kaydet'" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Fiyat kontrolünü kaydet</button>
        </div>
    </form>
</x-modal>
