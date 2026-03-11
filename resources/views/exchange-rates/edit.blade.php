<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('exchange-rates.index', ['date' => optional($rate->effective_date)->toDateString()]) }}" class="text-gray-500 hover:text-gray-700">&larr;</a>
            <h1 class="text-xl font-semibold text-gray-800">
                Kur Düzenle — {{ $rate->currency_code }}
            </h1>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-lg">
        <form action="{{ route('exchange-rates.update', $rate) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="space-y-4">
                <div>
                    <x-input-label value="Döviz Kodu" />
                    <p class="mt-1 text-sm text-gray-900 font-medium">{{ $rate->currency_code }}</p>
                </div>
                <div>
                    <x-input-label value="Ad" />
                    <p class="mt-1 text-sm text-gray-900">{{ $rate->name ?? '—' }}</p>
                </div>
                <div>
                    <x-input-label value="Tarih" />
                    <p class="mt-1 text-sm text-gray-900">
                        {{ optional($rate->effective_date)->format('d.m.Y') ?? '—' }}
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="forex_buying" value="Döviz Alış" />
                        <x-text-input id="forex_buying" name="forex_buying" type="text"
                                      class="mt-1 block w-full"
                                      :value="old('forex_buying', $rate->forex_buying)"
                                      inputmode="decimal" />
                    </div>
                    <div>
                        <x-input-label for="forex_selling" value="Döviz Satış" />
                        <x-text-input id="forex_selling" name="forex_selling" type="text"
                                      class="mt-1 block w-full"
                                      :value="old('forex_selling', $rate->forex_selling)"
                                      inputmode="decimal" />
                    </div>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <x-primary-button>Kaydet</x-primary-button>
                <a href="{{ route('exchange-rates.index', ['date' => optional($rate->effective_date)->toDateString()]) }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                    İptal
                </a>
            </div>
        </form>
    </div>
</x-app-layout>

