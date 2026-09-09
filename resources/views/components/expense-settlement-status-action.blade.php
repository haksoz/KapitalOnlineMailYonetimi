@props(['settlement', 'variant' => 'link'])

@php
    $isClosed = (bool) $settlement->is_closed;
    $linkClass = $isClosed
        ? 'text-red-600 hover:text-red-800 font-medium'
        : 'text-emerald-700 hover:text-emerald-900 font-medium';
    $buttonClass = $isClosed
        ? 'inline-flex items-center px-3 py-2 text-xs font-semibold rounded-lg border border-red-300 text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2'
        : 'inline-flex items-center px-3 py-2 text-xs font-semibold rounded-lg border border-emerald-300 text-emerald-800 bg-white hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2';
    $iconClass = $isClosed
        ? 'inline-flex items-center justify-center w-8 h-8 rounded-md text-red-600 hover:text-red-800 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1 touch-manipulation'
        : 'inline-flex items-center justify-center w-8 h-8 rounded-md text-emerald-700 hover:text-emerald-900 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1 touch-manipulation';
    $label = $isClosed ? 'Gideri aç' : 'Gideri kapat';
    $class = match ($variant) {
        'button' => $buttonClass,
        'icon' => $iconClass,
        default => $linkClass,
    };
@endphp

@if ($isClosed)
    <form action="{{ route('expense-settlements.mark-open', $settlement) }}" method="POST" class="inline" onsubmit="return confirm('Bu gideri tekrar açık olarak işaretlemek istiyor musunuz?');">
        @csrf
        <button type="submit" class="{{ $class }}" title="{{ $label }}" aria-label="{{ $label }}">
            @if ($variant === 'icon')
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                </svg>
            @else
                {{ $label }}
            @endif
        </button>
    </form>
@else
    <form action="{{ route('expense-settlements.mark-closed', $settlement) }}" method="POST" class="inline">
        @csrf
        <button type="submit" class="{{ $class }}" title="{{ $label }}" aria-label="{{ $label }}">
            @if ($variant === 'icon')
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            @else
                {{ $label }}
            @endif
        </button>
    </form>
@endif
