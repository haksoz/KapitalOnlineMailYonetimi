<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar :title="$quote->quote_number.' düzenle'">
        <x-slot name="left">
            <a href="{{ route('quotes.show', $quote) }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    @include('quotes._form')
</x-app-layout>
