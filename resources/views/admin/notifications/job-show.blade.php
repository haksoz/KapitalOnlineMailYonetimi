<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="İş #{{ $job->id }}">
        <x-slot name="left">
            <a href="{{ route('admin.notifications.jobs.index') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    @include('admin.notifications.partials.tabs')

    <div class="max-w-3xl space-y-4">
        <div class="bg-white rounded-xl shadow-sm p-5 space-y-2 text-sm">
            <p><span class="text-gray-500">Durum:</span> {{ $job->status?->label() }}</p>
            <p><span class="text-gray-500">Olay:</span> {{ $job->event_type?->label() }}</p>
            <p><span class="text-gray-500">Kural:</span> {{ $job->rule?->name }}</p>
            <p><span class="text-gray-500">Aksiyon:</span> {{ $job->action?->action_type?->label() }}</p>
            <p><span class="text-gray-500">Cari:</span> {{ $job->cari?->short_name ?: $job->cari?->name ?: '—' }}</p>
            <p>
                <span class="text-gray-500">Konu:</span>
                @if ($job->subjectUrl())
                    <a href="{{ $job->subjectUrl() }}" class="text-slate-700 underline">{{ $job->subjectLabel() }}</a>
                @else
                    {{ $job->subjectLabel() }}
                @endif
            </p>
            <p><span class="text-gray-500">E-posta:</span> {{ $job->to_email ?: '—' }}</p>
            <p><span class="text-gray-500">Hata:</span> {{ $job->error_message ?: '—' }}</p>
            <p><span class="text-gray-500">Plan:</span> {{ $job->scheduled_at?->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</p>
            <p><span class="text-gray-500">Çalışma:</span> {{ $job->executed_at?->timezone('Europe/Istanbul')->format('d.m.Y H:i') ?? '—' }}</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5 space-y-3">
            <h2 class="text-sm font-semibold text-gray-800">İşlemler</h2>
            <div class="flex flex-wrap gap-2">
                @if (in_array($job->status, [\App\Automation\JobStatus::Failed, \App\Automation\JobStatus::Skipped], true))
                    <form method="POST" action="{{ route('admin.notifications.jobs.retry', $job) }}">
                        @csrf
                        <x-primary-button>Tekrar dene</x-primary-button>
                    </form>
                @endif
                @if (in_array($job->status, [\App\Automation\JobStatus::Pending, \App\Automation\JobStatus::Failed], true))
                    <form method="POST" action="{{ route('admin.notifications.jobs.cancel', $job) }}">
                        @csrf
                        <x-secondary-button>İptal et</x-secondary-button>
                    </form>
                @endif
                <form method="POST" action="{{ route('admin.notifications.jobs.retrigger', $job) }}">
                    @csrf
                    <x-secondary-button>Manuel tekrar tetikle</x-secondary-button>
                </form>
            </div>
            @if ($job->status === \App\Automation\JobStatus::Pending)
                <form method="POST" action="{{ route('admin.notifications.jobs.reschedule', $job) }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    @method('PATCH')
                    <div>
                        <x-input-label for="scheduled_at" value="Yeni tarih" />
                        <x-text-input id="scheduled_at" name="scheduled_at" type="datetime-local" class="mt-1 block" :value="$job->scheduled_at?->timezone('Europe/Istanbul')->format('Y-m-d\TH:i')" required />
                    </div>
                    <x-primary-button>Tarihi değiştir</x-primary-button>
                </form>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="text-sm font-semibold text-gray-800 mb-2">Neden oluştu</h2>
            <pre class="text-xs bg-slate-50 border border-slate-200 rounded-lg p-3 overflow-x-auto">{{ json_encode($job->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <h2 class="text-sm font-semibold text-gray-800 px-5 py-3">Denemeler</h2>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Başlangıç</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Bitiş</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tetikleyen</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Hata</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($job->runs as $run)
                        <tr>
                            <td class="px-4 py-2 text-sm">{{ $run->status?->label() }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $run->started_at?->timezone('Europe/Istanbul')->format('d.m.Y H:i:s') }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $run->finished_at?->timezone('Europe/Istanbul')->format('d.m.Y H:i:s') }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $run->triggered_by?->label() }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $run->error_message ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">Henüz deneme yok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
