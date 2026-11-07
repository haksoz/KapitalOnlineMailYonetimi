<x-app-layout>
    <x-flash-messages />
    <x-page-toolbar :title="$task->title"><x-slot name="left"><a href="{{ route('work-tasks.index') }}" class="text-sm underline">İş Takip</a></x-slot></x-page-toolbar>
    @if ($errors->any())<div role="alert" class="mb-4 rounded-lg bg-red-50 p-4">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 space-y-5">
            <section class="bg-white rounded-xl shadow-sm p-5 space-y-4">
                <h2 class="font-semibold">{{ $task->customerCari?->name }}</h2>
                <x-work-task-stage :status="$task->status" />
                @if ($quote)
                <p><a href="{{ route('quotes.show', $quote) }}" class="text-blue-700 underline">{{ $quote->quote_number }} · Teklifi aç</a></p>
                <p class="text-sm">Geçerlilik: {{ $quote->valid_until?->format('d.m.Y') ?? 'Belirtilmedi' }} · Gönderim: {{ $quote->sent_at?->timezone('Europe/Istanbul')->format('d.m.Y H:i') ?? 'Bekleniyor' }}</p>
                <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead><tr class="text-left"><th class="p-3">Kalem</th><th class="p-3">Müşteri yanıtı</th><th class="p-3">Abonelik işlemi</th></tr></thead><tbody>
                @foreach ($quote->items as $item)
                    <tr class="border-t"><td class="p-3">{{ $item->product_name }}</td><td class="p-3">{{ ['pending' => 'Yanıt bekleniyor', 'approved' => 'Onaylandı', 'rejected' => 'Reddedildi'][$item->response_status] ?? $item->response_status }}@if ($item->response_note)<p>{{ $item->response_note }}</p>@endif</td><td class="p-3">{{ $item->subscriptionRecord ? 'Kaydedildi' : ($item->isAccepted() ? 'İşlem bekleniyor' : '—') }}</td></tr>
                @endforeach
                </tbody></table></div>
                @endif
            </section>
            <section class="bg-white rounded-xl shadow-sm p-5 space-y-3"><h2 class="font-semibold">İşlem geçmişi</h2>@foreach ($task->activities as $activity)<div class="border-b pb-3 text-sm"><p class="whitespace-pre-line">{{ $activity->description }}</p><p>{{ $activity->user?->name ?? 'Sistem' }} · {{ $activity->created_at->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</p></div>@endforeach</section>
        </div>
        <aside class="space-y-5">
            <section class="bg-white rounded-xl shadow-sm p-5 space-y-3"><h2 class="font-semibold">Sorumlu ve takip</h2><p class="text-sm">{{ $task->assignee?->name ?? 'Atanmamış' }} · Takip: {{ $task->next_follow_up?->format('d.m.Y') ?? '—' }}</p>
                @if ($task->isOpen())<form method="POST" action="{{ route('work-tasks.update', $task) }}" class="space-y-3">@csrf @method('PATCH')<input type="hidden" name="action" value="assign"><label class="block text-sm">Sorumlu<select name="assigned_to" class="block w-full rounded-md border-gray-300"><option value="">Atanmamış</option>@foreach ($users as $user)<option value="{{ $user->id }}" @selected($task->assigned_to === $user->id)>{{ $user->name }}</option>@endforeach</select></label><x-secondary-button type="submit">Ata</x-secondary-button></form>
                <form method="POST" action="{{ route('work-tasks.update', $task) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="assign"><input type="hidden" name="assigned_to" value="{{ auth()->id() }}"><x-secondary-button type="submit">İşi üstlen</x-secondary-button></form>@endif
            </section>
            @if ($task->isOpen())
                <section class="bg-white rounded-xl shadow-sm p-5 space-y-3"><h2 class="font-semibold">Not / sonraki takip</h2><form method="POST" action="{{ route('work-tasks.update', $task) }}" class="space-y-3">@csrf @method('PATCH')<input type="hidden" name="action" value="note"><label class="block text-sm">Takip tarihi<input type="date" name="next_follow_up" value="{{ old('next_follow_up', $task->next_follow_up?->toDateString()) }}" class="block w-full rounded-md border-gray-300"></label><label class="block text-sm">Not<textarea name="note" rows="3" class="block w-full rounded-md border-gray-300">{{ old('note') }}</textarea></label><x-primary-button>Kaydet</x-primary-button></form></section>
            @else<section class="bg-white rounded-xl shadow-sm p-5"><h2 class="font-semibold">Sonuç</h2><p class="mt-2 text-sm whitespace-pre-line">{{ $task->result }}</p></section>@endif
        </aside>
    </div>
</x-app-layout>
