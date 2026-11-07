<x-app-layout>
    <x-flash-messages />
    @if ($errors->any())<div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <x-page-toolbar title="İş Takip">
        <x-slot name="right">
            <form method="POST" action="{{ route('work-tasks.detect') }}">@csrf<x-primary-button>Yenileme işlerini kontrol et</x-primary-button></form>
        </x-slot>
    </x-page-toolbar>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        @foreach (['open' => ['Açık işler', []], 'mine' => ['Bana atananlar', ['owner' => 'mine']], 'today' => ['Bugün yapılacaklar', ['timing' => 'today']], 'overdue' => ['Gecikenler', ['timing' => 'overdue']]] as $key => [$label, $filters])
            <a href="{{ route('work-tasks.index', $filters) }}" class="rounded-xl bg-white p-5 shadow-sm border border-gray-200 hover:border-slate-500">
                <p class="text-sm text-gray-600">{{ $label }}</p><p class="mt-2 text-2xl font-semibold">{{ $counts[$key] }}</p>
            </a>
        @endforeach
    </div>
    <form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-4 flex flex-wrap gap-3 items-end">
        <label class="text-sm">Ara<input name="search" value="{{ request('search') }}" placeholder="Müşteri veya iş" class="block rounded-md border-gray-300"></label>
        <label class="text-sm">İş türü<select name="type" class="block rounded-md border-gray-300"><option value="">Tümü</option><option value="subscription_renewal" @selected(request('type') === 'subscription_renewal')>Abonelik yenileme</option><option value="quote_follow_up" @selected(request('type') === 'quote_follow_up')>Teklif takibi</option></select></label>
        <label class="text-sm">Aşama<select name="status" class="block rounded-md border-gray-300"><option value="">Açık işler</option>@foreach (\App\Models\WorkTask::STATUSES as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></label>
        <label class="text-sm">Sorumlu<select name="owner" class="block rounded-md border-gray-300"><option value="">Tümü</option><option value="mine" @selected(request('owner') === 'mine')>Bana atananlar</option><option value="unassigned" @selected(request('owner') === 'unassigned')>Atanmamış</option>@foreach ($users as $user)<option value="{{ $user->id }}" @selected((string) request('owner') === (string) $user->id)>{{ $user->name }}</option>@endforeach</select></label>
        <label class="text-sm">Müşteri<select name="customer" class="block rounded-md border-gray-300"><option value="">Tümü</option>@foreach ($customers as $customer)<option value="{{ $customer->id }}" @selected(request('customer') == $customer->id)>{{ $customer->short_name ?: $customer->name }}</option>@endforeach</select></label>
        <label class="text-sm">Takip<select name="timing" class="block rounded-md border-gray-300"><option value="">Tümü</option><option value="today" @selected(request('timing') === 'today')>Bugün ve gecikenler</option><option value="overdue" @selected(request('timing') === 'overdue')>Gecikenler</option></select></label>
        <label class="text-sm"><input type="checkbox" name="closed" value="1" @checked(request('closed'))> Kapananları dahil et</label>
        <x-primary-button>Filtrele</x-primary-button><a href="{{ route('work-tasks.index') }}" class="text-sm underline">Temizle</a>
    </form>
    <form method="GET" action="{{ route('quotes.create') }}" x-data="workTaskSelection(@js($tasks->getCollection()->mapWithKeys(fn ($task) => [$task->id => ['customer' => (string) $task->customer_cari_id, 'name' => $task->customerCari?->short_name ?: $task->customerCari?->name]])))">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 11v6m0-10v.01"/></svg>
            <p>Aynı müşterinin <strong>fiyat kontrolü tamamlanan</strong> işlerini birlikte seçebilirsiniz.</p>
        </div>
        <button type="submit" name="type" value="firm" :disabled="selected.length === 0" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white disabled:opacity-40">Seçilenlerden kesin teklif oluştur <span x-text="selected.length ? '(' + selected.length + ')' : ''"></span></button>
    </div>
    <div x-show="selected.length" x-cloak class="mb-3 flex justify-end">
        <button type="button" @click="selected = []" class="text-sm font-semibold text-blue-700 underline">Seçimi temizle</button>
    </div>
    <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-600"><tr><th class="p-4">Seç</th><th class="p-4">Müşteri / abonelik</th><th class="p-4">İş / aşama</th><th class="p-4">Bitiş / geçerlilik</th><th class="p-4">Sonraki takip</th><th class="p-4">İşlem</th></tr></thead>
            <tbody>
                @forelse ($tasks as $task)
                    @php $days = (int) now('Europe/Istanbul')->startOfDay()->diffInDays(\Carbon\Carbon::parse($task->snapshot['end_date'] ?? $task->due_date), false); @endphp
                    <tr class="border-t border-gray-100" x-data="{ checked: @js((bool) $task->cost_checked_at), saved: false, taskId: '{{ $task->id }}', rowCustomerId: @js((string) $task->customer_cari_id), eligible: @js($task->type === \App\Models\WorkTask::TYPE_RENEWAL && $task->isOpen() && ! $task->hasBlockingQuote()) }"
                        :class="{ 'bg-blue-100': selected.includes(taskId), 'bg-blue-50': selected.length > 0 && matchesCustomer(rowCustomerId) && checked && eligible && !selected.includes(taskId) }" @pricing-saved.window="if ($event.detail.id === {{ $task->id }}) { checked = true; saved = true }">
                        <td class="p-4"><input type="checkbox" name="work_task_ids[]" value="{{ $task->id }}" x-model="selected" aria-label="İş #{{ $task->id }} seç" :disabled="!checked || !eligible || !matchesCustomer(rowCustomerId)" :title="!matchesCustomer(rowCustomerId) ? 'Yalnızca seçili müşteriyle aynı müşterinin işlerini seçebilirsiniz.' : ''" class="rounded border-gray-300 text-slate-600 disabled:opacity-40"></td>
                        <td class="p-4"><p class="font-semibold">{{ $task->customerCari?->short_name ?: $task->customerCari?->name }}</p><p class="text-gray-600">@if ($task->type === \App\Models\WorkTask::TYPE_QUOTE){{ $task->snapshot['item_count'] }} teklif kalemi @else{{ $task->snapshot['product_name'] ?? '—' }} · {{ $task->snapshot['quantity'] ?? '—' }} lisans @endif</p></td>
                        <td class="p-4"><p>{{ $task->title }}</p><div class="mt-2"><span x-show="!saved"><x-work-task-stage :status="$task->status" /></span><span x-show="saved" x-cloak><x-work-task-stage status="pricing" /></span></div></td>
                        <td class="p-4 whitespace-nowrap">{{ $task->due_date->format('d.m.Y') }}<p @class(['text-xs', 'text-red-700' => $days <= 3 && $task->isOpen(), 'text-gray-500' => $days > 3 || ! $task->isOpen()])>{{ $days < 0 ? abs($days).' gün geçti' : $days.' gün kaldı' }}</p></td>
                        <td class="p-4 whitespace-nowrap">{{ $task->next_follow_up?->format('d.m.Y') ?? '—' }}</td>
                        <td class="p-4 space-y-2">
                            @if ($task->type === \App\Models\WorkTask::TYPE_RENEWAL && $task->isOpen() && ! $task->cost_checked_at && ! $task->hasBlockingQuote())
                                <button type="button" x-show="!checked" @click="$dispatch('open-modal', 'pricing-{{ $task->id }}')" class="block rounded-md bg-slate-800 px-3 py-2 text-sm font-semibold text-white whitespace-nowrap">Fiyat kontrolü</button>
                            @endif
                            <a href="{{ route('work-tasks.show', $task) }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 whitespace-nowrap hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">İşi aç</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-8 text-center text-gray-500">Bu filtrelere uygun iş bulunmuyor.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </form>
    @foreach ($tasks as $task)
        @if ($task->type === \App\Models\WorkTask::TYPE_RENEWAL && $task->isOpen() && ! $task->cost_checked_at && ! $task->hasBlockingQuote())
            @include('work-tasks._pricing-modal')
        @endif
    @endforeach
    <div class="mt-4">{{ $tasks->links() }}</div>
</x-app-layout>
