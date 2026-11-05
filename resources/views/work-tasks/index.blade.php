<x-app-layout>
    <x-flash-messages />
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
        <label class="text-sm">İş türü<select name="type" class="block rounded-md border-gray-300"><option value="">Tümü</option><option value="subscription_renewal" @selected(request('type') === 'subscription_renewal')>Abonelik yenileme</option></select></label>
        <label class="text-sm">Aşama<select name="status" class="block rounded-md border-gray-300"><option value="">Açık işler</option>@foreach (\App\Models\WorkTask::STATUSES as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></label>
        <label class="text-sm">Sorumlu<select name="owner" class="block rounded-md border-gray-300"><option value="">Tümü</option><option value="mine" @selected(request('owner') === 'mine')>Bana atananlar</option><option value="unassigned" @selected(request('owner') === 'unassigned')>Atanmamış</option>@foreach ($users as $user)<option value="{{ $user->id }}" @selected((string) request('owner') === (string) $user->id)>{{ $user->name }}</option>@endforeach</select></label>
        <label class="text-sm">Müşteri<select name="customer" class="block rounded-md border-gray-300"><option value="">Tümü</option>@foreach ($customers as $customer)<option value="{{ $customer->id }}" @selected(request('customer') == $customer->id)>{{ $customer->short_name ?: $customer->name }}</option>@endforeach</select></label>
        <label class="text-sm">Takip<select name="timing" class="block rounded-md border-gray-300"><option value="">Tümü</option><option value="today" @selected(request('timing') === 'today')>Bugün ve gecikenler</option><option value="overdue" @selected(request('timing') === 'overdue')>Gecikenler</option></select></label>
        <label class="text-sm"><input type="checkbox" name="closed" value="1" @checked(request('closed'))> Kapananları dahil et</label>
        <x-primary-button>Filtrele</x-primary-button><a href="{{ route('work-tasks.index') }}" class="text-sm underline">Temizle</a>
    </form>
    <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-600"><tr><th class="p-4">Müşteri / abonelik</th><th class="p-4">İş / aşama</th><th class="p-4">Dönem bitişi</th><th class="p-4">Sorumlu</th><th class="p-4">Sonraki takip</th><th class="p-4">İşlem</th></tr></thead>
            <tbody>
                @forelse ($tasks as $task)
                    @php $days = (int) now('Europe/Istanbul')->startOfDay()->diffInDays(\Carbon\Carbon::parse($task->snapshot['end_date'] ?? $task->due_date), false); @endphp
                    <tr class="border-t border-gray-100">
                        <td class="p-4"><p class="font-semibold">{{ $task->customerCari?->short_name ?: $task->customerCari?->name }}</p><p class="text-gray-600">{{ $task->snapshot['product_name'] ?? '—' }} · {{ $task->snapshot['quantity'] ?? '—' }} lisans</p></td>
                        <td class="p-4"><p>{{ $task->title }}</p><div class="mt-2"><x-work-task-stage :status="$task->status" /></div></td>
                        <td class="p-4 whitespace-nowrap">{{ $task->due_date->format('d.m.Y') }}<p @class(['text-xs', 'text-red-700' => $days <= 3 && $task->isOpen(), 'text-gray-500' => $days > 3 || ! $task->isOpen()])>{{ $days < 0 ? abs($days).' gün geçti' : $days.' gün kaldı' }}</p></td>
                        <td class="p-4">{{ $task->assignee?->name ?? 'Atanmamış' }}</td>
                        <td class="p-4 whitespace-nowrap">{{ $task->next_follow_up?->format('d.m.Y') ?? '—' }}</td>
                        <td class="p-4"><a href="{{ route('work-tasks.show', $task) }}" class="font-semibold text-blue-700 underline whitespace-nowrap">İşi aç</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-8 text-center text-gray-500">Bu filtrelere uygun iş bulunmuyor.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $tasks->links() }}</div>
</x-app-layout>
