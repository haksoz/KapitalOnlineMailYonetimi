@props(['email' => '', 'checked' => false])

@php
    $emailValue = (string) $email;
    $isChecked = filter_var(old('notifications_enabled', $checked), FILTER_VALIDATE_BOOLEAN);
@endphp

<div class="space-y-4" x-data="{
    email: @js($emailValue),
    get hasEmail() {
        return String(this.email || '').trim() !== '';
    }
}">
    <div>
        <x-input-label for="email" value="E-posta" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" x-model="email" :value="$emailValue" />
        <p class="mt-1 text-xs text-gray-500">Müşteri bildirimleri bu adrese gönderilir. Bildirim açmak için e-posta gerekir.</p>
        <x-input-error :messages="$errors->get('email')" class="mt-1" />
    </div>

    <div>
        <input type="hidden" name="notifications_enabled" value="0">
        <div class="flex items-center gap-2" :class="{ 'opacity-50': !hasEmail }">
            <input
                type="checkbox"
                id="notifications_enabled"
                name="notifications_enabled"
                value="1"
                class="rounded border-gray-300 text-slate-600 focus:ring-slate-500 disabled:cursor-not-allowed"
                :disabled="!hasEmail"
                @disabled($emailValue === '')
                x-ref="notify"
                x-effect="if (!hasEmail) { $refs.notify.checked = false }"
                @checked($isChecked)
            >
            <x-input-label for="notifications_enabled" value="Bildirimler aktif" class="!mb-0" />
        </div>
        <p class="mt-1 text-xs text-gray-500" x-show="!hasEmail">E-posta girilmeden bildirim açılamaz; gönderilecek adres yok.</p>
        <p class="mt-1 text-xs text-gray-500" x-show="hasEmail" x-cloak>Bildirimler bu e-posta adresine gider. İstemiyorsanız kutuyu boş bırakın.</p>
        <x-input-error :messages="$errors->get('notifications_enabled')" class="mt-1" />
    </div>
</div>
