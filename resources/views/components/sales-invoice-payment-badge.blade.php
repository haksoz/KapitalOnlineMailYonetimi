@props(['invoice'])

@if ($invoice->is_paid)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200']) }}>
        Ödendi
    </span>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 ring-1 ring-red-200']) }}>
        Ödenmedi
    </span>
@endif
