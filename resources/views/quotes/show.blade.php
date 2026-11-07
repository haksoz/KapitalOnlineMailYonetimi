<x-app-layout>
    <x-flash-messages />
    @if ($quote->followUpTask)<div class="mb-3 rounded-lg bg-blue-50 p-3 text-sm"><a class="text-blue-700 underline" href="{{ route('work-tasks.show', $quote->followUpTask) }}">Teklif takibi · {{ $quote->followUpTask->statusLabel() }}</a></div>@endif
    @foreach ($quote->workTasks as $linkedTask)
        <div class="mb-3 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm"><a class="font-semibold text-blue-700 underline" href="{{ route('work-tasks.show', $linkedTask) }}">{{ $linkedTask->purposeLabel() }} · İş #{{ $linkedTask->id }}</a> · {{ $linkedTask->snapshot['product_name'] }} · {{ $linkedTask->snapshot['end_date'] }}</div>
    @endforeach

    <x-page-toolbar :title="$quote->quote_number">
        <x-slot name="left">
            <a href="{{ route('quotes.index') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
        <x-slot name="right">
            <a href="{{ route('quotes.customer', $quote) }}" class="inline-flex items-center justify-center min-h-[40px] px-3 py-2 bg-white border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50">Müşteri belgesi</a>
            @if ($quote->canRevise())
                <a href="{{ route('quotes.edit', $quote) }}" class="inline-flex items-center justify-center min-h-[40px] px-3 py-2 bg-slate-800 rounded-lg text-xs font-semibold text-white uppercase tracking-widest hover:bg-slate-700">Düzenle</a>
            @endif
            @if ($quote->canConvert())
                <a href="{{ route('quotes.convert', $quote) }}" class="inline-flex items-center justify-center min-h-[40px] px-3 py-2 bg-emerald-700 rounded-lg text-xs font-semibold text-white uppercase tracking-widest hover:bg-emerald-600">Kesin teklife dönüştür</a>
            @endif
        </x-slot>
    </x-page-toolbar>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-5 space-y-3">
            <div class="flex flex-wrap gap-2 text-sm">
                <span class="inline-flex px-2 py-1 rounded bg-slate-100 text-slate-800 font-medium">{{ \App\Models\Quote::typeLabel($quote->type) }}</span>
                <x-quote-status :status="$quote->status" />
                <span class="inline-flex px-2 py-1 rounded bg-gray-100 text-gray-700">{{ $quote->currencyLabel() }}</span>
            </div>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div>
                    <dt class="text-gray-500">Müşteri</dt>
                    <dd class="font-medium text-gray-900">{{ $quote->customerCari?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Geçerlilik</dt>
                    <dd class="font-medium text-gray-900">{{ $quote->valid_until?->format('d.m.Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Oluşturan</dt>
                    <dd class="font-medium text-gray-900">{{ $quote->creator?->name ?? '—' }}</dd>
                </div>
                @if ($quote->sent_at)
                    <div>
                        <dt class="text-gray-500">Gönderim</dt>
                        <dd class="font-medium text-gray-900">{{ $quote->sent_at->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</dd>
                    </div>
                @endif
                @if ($quote->isFirm())
                    <div>
                        <dt class="text-gray-500">KDV</dt>
                        <dd class="font-medium text-gray-900">%{{ \App\Services\QuoteMath::display($quote->vat_rate) }}</dd>
                    </div>
                @endif
                @if ($quote->sourceQuote)
                    <div>
                        <dt class="text-gray-500">Kaynak teklif</dt>
                        <dd><a href="{{ route('quotes.show', $quote->sourceQuote) }}" class="font-medium text-blue-600 hover:underline">{{ $quote->sourceQuote->quote_number }}</a></dd>
                    </div>
                @endif
                @if ($quote->derivedQuote)
                    <div>
                        <dt class="text-gray-500">Kesin teklif</dt>
                        <dd><a href="{{ route('quotes.show', $quote->derivedQuote) }}" class="font-medium text-blue-600 hover:underline">{{ $quote->derivedQuote->quote_number }}</a></dd>
                    </div>
                @endif
            </dl>
            @if ($quote->notes)
                <div>
                    <p class="text-xs text-gray-500">Müşteri notu</p>
                    <p class="text-sm text-gray-800 whitespace-pre-line">{{ $quote->notes }}</p>
                </div>
            @endif
            @if ($quote->internal_notes)
                <div class="rounded-lg bg-amber-50 border border-amber-100 p-3">
                    <p class="text-xs font-semibold text-amber-800">İç not</p>
                    <p class="text-sm text-amber-950 whitespace-pre-line">{{ $quote->internal_notes }}</p>
                </div>
            @endif
            @if ($quote->isOptional())
                <p class="text-xs text-gray-500">Taahhüt seçenekleri ayrı birim fiyatlanır. Adet kesin teklife geçerken girilir. KDV ve genel toplam kesin teklifte hesaplanır. Alış ve kâr yalnızca bu ekranda görünür.</p>
            @endif
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 space-y-2">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">İşlemler</p>
            @include('quotes._subscription-action')
            @if ($quote->canRevise())
                @php
                    $recipientEmails = $quote->customerCari?->notificationEmails() ?? [];
                    $selectedRecipients = old('_quote_send') ? (array) old('recipients', []) : $recipientEmails;
                    $recipientOptions = array_values(array_unique(array_merge($recipientEmails, array_filter($selectedRecipients, 'is_string'))));
                    $recipientErrors = collect($errors->getMessages())->filter(fn ($messages, $key) => $key === 'recipients' || str_starts_with($key, 'recipients.'))->flatten()->all();
                    $resend = $quote->status === \App\Models\Quote::STATUS_REJECTED;
                @endphp
                <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-sm hover:bg-gray-50" x-data x-on:click="$dispatch('open-modal', 'quote-send')">{{ $resend ? 'Tekrar gönder' : 'Gönder' }}</button>
                <x-modal name="quote-send" :show="count($recipientErrors) > 0" focusable>
                    <form method="POST" action="{{ route('quotes.send', $quote) }}" class="p-6"
                        x-data="quoteRecipients(@js(['addresses' => $recipientOptions, 'selected' => $selectedRecipients]))" @submit.prevent="send()">
                        @csrf
                        <input type="hidden" name="_quote_send" value="1">
                        <h2 class="text-lg font-medium text-gray-900">{{ $resend ? 'Teklifi tekrar gönder' : 'Teklifi gönder' }}</h2>
                        <p class="mt-1 text-sm text-gray-600">{{ $quote->customerCari?->name ?? 'Müşteri' }}</p>
                        <p class="mt-4 text-sm font-semibold text-gray-700">Alıcılar</p>
                        <div class="mt-2 space-y-2">
                            <template x-for="address in addresses" :key="address">
                                <label class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 text-sm">
                                    <input type="checkbox" name="recipients[]" :value="address" x-model="selected" class="rounded border-gray-300 text-slate-600 focus:ring-slate-500">
                                    <span class="break-all" x-text="address"></span>
                                </label>
                            </template>
                        </div>
                        <div class="mt-4">
                            <x-input-label for="quote-send-email" value="Bu gönderime özel e-posta ekle" />
                            <div class="mt-1 flex gap-2">
                                <x-text-input id="quote-send-email" type="email" maxlength="254" class="block w-full" x-ref="newEmail" x-model="newEmail" @keydown.enter.prevent="addEmail()" />
                                <x-secondary-button type="button" @click="addEmail()">Ekle</x-secondary-button>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">Eklenen adres cari kaydını değiştirmez.</p>
                        </div>
                        <p class="mt-2 text-sm text-red-700" role="alert" x-show="error" x-text="error"></p>
                        <x-input-error :messages="$recipientErrors" class="mt-2" />
                        <p class="mt-4 text-xs text-gray-500">«{{ \App\Automation\QuotePlaceholders::eventFor($quote)->label() }}» şablonu seçili adreslere gönderilir.</p>
                        <div class="mt-6 flex justify-end gap-3">
                            <x-secondary-button x-on:click="$dispatch('close')">Vazgeç</x-secondary-button>
                            <x-primary-button x-bind:disabled="sending">{{ $resend ? 'Tekrar gönder' : 'Gönder' }}</x-primary-button>
                        </div>
                    </form>
                </x-modal>
            @endif
            @if ($quote->isFirm() && in_array($quote->status, ['sent', 'partially_approved']) && $quote->items->contains('response_status', 'pending'))
                <p class="pt-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Müşteri yanıtı</p>
                <form method="POST" action="{{ route('quotes.approve', $quote) }}">@csrf<button class="w-full text-left px-3 py-2 rounded-lg text-sm font-semibold bg-green-100 text-green-800 hover:bg-green-200" type="submit">{{ $quote->items->count() > 1 ? 'Bekleyen tüm kalemleri onayla' : 'Onaylandı olarak işaretle' }}</button></form>
            @endif
            @if (in_array($quote->status, ['sent', 'partially_approved']) && $quote->items->contains('response_status', 'pending'))
                @if ($quote->isOptional())<p class="pt-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Müşteri yanıtı</p><p class="text-xs text-gray-600">Olumlu yanıtta kesin teklife dönüştürün.</p>@endif
                <form method="POST" action="{{ route('quotes.reject', $quote) }}">@csrf<button class="w-full text-left px-3 py-2 rounded-lg text-sm font-semibold bg-red-100 text-red-800 hover:bg-red-200" type="submit">{{ $quote->isFirm() && $quote->items->count() > 1 ? 'Bekleyen tüm kalemleri reddet' : 'Reddedildi olarak işaretle' }}</button></form>
                @if ($quote->status === 'sent')<form method="POST" action="{{ route('quotes.expire', $quote) }}">@csrf<button class="w-full text-left px-3 py-2 rounded-lg text-sm hover:bg-gray-50" type="submit">Süresi doldu</button></form>@endif
            @endif
            @if (in_array($quote->status, [\App\Models\Quote::STATUS_DRAFT, \App\Models\Quote::STATUS_SENT, \App\Models\Quote::STATUS_APPROVED], true))
                <form method="POST" action="{{ route('quotes.cancel', $quote) }}">@csrf<button class="w-full text-left px-3 py-2 rounded-lg text-sm text-red-700 hover:bg-red-50" type="submit">İptal et</button></form>
            @endif
            @if ($quote->isDraft())
                <form method="POST" action="{{ route('quotes.destroy', $quote) }}" onsubmit="return confirm('Taslak teklif silinsin mi?')">
                    @csrf
                    @method('DELETE')
                    <button class="w-full text-left px-3 py-2 rounded-lg text-sm text-red-700 hover:bg-red-50" type="submit">Sil</button>
                </form>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            @if ($quote->isFirm())
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-3 py-3 text-left">Ürün</th>
                            <th class="px-3 py-3 text-left">Taahhüt</th>
                            <th class="px-3 py-3 text-right">Adet</th>
                            <th class="px-3 py-3 text-right">Birim alış</th>
                            <th class="px-3 py-3 text-right">Birim satış</th>
                            <th class="px-3 py-3 text-right">Alış toplamı</th>
                            <th class="px-3 py-3 text-right">Satış toplamı</th>
                            <th class="px-3 py-3 text-right">Kâr</th>
                            <th class="px-3 py-3 text-right">Kâr oranı</th>
                            <th class="px-3 py-3 text-left">Müşteri yanıtı</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($quote->items as $item)
                            <tr>
                                <td class="px-3 py-3">
                                    <div class="font-medium text-gray-900">{{ $item->product_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $item->stock_code ?: '—' }}</div>
                                    @if ($item->work_task_id)<a class="text-xs text-blue-700 underline" href="{{ route('work-tasks.show', $item->work_task_id) }}">İş #{{ $item->work_task_id }}</a>@endif
                                </td>
                                <td class="px-3 py-3">{{ $item->commitmentLabel() }}</td>
                                <td class="px-3 py-3 text-right">{{ $item->quantity }}</td>
                                <td class="px-3 py-3 text-right">{{ $quote->formatMoney($item->birim_alis) }}</td>
                                <td class="px-3 py-3 text-right font-medium">{{ $quote->formatMoney($item->birim_satis) }}</td>
                                <td class="px-3 py-3 text-right">{{ $quote->formatMoney($item->costTotal()) }}</td>
                                <td class="px-3 py-3 text-right">{{ $quote->formatMoney($item->saleTotal()) }}</td>
                                <td class="px-3 py-3 text-right">{{ $quote->formatMoney($item->profitAmount()) }}</td>
                                <td class="px-3 py-3 text-right">{{ \App\Services\QuoteMath::displayRate($item->profitRate()) }}</td>
                                <td class="px-3 py-3 min-w-[200px]">
                                    @if ($item->response_status === 'pending' && in_array($quote->status, ['sent', 'partially_approved']))
                                        <form method="POST" action="{{ route('quotes.items.response', [$quote, $item]) }}" class="space-y-2">
                                            @csrf
                                            <input name="note" maxlength="5000" placeholder="Yanıt notu (isteğe bağlı)" aria-label="Kalem yanıt notu" class="w-full rounded-md border-gray-300 text-xs">
                                            <div class="flex gap-2"><button name="decision" value="approved" class="rounded bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">Onaylandı</button><button name="decision" value="rejected" class="rounded bg-red-100 px-2 py-1 text-xs font-semibold text-red-800">Reddedildi</button></div>
                                        </form>
                                    @else
                                        <x-quote-status :status="$item->isAccepted() ? 'approved' : ($item->response_status === 'rejected' ? 'rejected' : 'draft')" />
                                        @if ($item->response_note)<p class="mt-1 text-xs text-gray-600">{{ $item->response_note }}</p>@endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @php $summary = $quote->firmSummary(); @endphp
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 border-t border-gray-100 text-sm">
                    <div class="space-y-1">
                        <p>Alış toplamı: <span class="font-medium">{{ $quote->formatMoney($summary['cost']) }}</span></p>
                        <p>Kâr: <span class="font-medium">{{ $quote->formatMoney($summary['profit']) }}</span></p>
                        <p>Kâr oranı: <span class="font-medium">{{ \App\Services\QuoteMath::displayRate($summary['profit_rate']) }}</span></p>
                    </div>
                    <div class="space-y-1 sm:text-right">
                        <p>Ara toplam: <span class="font-medium">{{ $quote->formatMoney($summary['net']) }}</span></p>
                        <p>KDV: <span class="font-medium">{{ $quote->formatMoney($summary['vat']) }}</span></p>
                        <p>Genel toplam: <span class="font-semibold">{{ $quote->formatMoney($summary['gross']) }}</span></p>
                    </div>
                </div>
            @else
                @php
                    $showQuantity = $quote->items->contains(fn ($item) => $item->hasQuantity());
                @endphp
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-3 py-3 text-left">Ürün</th>
                            <th class="px-3 py-3 text-left">Taahhüt</th>
                            @if ($showQuantity)
                                <th class="px-3 py-3 text-right">Adet</th>
                            @endif
                            <th class="px-3 py-3 text-right">Birim alış</th>
                            <th class="px-3 py-3 text-right">Birim satış</th>
                            @if ($showQuantity)
                                <th class="px-3 py-3 text-right">Alış toplamı</th>
                                <th class="px-3 py-3 text-right">Satış tutarı</th>
                                <th class="px-3 py-3 text-right">Kâr</th>
                            @endif
                            <th class="px-3 py-3 text-right">Kâr oranı</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($quote->items as $item)
                            @foreach (\App\Models\Quote::COMMITMENTS as $tip)
                                @php $option = $item->options->firstWhere('taahhut_tipi', $tip); @endphp
                                @if ($option)
                                    <tr>
                                        <td class="px-3 py-3">
                                            <div class="font-medium text-gray-900">{{ $item->product_name }}</div>
                                            <div class="text-xs text-gray-500">{{ $item->stock_code ?: '—' }}</div>
                                        </td>
                                        <td class="px-3 py-3">{{ $option->commitmentLabel() }}</td>
                                        @if ($showQuantity)
                                            <td class="px-3 py-3 text-right">{{ $item->quantity ?? '—' }}</td>
                                        @endif
                                        <td class="px-3 py-3 text-right">{{ $quote->formatMoney($option->birim_alis) }}</td>
                                        <td class="px-3 py-3 text-right font-medium">{{ $quote->formatMoney($option->birim_satis) }}</td>
                                        @if ($showQuantity)
                                            <td class="px-3 py-3 text-right">{{ $item->hasQuantity() ? $quote->formatMoney($option->costTotal()) : '—' }}</td>
                                            <td class="px-3 py-3 text-right">{{ $item->hasQuantity() ? $quote->formatMoney($option->saleTotal()) : '—' }}</td>
                                            <td class="px-3 py-3 text-right">{{ $item->hasQuantity() ? $quote->formatMoney($option->profitAmount()) : '—' }}</td>
                                        @endif
                                        <td class="px-3 py-3 text-right">{{ \App\Services\QuoteMath::displayRate($option->profitRate()) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-app-layout>
