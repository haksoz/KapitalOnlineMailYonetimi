@props(['days' => null])

@php
    $daysValue = old('odeme_vadesi_gun', $days);
    $isVadeli = filter_var(old('is_vadeli'), FILTER_VALIDATE_BOOLEAN);
    if (old('is_vadeli') === null) {
        $isVadeli = $daysValue !== null && $daysValue !== '';
    }
@endphp

<div class="space-y-3" x-data="{
    vadeli: {{ $isVadeli ? 'true' : 'false' }},
    days: {{ \Illuminate\Support\Js::from($daysValue !== null && $daysValue !== '' ? (string) $daysValue : '7') }}
}">
    <input type="hidden" name="is_vadeli" value="0">
    <div class="flex items-center gap-2">
        <input
            type="checkbox"
            id="is_vadeli"
            name="is_vadeli"
            value="1"
            class="rounded border-gray-300 text-slate-600 focus:ring-slate-500"
            x-model="vadeli"
            @checked($isVadeli)
        >
        <x-input-label for="is_vadeli" value="Vadeli çalışılıyor" class="!mb-0" />
    </div>
        <p class="text-xs text-gray-500">Kapalıysa peşin: vade belge günü olur, “vade yaklaşıyor” gitmez; ödenmezse ertesi gün “vadesi geçti” başlar. İşaretlenirse belge tarihine gün eklenir.</p>

    <div x-show="vadeli" x-cloak>
        <x-input-label for="odeme_vadesi_gun" value="Vade gün sayısı *" />
        <x-text-input
            id="odeme_vadesi_gun"
            name="odeme_vadesi_gun"
            type="number"
            min="0"
            max="3650"
            class="mt-1 block w-full max-w-xs"
            x-model="days"
            x-bind:disabled="!vadeli"
            :value="$isVadeli ? $daysValue : 7"
        />
        <p class="mt-1 text-xs text-gray-500">Belge tarihinden kaç gün sonra ödeme istenir. 0 = aynı gün, 7 = bir hafta, 30 = bir ay.</p>
        <x-input-error :messages="$errors->get('odeme_vadesi_gun')" class="mt-1" />
        <x-input-error :messages="$errors->get('is_vadeli')" class="mt-1" />
    </div>
</div>
