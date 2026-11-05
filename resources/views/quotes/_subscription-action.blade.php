@if ($quote->isFirm() && $quote->status === 'approved')
    @php $pendingItems = $quote->items->filter(fn ($item) => ! $item->subscriptionRecord); @endphp
    @if ($pendingItems->isNotEmpty())
        <button type="button" x-data x-on:click="$dispatch('open-modal', 'quote-subscription-{{ $quote->id }}')" class="inline-flex rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">Abonelik işlemini kaydet</button>
        <x-modal :name="'quote-subscription-'.$quote->id" maxWidth="2xl">
            <div class="p-6 space-y-5">
                <div class="flex items-center justify-between gap-3"><h2 class="text-lg font-semibold">Tedarikçide tamamlanan işlem</h2><button type="button" x-on:click="$dispatch('close')" class="text-sm underline">Kapat</button></div>
                @foreach ($pendingItems as $item)
                    <section class="rounded-lg border border-gray-200 p-4 space-y-3">
                        <h3 class="font-semibold">{{ $item->product_name }} · {{ $item->quantity }} lisans</h3>
                        <div class="flex flex-wrap gap-3">
                            @if ($quote->renewal_context && (int) $quote->renewal_context['product_id'] === (int) $item->product_id && \App\Models\Subscription::whereKey($quote->renewal_context['subscription_id'])->exists())
                                <a href="{{ route('subscriptions.edit', ['subscription' => $quote->renewal_context['subscription_id'], 'quote_item_id' => $item->id]) }}" class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-800">Mevcut abonelik yenilendi</a>
                            @endif
                            <a href="{{ route('subscriptions.create', ['quote_item_id' => $item->id]) }}" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">Yeni abonelik açıldı</a>
                        </div>
                    </section>
                @endforeach
            </div>
        </x-modal>
    @endif
    @foreach ($quote->items as $item)
        @if ($item->subscriptionRecord)
            <p class="text-sm text-green-800">{{ $item->product_name }} · {{ $item->subscriptionRecord->mode === 'new' ? 'Yeni abonelik' : 'Mevcut abonelik yenilendi' }}:
                @if ($item->subscriptionRecord->subscription)<a class="font-semibold underline" href="{{ route('subscriptions.show', $item->subscriptionRecord->subscription) }}">{{ $item->subscriptionRecord->contract_number }} · Aboneliği aç</a>@else{{ $item->subscriptionRecord->contract_number }}@endif
            </p>
        @endif
    @endforeach
@endif
