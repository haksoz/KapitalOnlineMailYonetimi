@props(['settlement'])

@if ($settlement->is_closed)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200']) }}>
        Gider kapalı
    </span>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 ring-1 ring-red-200']) }}>
        Gider açık
    </span>
@endif
