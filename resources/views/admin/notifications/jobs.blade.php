<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Bildirim Yönetimi">
        <x-slot name="left">
            <a href="{{ route('admin.mail-settings.edit') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    @include('admin.notifications.partials.tabs')

    <form method="GET" action="{{ route('admin.notifications.jobs.index') }}" class="mb-4 flex flex-wrap gap-3 items-end">
        <div>
            <x-input-label for="status" value="Durum" />
            <select id="status" name="status" class="mt-1 rounded-md border-gray-300 text-sm">
                <option value="">Tümü</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="event_type" value="Olay" />
            <select id="event_type" name="event_type" class="mt-1 rounded-md border-gray-300 text-sm">
                <option value="">Tümü</option>
                @foreach ($eventTypes as $type)
                    <option value="{{ $type->value }}" @selected($filters['event_type'] === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="cari_id" value="Cari" />
            <select id="cari_id" name="cari_id" class="mt-1 rounded-md border-gray-300 text-sm">
                <option value="">Tümü</option>
                @foreach ($caris as $cari)
                    <option value="{{ $cari->id }}" @selected((string) $filters['cari_id'] === (string) $cari->id)>{{ $cari->short_name ?: $cari->name }}</option>
                @endforeach
            </select>
        </div>
        <x-primary-button>Filtrele</x-primary-button>
    </form>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cari</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Konu</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Olay</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kural</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Oluşturma</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Çalışma</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($jobs as $job)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $job->cari?->short_name ?: $job->cari?->name ?: '—' }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if ($job->subjectUrl())
                                    <a href="{{ $job->subjectUrl() }}" class="text-slate-700 underline">{{ $job->subjectLabel() }}</a>
                                @else
                                    {{ $job->subjectLabel() }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $job->event_type?->label() }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $job->rule?->name }}</td>
                            <td class="px-4 py-3 text-sm">{{ $job->status?->label() }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">{{ $job->created_at?->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">{{ $job->scheduled_at?->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">{{ $job->executed_at?->timezone('Europe/Istanbul')->format('d.m.Y H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ route('admin.notifications.jobs.show', $job) }}" class="text-slate-600 hover:text-slate-900 font-medium">Detay</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-500">Kuyrukta iş yok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($jobs->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                {{ $jobs->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
