@props(['insertable' => false])

<div class="text-xs text-gray-600 bg-white border border-gray-200 rounded-lg p-3">
    <p class="font-medium text-gray-700 mb-1">Mail içeriğinde kullanabileceğiniz değişkenler</p>
    <p class="text-gray-500 mb-3">
        Konu veya gövdeye <code class="bg-gray-100 px-1 rounded">{ornek}</code> yazın.
        @if ($insertable)
            Koda tıklayınca imlecin olduğu yere eklenir.
        @endif
        Değeri olmayan değişken metinde olduğu gibi kalır.
    </p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @foreach (\App\Automation\PlaceholderCatalog::groups() as $group)
            <div>
                <p class="font-medium text-gray-700 mb-1">{{ $group['group'] }}</p>
                <ul class="space-y-1">
                    @foreach ($group['items'] as $item)
                        <li class="flex flex-wrap items-baseline gap-x-1.5">
                            @if ($insertable)
                                <button
                                    type="button"
                                    class="font-mono bg-gray-100 hover:bg-slate-200 px-1 rounded text-slate-800"
                                    @click="insertPlaceholder({{ \Illuminate\Support\Js::from($item['token']) }})"
                                >{{ $item['token'] }}</button>
                            @else
                                <code class="bg-gray-100 px-1 rounded">{{ $item['token'] }}</code>
                            @endif
                            <span class="text-gray-500">{{ $item['hint'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</div>
