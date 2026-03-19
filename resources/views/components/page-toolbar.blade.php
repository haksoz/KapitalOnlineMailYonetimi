@props([
    'title' => null,
])

<div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div class="flex items-center gap-3 min-w-0">
        @isset($left)
            <div class="shrink-0">
                {{ $left }}
            </div>
        @endisset

        <h1 class="text-lg sm:text-xl font-semibold text-gray-800 truncate">
            {{ $title ?? $slot }}
        </h1>
    </div>

    @isset($right)
        <div class="flex items-center gap-2 flex-wrap justify-end">
            {{ $right }}
        </div>
    @endisset
</div>

