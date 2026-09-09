@props(['id', 'label'])

@php
    $safeId = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $id));
@endphp

<div class="pt-3">
    <button
        type="button"
        class="w-full flex items-center justify-between gap-2 min-h-[40px] px-3 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider rounded-lg hover:bg-slate-700/60 hover:text-slate-200 transition-colors"
        @click="toggleNavGroup('{{ $safeId }}')"
        :aria-expanded="navGroups.{{ $safeId }} ? 'true' : 'false'"
    >
        <span>{{ $label }}</span>
        <svg class="h-4 w-4 shrink-0 transition-transform duration-200" :class="{ 'rotate-90': navGroups.{{ $safeId }} }" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
    </button>
    <div x-show="navGroups.{{ $safeId }}" x-cloak class="mt-0.5 space-y-0.5" role="group" aria-label="{{ $label }}">
        {{ $slot }}
    </div>
</div>
