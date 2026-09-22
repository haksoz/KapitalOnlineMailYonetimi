@php
    $autoRenew = old('auto_renew', $rule?->conditions['auto_renew'] ?? '');
    if ($autoRenew === true) {
        $autoRenew = '1';
    } elseif ($autoRenew === false) {
        $autoRenew = '0';
    }
    $actionTemplateId = old('notification_template_id', $rule?->actions->first()?->notification_template_id);
    $defaultEvent = old('event_type', $rule?->event_type?->value ?? \App\Automation\EventType::SubscriptionCreated->value);
    $eventForMode = \App\Automation\EventType::tryFrom((string) $defaultEvent);
    $defaultMode = old('timing_mode', $rule?->timingMode()->value ?? \App\Automation\TimingMode::Immediate->value);
    $modeForDefault = \App\Automation\TimingMode::tryFrom((string) $defaultMode);
    if ($eventForMode !== null && ($modeForDefault === null || ! $eventForMode->allowsTimingMode($modeForDefault))) {
        $defaultMode = $eventForMode->defaultTimingMode()->value;
    }
    $defaultDedupe = old('dedupe_policy', $rule?->dedupePolicy()->value ?? \App\Automation\DedupePolicy::Once->value);
    $policyForDefault = \App\Automation\DedupePolicy::tryFrom((string) $defaultDedupe);
    if ($eventForMode !== null && ($policyForDefault === null || ! $eventForMode->allowsDedupePolicy($policyForDefault))) {
        $defaultDedupe = $eventForMode->defaultDedupePolicy()->value;
    }
