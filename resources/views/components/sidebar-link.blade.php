@props([
    'href',
    'active' => false,
    'closeOnClick' => false,
])

<a
    href="{{ $href }}"
    @class([
        'flex items-center min-h-[44px] px-3 py-2.5 text-sm font-medium rounded-lg transition-colors',
        'bg-slate-700 text-white' => $active,
        'text-gray-300 hover:bg-slate-700 hover:text-white' => ! $active,
    ])
    @if($closeOnClick) @click="sidebarOpen = false" @endif
>{{ $slot }}</a>
