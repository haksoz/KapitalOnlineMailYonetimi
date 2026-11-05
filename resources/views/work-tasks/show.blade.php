<x-app-layout>
    <x-flash-messages />
    <x-page-toolbar :title="'İş #'.$task->id.' · '.$task->title">
        <x-slot name="left"><a href="{{ route('work-tasks.index') }}" class="text-sm underline">İş Takip</a></x-slot>
    </x-page-toolbar>
    @if ($errors->any())<div role="alert" class="mb-4 rounded-lg bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @php
        $s = $task->snapshot;
        $money = fn ($value) => \App\Services\QuoteMath::display($value).' '.($s['currency'] === 'TRY' ? 'TL' : $s['currency']);
        $change = function ($old, $new) {
            if ($old === null || $new === null || (float) $old <= 0) return '—';
            return number_format((((float) $new / (float) $old) - 1) * 100, 2, ',', '.').'%';
        };
    @endphp
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 space-y-5">
            <section class="bg-white rounded-xl shadow-sm p-5 space-y-3">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $task->customerCari?->name }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $task->purposeLabel() }}</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $task->statusLabel() }}</span>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-3 text-sm">
                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                        <p><span class="text-gray-500">Dönem bitişi</span> <strong>{{ \Carbon\Carbon::parse($s['end_date'])->format('d.m.Y') }}</strong></p>
                        <p><span class="text-gray-500">Otomatik yenileme</span> <strong>{{ $s['auto_renew'] ? 'Açık' : 'Kapalı' }}</strong></p>
                    </div>
                    @if ($task->subject)<a href="{{ route('subscriptions.edit', $task->subject) }}" class="font-medium text-blue-700 hover:underline">Aboneliği düzenle &rarr;</a>@endif
                </div>
                @if ($task->subject && ($task->subject->bitis_tarihi?->toDateString() !== $s['end_date'] || $task->subject->auto_renew !== $s['auto_renew']))
                    <p class="text-xs text-gray-500">Güncel abonelik: {{ $task->subject->bitis_tarihi?->format('d.m.Y') ?? '—' }} · Otomatik yenileme {{ $task->subject->auto_renew ? 'açık' : 'kapalı' }}</p>
                @endif
            </section>
            <section class="bg-white rounded-xl shadow-sm p-5 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="font-semibold">Yeni dönem fiyat kontrolü</h2>
                    <span class="text-xs text-gray-500">{{ $s['currency'] === 'TRY' ? 'TL' : $s['currency'] }} · KDV hariç</span>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <h3 class="font-semibold text-gray-900">{{ $s['product_name'] }}</h3>
                    <div class="mt-2 flex flex-wrap gap-2 text-xs font-medium text-gray-700">
                        <span class="rounded-md border border-gray-200 bg-white px-2 py-1">{{ \App\Models\Quote::commitmentLabel($s['commitment']) }}</span>
                        <span class="rounded-md border border-gray-200 bg-white px-2 py-1">{{ $s['billing_period'] === 'yearly' ? 'Yıllık ödeme' : 'Aylık ödeme' }}</span>
                        <span class="rounded-md border border-gray-200 bg-white px-2 py-1">{{ $s['quantity'] }} lisans</span>
                    </div>
                </div>
                <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead><tr class="text-left border-b"><th class="p-2">Birim fiyat</th><th class="p-2">Mevcut dönem</th><th class="p-2">Katalog</th><th class="p-2">Yeni dönem</th><th class="p-2">Değişim</th></tr></thead><tbody>
                    <tr><th class="p-2 text-left">Alış</th><td class="p-2">{{ $money($s['cost']) }}</td><td class="p-2">{{ $money($catalog['alis'] ?? null) }}</td><td class="p-2">{{ $money($task->verified_cost) }}</td><td class="p-2">{{ $change($s['cost'], $task->verified_cost) }}</td></tr>
                    <tr><th class="p-2 text-left">Satış</th><td class="p-2">{{ $money($s['sale']) }}</td><td class="p-2">{{ $money($catalog['satis'] ?? null) }}</td><td class="p-2">{{ $money($task->proposed_sale) }}</td><td class="p-2">{{ $change($s['sale'], $task->proposed_sale) }}</td></tr>
                    <tr class="border-t border-gray-100"><th class="p-2 text-left">Kâr oranı</th><td class="p-2 font-semibold">{{ \App\Services\QuoteMath::displayRate($existingProfitRate) }}</td><td class="p-2">{{ \App\Services\QuoteMath::displayRate(\App\Services\QuoteMath::profitPercentage($catalog['alis'] ?? null, $catalog['satis'] ?? null)) }}</td><td class="p-2 font-semibold">{{ \App\Services\QuoteMath::displayRate(\App\Services\QuoteMath::profitPercentage($task->verified_cost, $task->proposed_sale)) }}</td><td class="p-2">—</td></tr>
                </tbody></table></div>
                @if ($task->cost_checked_at)
                    <div class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
                        <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                        <div>
                            <p class="text-sm font-semibold">Fiyat kontrolü tamamlandı</p>
                            <p class="mt-1 text-xs">{{ $task->costChecker?->name ?? '—' }} · {{ $task->cost_checked_at->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</p>
                        </div>
                    </div>
                @endif
                @if ($task->isOpen() && ! $task->hasBlockingQuote())
                    <form method="POST" action="{{ route('work-tasks.update', $task) }}" class="space-y-3"
                        x-data="renewalPricing(@js(['cost' => $initialCost, 'sale' => $initialSale, 'originalCost' => $s['cost'], 'originalSale' => $s['sale']]))">
                        @csrf @method('PATCH')<input type="hidden" name="action" value="pricing">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 border-t border-gray-100 pt-4">
                            <label class="text-sm">Yeni alış<input name="verified_cost" type="number" min="0" step="0.0001" required value="{{ $initialCost }}" x-model="cost" @input="calculateSale()" class="mt-1 block w-full rounded-md border-gray-300"></label>
                            <label class="text-sm">Kâr marjı (%)<input type="number" min="-100" step="0.01" x-model="rate" @input="rateChanged()" class="mt-1 block w-full rounded-md border-gray-300"></label>
                            <label class="text-sm">Yeni satış<input name="proposed_sale" type="number" min="0" step="0.01" required value="{{ $initialSale }}" x-model="sale" @input="saleChanged()" @blur="roundSale()" class="mt-1 block w-full rounded-md border-gray-300"></label>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm text-gray-600" x-show="suggestion !== null">Mevcut oranla satış: <strong x-text="suggestion === null ? '—' : format(suggestion)"></strong> {{ $s['currency'] === 'TRY' ? 'TL' : $s['currency'] }}</p>
                            <button type="button" @click="useOriginalRate()" :disabled="originalMultiplier === null" class="text-sm font-medium text-blue-700 hover:underline disabled:opacity-50">Mevcut kâr oranını uygula</button>
                        </div>
                        @if ($existingProfitRate === null)<p class="text-xs text-gray-500">Mevcut alış sıfır veya eksik; önceki kâr oranı hesaplanamıyor.</p>@endif
                        <label class="block text-sm"><input name="cost_confirmed" type="checkbox" value="1" required> Alışı doğruladım; ürün alış fiyatını güncelle.</label>
                        <x-primary-button>Fiyat kontrolünü kaydet</x-primary-button>
                    </form>
                @elseif ($task->isOpen())
                    <p class="text-xs text-gray-500">Devam eden teklif nedeniyle fiyat kontrolü kilitli.</p>
                @endif
            </section>
            <section class="bg-white rounded-xl shadow-sm p-5 space-y-4">
                <h2 class="font-semibold">Bağlı teklifler</h2>
                @if ($task->isOpen() && $task->cost_checked_at)
                    @if ($task->hasBlockingQuote())
                        <div class="flex flex-wrap gap-3"><button type="button" disabled class="rounded-md bg-gray-100 px-4 py-2 text-sm text-gray-400 cursor-not-allowed">Kesin teklif oluştur</button><button type="button" disabled class="rounded-md bg-gray-100 px-4 py-2 text-sm text-gray-400 cursor-not-allowed">Birim fiyat teklifi oluştur</button></div>
                        <p class="text-xs text-gray-500">Devam eden teklif var. Mevcut teklif üzerinden ilerleyin.</p>
                    @else
                    <div class="flex flex-wrap gap-3"><a href="{{ route('quotes.create', ['type' => 'firm', 'work_task_id' => $task->id]) }}" class="rounded-md bg-slate-800 px-4 py-2 text-sm text-white">Kesin teklif oluştur</a><a href="{{ route('quotes.create', ['type' => 'optional', 'work_task_id' => $task->id]) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm">Birim fiyat teklifi oluştur</a></div>
                    @endif
                @elseif ($task->isOpen())<p class="text-sm text-gray-600">Teklif oluşturmak için önce güncel alış fiyatını doğrulayın.</p>@endif
                @forelse ($task->quotes as $quote)
                    <div class="rounded-lg border border-gray-200 p-3 space-y-2">
                        <div class="flex flex-wrap items-center justify-between gap-2"><a href="{{ route('quotes.show', $quote) }}" class="font-semibold text-blue-700 underline">{{ $quote->quote_number }}</a><x-quote-status :status="$quote->status" /></div>
                        <p class="text-sm text-gray-600">{{ \App\Models\Quote::typeLabel($quote->type) }}@if ($quote->sent_at) · Gönderim: {{ $quote->sent_at->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}@endif</p>
                        @if ($quote->status === 'sent')<a href="{{ route('quotes.show', $quote) }}" class="inline-flex rounded-md bg-blue-100 px-3 py-2 text-sm font-semibold text-blue-800 hover:bg-blue-200">Müşteri yanıtını işle &rarr;</a>@endif
                        @include('quotes._subscription-action')
                    </div>
                @empty<p class="text-sm text-gray-500">Henüz teklif oluşturulmadı.</p>@endforelse
            </section>
            <section class="bg-white rounded-xl shadow-sm p-5 space-y-4"><h2 class="font-semibold">İşlem geçmişi</h2>@foreach ($task->activities as $activity)<div class="border-b border-gray-100 pb-3 text-sm"><p class="whitespace-pre-line">{{ $activity->description }}</p><p class="mt-1 text-xs text-gray-500">{{ $activity->user?->name ?? 'Sistem' }} · {{ $activity->created_at->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</p></div>@endforeach</section>
        </div>
        <aside class="space-y-5">
            <section class="bg-white rounded-xl shadow-sm p-5 space-y-3"><h2 class="font-semibold">Sorumlu ve takip</h2><p class="text-sm">{{ $task->assignee?->name ?? 'Atanmamış' }} · Takip: {{ $task->next_follow_up?->format('d.m.Y') ?? '—' }}</p>
                @if ($task->isOpen())<form method="POST" action="{{ route('work-tasks.update', $task) }}" class="space-y-3">@csrf @method('PATCH')<input type="hidden" name="action" value="assign"><label class="block text-sm">Sorumlu<select name="assigned_to" class="block w-full rounded-md border-gray-300"><option value="">Atanmamış</option>@foreach ($users as $user)<option value="{{ $user->id }}" @selected($task->assigned_to === $user->id)>{{ $user->name }}</option>@endforeach</select></label><x-secondary-button type="submit">Ata</x-secondary-button></form>
                <form method="POST" action="{{ route('work-tasks.update', $task) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="assign"><input type="hidden" name="assigned_to" value="{{ auth()->id() }}"><x-secondary-button type="submit">İşi üstlen</x-secondary-button></form>@endif
            </section>
            @if ($task->isOpen())
                <section class="bg-white rounded-xl shadow-sm p-5 space-y-3"><h2 class="font-semibold">Not / sonraki takip</h2><form method="POST" action="{{ route('work-tasks.update', $task) }}" class="space-y-3">@csrf @method('PATCH')<input type="hidden" name="action" value="note"><label class="block text-sm">Takip tarihi<input type="date" name="next_follow_up" value="{{ old('next_follow_up', $task->next_follow_up?->toDateString()) }}" class="block w-full rounded-md border-gray-300"></label><label class="block text-sm">Not<textarea name="note" rows="3" class="block w-full rounded-md border-gray-300">{{ old('note') }}</textarea></label><x-primary-button>Kaydet</x-primary-button></form></section>
                <section class="bg-white rounded-xl shadow-sm p-5 space-y-3"><h2 class="font-semibold">Aşama</h2><form method="POST" action="{{ route('work-tasks.update', $task) }}" class="space-y-3">@csrf @method('PATCH')<input type="hidden" name="action" value="stage"><label class="block text-sm">Yeni aşama<select name="status" class="block w-full rounded-md border-gray-300">@foreach (\App\Models\WorkTask::STATUSES as $value => $label)@if (!in_array($value, ['cost_pending', 'completed', 'cancelled']))<option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>@endif @endforeach</select></label><x-secondary-button type="submit">Aşamayı güncelle</x-secondary-button></form></section>
                <section class="bg-white rounded-xl shadow-sm p-5 space-y-3">
                    <h2 class="font-semibold">Bildirim ve sonuç</h2>
                    <form method="POST" action="{{ route('work-tasks.update', $task) }}" class="space-y-3">
                        @csrf @method('PATCH')
                        <label class="block text-sm">İşlem<select name="action" class="block w-full rounded-md border-gray-300"><option value="notify">Harici müşteri bildirimi yapıldı</option>@if ($task->type !== \App\Models\WorkTask::TYPE_RENEWAL)<option value="complete">Tamamlandı</option>@endif<option value="cancel">Yenilenmeyecek / iptal</option></select></label>
                        <label class="block text-sm">Açıklama<textarea name="note" rows="3" required class="block w-full rounded-md border-gray-300"></textarea></label>
                        <x-primary-button>Sonucu kaydet</x-primary-button>
                    </form>
                </section>
            @else<section class="bg-white rounded-xl shadow-sm p-5"><h2 class="font-semibold">Sonuç</h2><p class="mt-2 text-sm whitespace-pre-line">{{ $task->result }}</p></section>@endif
        </aside>
    </div>
</x-app-layout>
