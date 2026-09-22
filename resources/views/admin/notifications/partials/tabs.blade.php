@php
    $tabs = [
        ['route' => 'admin.notifications.edit', 'label' => 'Kurallar', 'active' => request()->routeIs('admin.notifications.edit', 'admin.notifications.rules.*')],
        ['route' => 'admin.notifications.templates.index', 'label' => 'Şablonlar', 'active' => request()->routeIs('admin.notifications.templates.*')],
        ['route' => 'admin.notifications.jobs.index', 'label' => 'Kuyruk', 'active' => request()->routeIs('admin.notifications.jobs.*')],
        ['route' => 'admin.notifications.caris.index', 'label' => 'Cari ayarları', 'active' => request()->routeIs('admin.notifications.caris.*')],
    ];
@endphp
<nav class="mb-4 flex flex-wrap gap-2" aria-label="Bildirim bölümleri">
    @foreach ($tabs as $tab)
        <a
            href="{{ route($tab['route']) }}"
            class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold uppercase tracking-widest {{ $tab['active'] ? 'bg-slate-700 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}"
        >
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
