<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Bildiri Yönetimi">
        <x-slot name="left">
            <a href="{{ route('admin.mail-settings.edit') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation" aria-label="Mail yönetimine dön">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    <div class="mb-4 max-w-3xl space-y-2">
        <p class="text-sm text-gray-600">
            Satış faturası numarası atanmış, vadesi dolmuş veya yaklaşan, henüz ödenmemiş faturalar için müşteri e-postasına bildirim gider.
            Mail yalnızca e-posta adresi dolu ve bildirimi açık carilere gider.
            Her bildirinin kendi saati vardır (Türkiye saati); o saatten önce o gün mail gitmez.
            SMTP ayarı <a href="{{ route('admin.mail-settings.edit') }}" class="text-slate-700 font-medium underline">Mail Yönetimi</a> sayfasındadır.
        </p>
        @php
            $interestDefinition = $definitions->firstWhere('key', \App\Models\NotificationDefinition::KEY_INVOICE_INTEREST_CLOSURE);
            $interestStartDays = (int) ($interestDefinition?->start_after_days ?? 30);
        @endphp
        <div class="text-xs text-gray-600 bg-amber-50 border border-amber-100 rounded-lg p-3">
            <p class="font-medium text-gray-700 mb-2">Aşamalar — aynı faturaya aynı anda yalnızca biri gider</p>
            <ol class="list-decimal list-inside space-y-1">
                <li><strong>Vade öncesi hatırlatma</strong> — vade gününe kadar.</li>
                <li><strong>Vade sonrası gecikme</strong> — vade ertesinden itibaren. Faiz uyarısı aktifse, vade + {{ $interestStartDays }} gün dolduğu gün durur.</li>
                <li><strong>Faiz Uygulaması ve Kapatma</strong> — vade + {{ $interestStartDays }} günden sonra gecikmenin yerini alır. Bir kez gittiyse gecikme o faturaya bir daha gitmez.</li>
            </ol>
        </div>
        <div class="text-xs text-gray-600 bg-white border border-gray-200 rounded-lg p-3">
            <p class="font-medium text-gray-700 mb-2">Mail içeriğinde kullanabileceğiniz yer tutucular</p>
            <ul class="space-y-1">
                <li><code class="bg-gray-100 px-1 rounded">{musteri}</code> — müşteri adı</li>
                <li><code class="bg-gray-100 px-1 rounded">{fatura_no}</code> — fatura numarası</li>
                <li><code class="bg-gray-100 px-1 rounded">{fatura_tarihi}</code> — fatura tarihi</li>
                <li><code class="bg-gray-100 px-1 rounded">{vade_tarihi}</code> — vade tarihi</li>
                <li><code class="bg-gray-100 px-1 rounded">{tutar}</code> — kesilen faturanın <strong>KDV dahil</strong> toplamı</li>
                <li><code class="bg-gray-100 px-1 rounded">{ftn}</code> — fatura takip no</li>
            </ul>
        </div>
    </div>

    <form id="notification-settings-form" action="{{ route('admin.notifications.update') }}" method="POST" class="hidden">
        @csrf
        @method('PATCH')
    </form>

    <script>
        window.notificationSampleInvoices = {!! \Illuminate\Support\Js::from($sampleInvoices) !!};
        window.notificationMailFrom = {!! \Illuminate\Support\Js::from($mailFrom) !!};
    </script>

    <div class="max-w-3xl space-y-4">
        @foreach ($definitions as $index => $definition)
            @php
                $startHint = match ($definition->key) {
                    \App\Models\NotificationDefinition::KEY_INVOICE_DUE_REMINDER => 'Fatura tarihinden kaç gün sonra ilk hatırlatma gitsin (0 = fatura günü). Vade dolunca bu bildiri durur.',
                    \App\Models\NotificationDefinition::KEY_INVOICE_OVERDUE => 'Vade tarihinin ertesi günden kaç gün sonra ilk gecikme maili gitsin (0 = vade ertesi). Faiz Uygulaması ve Kapatma aktifken, o bildiri başladığı gün gecikme otomatik durur.',
                    \App\Models\NotificationDefinition::KEY_INVOICE_INTEREST_CLOSURE => 'Vade tarihinden kaç gün sonra ilk yasal uyarı gitsin (30 = 1 ay). Bu bildiri başlayınca vade sonrası gecikme o faturaya gitmez.',
                    default => '',
                };
                $sendAtHint = match ($definition->key) {
                    \App\Models\NotificationDefinition::KEY_INVOICE_DUE_REMINDER => 'Türkiye saati. Hatırlatma için 10:00 önerilir.',
                    \App\Models\NotificationDefinition::KEY_INVOICE_OVERDUE => 'Türkiye saati. Gecikme için 14:30 önerilir.',
                    \App\Models\NotificationDefinition::KEY_INVOICE_INTEREST_CLOSURE => 'Türkiye saati. Yasal uyarı için 16:00 önerilir.',
                    default => 'Türkiye saati.',
                };
                $cardOpen = collect($errors->keys())->contains(
                    fn ($key) => str_starts_with((string) $key, 'definitions.'.$index.'.')
                );
            @endphp
            <div
                class="bg-white rounded-xl shadow-sm"
                x-data="{
                    open: {{ $cardOpen ? 'true' : 'false' }},
                    previewOpen: false,
                    invoiceId: '',
                    subject: {{ \Illuminate\Support\Js::from(old('definitions.'.$index.'.subject', $definition->subject)) }},
                    body: {{ \Illuminate\Support\Js::from(old('definitions.'.$index.'.body', $definition->body)) }},
                    invoices: window.notificationSampleInvoices || [],
                    fromName: (window.notificationMailFrom && window.notificationMailFrom.name) || '',
                    fromAddress: (window.notificationMailFrom && window.notificationMailFrom.address) || '',
                    get selected() {
                        return this.invoices.find((invoice) => String(invoice.id) === String(this.invoiceId)) || null;
                    },
                    apply(text) {
                        const replacements = (this.selected && this.selected.replacements) || {};
                        return Object.keys(replacements).reduce(
                            (out, key) => out.split(key).join(replacements[key]),
                            text == null ? '' : String(text)
                        );
                    },
                    get previewSubject() { return this.apply(this.subject); },
                    get previewBody() { return this.apply(this.body); },
                    get previewTo() { return (this.selected && this.selected.to) || ''; },
                    get fromLine() {
                        if (this.fromName && this.fromAddress) {
                            return this.fromName + ' (' + this.fromAddress + ')';
                        }
                        return this.fromName || this.fromAddress || 'Gönderen ayarlanmamış';
                    }
                }"
            >
                <div class="flex items-start gap-3 px-5 py-4">
                    <button
                        type="button"
                        class="flex-1 min-w-0 flex items-start gap-2 text-left rounded-lg -ml-1 px-1 py-0.5 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500"
                        @click="open = !open"
                        :aria-expanded="open ? 'true' : 'false'"
                        aria-controls="notification-card-body-{{ $definition->id }}"
                    >
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-gray-400 transition-transform duration-200" :class="{ 'rotate-90': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-gray-800">{{ $definition->name }}</span>
                            <span class="block text-xs text-gray-500 mt-1">{{ $startHint }}</span>
                        </span>
                    </button>
                    <div class="flex items-center gap-2 shrink-0 pt-0.5">
                        <input type="hidden" form="notification-settings-form" name="definitions[{{ $index }}][id]" value="{{ $definition->id }}">
                        <input type="hidden" form="notification-settings-form" name="definitions[{{ $index }}][is_enabled]" value="0">
                        <input
                            type="checkbox"
                            id="enabled_{{ $definition->id }}"
                            form="notification-settings-form"
                            name="definitions[{{ $index }}][is_enabled]"
                            value="1"
                            class="rounded border-gray-300 text-slate-600 focus:ring-slate-500"
                            @checked(old('definitions.'.$index.'.is_enabled', $definition->is_enabled))
                        >
                        <x-input-label for="enabled_{{ $definition->id }}" value="Aktif" class="!mb-0" />
                    </div>
                </div>

                <div
                    id="notification-card-body-{{ $definition->id }}"
                    x-show="open"
                    x-cloak
                    class="px-5 pb-6 border-t border-gray-100"
                >
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4">
                    <div>
                        <x-input-label for="start_{{ $definition->id }}" value="İlk gönderim (gün)" />
                        <x-text-input
                            id="start_{{ $definition->id }}"
                            form="notification-settings-form"
                            name="definitions[{{ $index }}][start_after_days]"
                            type="number"
                            min="0"
                            max="3650"
                            class="mt-1 block w-full"
                            :value="old('definitions.'.$index.'.start_after_days', $definition->start_after_days)"
                            required
                        />
                        <x-input-error :messages="$errors->get('definitions.'.$index.'.start_after_days')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="interval_{{ $definition->id }}" value="Tekrar sıklığı (gün)" />
                        <x-text-input
                            id="interval_{{ $definition->id }}"
                            form="notification-settings-form"
                            name="definitions[{{ $index }}][interval_days]"
                            type="number"
                            min="1"
                            max="365"
                            class="mt-1 block w-full"
                            :value="old('definitions.'.$index.'.interval_days', $definition->interval_days)"
                            required
                        />
                        <p class="mt-1 text-xs text-gray-500">Aynı faturaya bir sonraki mail bu kadar gün sonra gider.</p>
                        <x-input-error :messages="$errors->get('definitions.'.$index.'.interval_days')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="send_at_{{ $definition->id }}" value="Gönderim saati" />
                        <input
                            id="send_at_{{ $definition->id }}"
                            form="notification-settings-form"
                            name="definitions[{{ $index }}][send_at]"
                            type="time"
                            step="60"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            value="{{ old('definitions.'.$index.'.send_at', $definition->sendAtForInput()) }}"
                        >
                        <p class="mt-1 text-xs text-gray-500">{{ $sendAtHint }}</p>
                        <x-input-error :messages="$errors->get('definitions.'.$index.'.send_at')" class="mt-1" />
                    </div>
                </div>

                <div class="mt-4">
                    <x-input-label for="subject_{{ $definition->id }}" value="Konu" />
                    <x-text-input
                        id="subject_{{ $definition->id }}"
                        form="notification-settings-form"
                        name="definitions[{{ $index }}][subject]"
                        type="text"
                        maxlength="255"
                        class="mt-1 block w-full"
                        x-model="subject"
                        required
                    />
                    <x-input-error :messages="$errors->get('definitions.'.$index.'.subject')" class="mt-1" />
                </div>

                <div class="mt-4">
                    <x-input-label for="body_{{ $definition->id }}" value="İçerik" />
                    <textarea
                        id="body_{{ $definition->id }}"
                        form="notification-settings-form"
                        name="definitions[{{ $index }}][body]"
                        rows="8"
                        maxlength="20000"
                        required
                        x-model="body"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 text-sm"
                    ></textarea>
                    <x-input-error :messages="$errors->get('definitions.'.$index.'.body')" class="mt-1" />
                </div>

                <div class="mt-6 pt-4 border-t border-gray-100">
                    @if (empty($sampleInvoices))
                        <p class="text-xs text-amber-700 mb-3">Vadesi kayıtlı satış faturası yok. Fatura no/tarihi girilmiş bir kayıt olunca yer tutucular doldurulabilir ve test edilebilir.</p>
                    @else
                        <div class="mb-4">
                            <x-input-label for="invoice_{{ $definition->id }}" value="Örnek fatura *" />
                            <select
                                id="invoice_{{ $definition->id }}"
                                name="sales_invoice_id"
                                form="notification-test-form-{{ $definition->id }}"
                                required
                                x-model="invoiceId"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 text-sm"
                            >
                                <option value="">— Fatura seçin —</option>
                                @foreach ($sampleInvoices as $invoice)
                                    <option value="{{ $invoice['id'] }}">{{ $invoice['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 text-sm font-semibold text-gray-700 rounded-lg -ml-1 px-1 py-0.5 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500"
                            @click="previewOpen = !previewOpen"
                            :aria-expanded="previewOpen ? 'true' : 'false'"
                            aria-controls="notification-preview-{{ $definition->id }}"
                        >
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-90': previewOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            Mail önizlemesi
                        </button>
                        @if (! empty($sampleInvoices))
                            <form
                                action="{{ route('admin.notifications.preview', $definition) }}"
                                method="POST"
                                target="_blank"
                                class="shrink-0"
                                x-show="previewOpen"
                                x-cloak
                            >
                                @csrf
                                <input type="hidden" name="sales_invoice_id" :value="invoiceId">
                                <input type="hidden" name="subject" :value="subject">
                                <input type="hidden" name="body" :value="body">
                                <button
                                    type="submit"
                                    class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 disabled:opacity-50"
                                    :disabled="! invoiceId"
                                >
                                    Yeni sekmede aç
                                </button>
                            </form>
                        @endif
                    </div>

                    <div
                        id="notification-preview-{{ $definition->id }}"
                        x-show="previewOpen"
                        x-cloak
                        class="mt-3"
                    >
                        <p class="text-xs text-gray-500 mb-3">Yazdığınız konu ve içerik, seçilen faturanın gerçek alanlarıyla dolar. Kaydetmeniz gerekmez.</p>
                        <article class="rounded-lg border border-slate-200 bg-slate-50 overflow-hidden" aria-label="Mail önizlemesi">
                            <header class="px-4 py-3 space-y-1.5 text-xs border-b border-slate-200 bg-white">
                                <div class="flex gap-2">
                                    <span class="w-14 shrink-0 text-slate-400">Kimden</span>
                                    <span class="text-slate-800 break-all" x-text="fromLine"></span>
                                </div>
                                <div class="flex gap-2">
                                    <span class="w-14 shrink-0 text-slate-400">Kime</span>
                                    <span class="text-slate-800 break-all">
                                        <template x-if="previewTo">
                                            <span x-text="previewTo"></span>
                                        </template>
                                        <template x-if="! previewTo">
                                            <span class="text-slate-400" x-text="invoiceId ? 'Müşterinin e-posta adresi yok' : 'Fatura seçince müşteri adresi görünür'"></span>
                                        </template>
                                    </span>
                                </div>
                                <div class="flex gap-2">
                                    <span class="w-14 shrink-0 text-slate-400">Konu</span>
                                    <span class="font-medium text-slate-900 break-words" x-text="previewSubject"></span>
                                </div>
                            </header>
                            <div class="px-4 py-4 text-sm text-slate-800 whitespace-pre-wrap break-words leading-relaxed min-h-[8rem]" x-text="previewBody"></div>
                        </article>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700 mb-1">Test maili gönder</h3>
                    <p class="text-xs text-gray-500 mb-3">Kayıtlı şablon, yukarıda seçilen faturanın gerçek alanlarıyla dolar. Müşteriye gitmez; yalnızca yazdığınız adrese gider. Önce ayarı kaydedin.</p>
                    @if (empty($sampleInvoices))
                        <p class="text-xs text-amber-700">Vadesi kayıtlı satış faturası yok. Fatura no/tarihi girilmiş bir kayıt olunca test edilebilir.</p>
                    @else
                        <form id="notification-test-form-{{ $definition->id }}" action="{{ route('admin.notifications.test', $definition) }}" method="POST" class="space-y-3">
                            @csrf
                            <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-end">
                                <div class="flex-1 w-full">
                                    <x-input-label for="test_email_{{ $definition->id }}" value="Test e-posta adresi *" />
                                    <x-text-input
                                        id="test_email_{{ $definition->id }}"
                                        name="test_email"
                                        type="email"
                                        class="mt-1 block w-full"
                                        :value="old('test_email', auth()->user()->email ?? '')"
                                        required
                                    />
                                </div>
                                <x-primary-button>Test mail gönder</x-primary-button>
                            </div>
                        </form>
                    @endif
                </div>
                </div>
            </div>
        @endforeach

        <div class="flex gap-3">
            <x-primary-button form="notification-settings-form">Kaydet</x-primary-button>
            <a href="{{ route('admin.mail-settings.edit') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">İptal</a>
        </div>
    </div>
</x-app-layout>
