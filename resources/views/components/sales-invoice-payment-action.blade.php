@props(['invoice', 'variant' => 'link'])

@php
    $isPaid = (bool) $invoice->is_paid;
    $linkClass = $isPaid
        ? 'text-red-600 hover:text-red-800 font-medium'
        : 'text-emerald-700 hover:text-emerald-900 font-medium';
    $buttonClass = $isPaid
        ? 'inline-flex items-center px-3 py-2 text-xs font-semibold rounded-lg border border-red-300 text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2'
        : 'inline-flex items-center px-3 py-2 text-xs font-semibold rounded-lg border border-emerald-300 text-emerald-800 bg-white hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2';
    $iconClass = $isPaid
        ? 'inline-flex items-center justify-center w-8 h-8 rounded-md text-red-600 hover:text-red-800 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1 touch-manipulation'
        : 'inline-flex items-center justify-center w-8 h-8 rounded-md text-emerald-700 hover:text-emerald-900 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1 touch-manipulation';
    $label = $isPaid ? 'Ödenmedi işaretle' : 'Ödendi işaretle';
    $class = match ($variant) {
        'button' => $buttonClass,
        'icon' => $iconClass,
        default => $linkClass,
    };
@endphp

@if ($isPaid)
    <form action="{{ route('sales-invoices.mark-unpaid', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('Bu faturayı ödenmedi olarak işaretlemek istiyor musunuz?');">
        @csrf
        <button type="submit" class="{{ $class }}" title="{{ $label }}" aria-label="{{ $label }}">
            @if ($variant === 'icon')
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            @else
                {{ $label }}
            @endif
        </button>
    </form>
@else
    <form action="{{ route('sales-invoices.mark-paid', $invoice) }}" method="POST" class="inline">
        @csrf
        <button type="submit" class="{{ $class }}" title="{{ $label }}" aria-label="{{ $label }}">
            @if ($variant === 'icon')
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            @else
                {{ $label }}
            @endif
        </button>
    </form>
@endif
