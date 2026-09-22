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

    <p class="text-sm text-gray-600 mb-4">Müşteri e-postası ve bildirim anahtarı. Kapalı cariye kuyruktaki e-posta aksiyonu atlanır.</p>

    <form method="GET" class="mb-4 max-w-md">
        <x-input-label for="search" value="Ara" />
        <div class="mt-1 flex gap-2">
            <x-text-input id="search" name="search" class="block w-full" :value="request('search')" />
            <x-primary-button>Ara</x-primary-button>
        </div>
    </form>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cari</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">E-posta</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bildirim</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Kaydet</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($caris as $cari)
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $cari->short_name ?: $cari->name }}</td>
                        <td class="px-4 py-3" colspan="3">
                            <form method="POST" action="{{ route('admin.notifications.caris.update', $cari) }}" class="flex flex-wrap items-center gap-3">
                                @csrf
                                @method('PATCH')
                                @if (request()->filled('search'))
                                    <input type="hidden" name="search" value="{{ request('search') }}">
                                @endif
                                <x-text-input name="email" type="email" class="block w-56" :value="old('email', $cari->email)" />
                                <input type="hidden" name="notifications_enabled" value="0">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" name="notifications_enabled" value="1" class="rounded border-gray-300 text-slate-600" @checked($cari->notifications_enabled)>
                                    Açık
                                </label>
                                <button type="submit" class="text-sm font-medium text-slate-700 hover:text-slate-900">Kaydet</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">Cari bulunamadı.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if ($caris->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                {{ $caris->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