@endphp
<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar :title="$rule ? 'Kuralı düzenle' : 'Yeni kural'">
        <x-slot name="left">
            <a href="{{ route('admin.notifications.edit') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    @include('admin.notifications.partials.tabs')

    <form
        method="POST"
        action="{{ $rule ? route('admin.notifications.rules.update', $rule) : route('admin.notifications.rules.store') }}"
        class="max-w-3xl bg-white rounded-xl shadow-sm p-5 space-y-4"
        x-data="{
            eventType: {{ \Illuminate\Support\Js::from($defaultEvent) }},
            timingMode: {{ \Illuminate\Support\Js::from($defaultMode) }},
            dedupePolicy: {{ \Illuminate\Support\Js::from($defaultDedupe) }},
            meta: {{ \Illuminate\Support\Js::from($eventMeta) }},
            init() {
                this.$watch('eventType', () => {
                    if (! this.allowedModes.includes(this.timingMode)) {
                        this.timingMode = this.eventMeta.default_mode;
                    }
                    if (! this.allowedDedupe.includes(this.dedupePolicy)) {
                        this.dedupePolicy = this.eventMeta.default_dedupe;
                    }
                });
            },
            get eventMeta() {
                return this.meta[this.eventType] || {
                    instant: true,
                    show_auto_renew: false,
                    offset_hint: '',
                    timing_intro: '',
                    default_mode: 'immediate',
                    allowed_modes: ['immediate', 'at_send_at'],
                    default_dedupe: 'once',
                    allowed_dedupe: ['once'],
                    dedupe_intro: '',
                    dedupe_options: [{ value: 'once', label: 'Bir kez (otomatik tekrar yok)' }]
                };
            },
            get isInstant() { return this.eventMeta.instant; },
            get showAutoRenew() { return this.eventMeta.show_auto_renew; },
            get allowedModes() { return this.eventMeta.allowed_modes || []; },
            get allowedDedupe() { return this.eventMeta.allowed_dedupe || []; },
            dedupeLabel(value) {
                const option = (this.eventMeta.dedupe_options || []).find((item) => item.value === value);
                return option ? option.label : value;
            },
            get showSendAt() { return this.timingMode !== 'immediate'; },
            get showOffset() { return ! this.isInstant; },
            get showInterval() { return ! this.isInstant && this.dedupePolicy !== 'once'; }
        }"
    >
        @csrf
        @if ($rule)
            @method('PATCH')
        @endif

        <div>
            <x-input-label for="name" value="Ad" />
            <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $rule?->name)" required />
        </div>
        <div>
            <x-input-label for="event_type" value="Olay" />
            <select id="event_type" name="event_type" x-model="eventType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                @foreach ($eventTypes as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-center gap-2">
            <input type="hidden" name="is_enabled" value="0">
            <input type="checkbox" id="is_enabled" name="is_enabled" value="1" class="rounded border-gray-300 text-slate-600 focus:ring-slate-500" @checked(old('is_enabled', $rule?->is_enabled))>
            <x-input-label for="is_enabled" value="Aktif" class="!mb-0" />
        </div>

        <fieldset class="space-y-2">
            <legend class="text-sm font-medium text-gray-700">Gönderim zamanı</legend>
            <p class="text-xs text-gray-500" x-text="eventMeta.timing_intro"></p>
            <template x-if="! isInstant">
                <input type="hidden" name="timing_mode" value="at_send_at">
            </template>
            <template x-if="isInstant">
                <div class="space-y-2">
                    @foreach ($timingModes as $mode)
                        @if ($mode === \App\Automation\TimingMode::OffsetDays)
                            @continue
                        @endif
                        <label class="flex items-start gap-2 text-sm text-gray-800" x-show="allowedModes.includes({{ \Illuminate\Support\Js::from($mode->value) }})">
                            <input type="radio" name="timing_mode" value="{{ $mode->value }}" x-model="timingMode" class="mt-0.5 border-gray-300 text-slate-600 focus:ring-slate-500">
                            <span>
                                <span class="font-medium">{{ $mode->label() }}</span>
                                <span class="block text-xs text-gray-500">{{ $mode->hint() }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </template>
        </fieldset>

        <div>
            <x-input-label for="dedupe_policy" value="Tekrar politikası" />
            <select id="dedupe_policy" name="dedupe_policy" x-model="dedupePolicy" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                @foreach (\App\Automation\DedupePolicy::cases() as $policy)
                    <option
                        value="{{ $policy->value }}"
                        x-show="allowedDedupe.includes({{ \Illuminate\Support\Js::from($policy->value) }})"
                        :disabled="! allowedDedupe.includes({{ \Illuminate\Support\Js::from($policy->value) }})"
                        x-text="dedupeLabel({{ \Illuminate\Support\Js::from($policy->value) }})"
                        @selected($policy->value === $defaultDedupe)
                    >{{ $policy->label() }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500" x-text="eventMeta.dedupe_intro"></p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div x-show="showOffset" x-cloak>
                <x-input-label for="offset_days" value="Gün ofseti" />
                <x-text-input id="offset_days" name="offset_days" type="number" min="0" class="mt-1 block w-full" :value="old('offset_days', $rule?->offsetDays() ?? 0)" />
                <p class="mt-1 text-xs text-gray-500" x-text="eventMeta.offset_hint"></p>
            </div>
            <div x-show="showInterval" x-cloak>
                <x-input-label for="interval_days" value="Aralık (gün)" />
                <x-text-input id="interval_days" name="interval_days" type="number" min="1" class="mt-1 block w-full" :value="old('interval_days', $rule?->intervalDays() ?? 1)" />
                <p class="mt-1 text-xs text-gray-500">Aynı kayda bir sonraki mail bu kadar gün sonra gider.</p>
            </div>
            <div x-show="showSendAt" x-cloak>
                <x-input-label for="send_at" value="Gönderim saati" />
                <x-text-input id="send_at" name="send_at" type="time" class="mt-1 block w-full" :value="old('send_at', $rule?->sendAtForInput() ?? '10:00')" />
            </div>
        </div>
        <div x-show="showAutoRenew" x-cloak>
            <x-input-label for="auto_renew" value="Otomatik yenileme koşulu" />
            <select id="auto_renew" name="auto_renew" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                <option value="" @selected($autoRenew === '' || $autoRenew === null)>Fark etmez</option>
                <option value="1" @selected((string) $autoRenew === '1')>Yalnızca açık</option>
                <option value="0" @selected((string) $autoRenew === '0')>Yalnızca kapalı</option>
            </select>
        </div>
        <div>
            <x-input-label for="notification_template_id" value="E-posta şablonu" />
            <select id="notification_template_id" name="notification_template_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                @foreach ($templates as $template)
                    <option value="{{ $template->id }}" @selected((int) $actionTemplateId === (int) $template->id)>{{ $template->name }}</option>
                @endforeach
            </select>
        </div>
        <x-primary-button>Kaydet</x-primary-button>
    </form>
</x-app-layout>
