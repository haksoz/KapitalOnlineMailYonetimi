@props(['status'])

@php
    [$number, $color] = match ($status) {
        'cost_pending' => ['1', 'border-amber-200 bg-amber-100 text-amber-800'],
        'pricing' => ['2', 'border-orange-200 bg-orange-100 text-orange-800'],
        'notification_pending' => ['3', 'border-cyan-200 bg-cyan-100 text-cyan-800'],
        'response_pending' => ['4', 'border-blue-200 bg-blue-100 text-blue-800'],
        'renewal_pending' => ['5', 'border-violet-200 bg-violet-100 text-violet-800'],
        'completed' => ['6', 'border-green-200 bg-green-100 text-green-800'],
        'cancelled' => ['×', 'border-red-200 bg-red-100 text-red-800'],
        default => ['—', 'border-gray-200 bg-gray-100 text-gray-700'],
    };
@endphp

<span class="inline-flex items-center gap-2">
    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border text-sm font-bold {{ $color }}">{{ $number }}</span>
    <span class="text-sm font-medium text-gray-800">{{ \App\Models\WorkTask::STATUSES[$status] ?? $status }}</span>
</span>
