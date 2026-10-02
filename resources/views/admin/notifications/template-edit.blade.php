<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Şablon: {{ $template->name }}">
        <x-slot name="left">
            <a href="{{ route('admin.notifications.templates.index') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    @include('admin.notifications.partials.tabs')

    <script>
        window.notificationSampleInvoices = {!! \Illuminate\Support\Js::from($previewSamples) !!};
        window.notificationMailFrom = {!! \Illuminate\Support\Js::from($mailFrom) !!};
    </script>

    <div
        class="max-w-3xl space-y-4"
        x-data="{
            previewOpen: false,
            invoiceId: '',
            subject: {{ \Illuminate\Support\Js::from(old('subject', $template->subject)) }},
            body: {{ \Illuminate\Support\Js::from(old('body', $template->body)) }},
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
            insertPlaceholder(token) {
                const el = this.$refs.bodyField;
                const current = this.body == null ? '' : String(this.body);
                if (! el) {
                    this.body = current + token;
                    return;
                }
                const start = el.selectionStart ?? current.length;
                const end = el.selectionEnd ?? start;
                this.body = current.slice(0, start) + token + current.slice(end);
                this.$nextTick(() => {
                    el.focus();
                    const pos = start + String(token).length;
                    el.setSelectionRange(pos, pos);
                });
            },
            get previewSubject() { return this.apply(this.subject); },
            get previewBody() { return this.apply(this.body); },
            get previewBodyHtml() {
                const replacements = (this.selected && this.selected.replacements) || {};
                const htmlTokens = (this.selected && this.selected.htmlTokens) || [];
                let text = this.body == null ? '' : String(this.body);
                const slots = {};
                htmlTokens.forEach((token, index) => {
                    const slot = '%%HTMLTOKEN' + index + '%%';
                    slots[slot] = replacements[token] == null ? '' : String(replacements[token]);
                    text = text.split(token).join(slot);
                });
                const escape = (value) => String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
                let html = escape(text).replace(/\n/g, '<br>');
                Object.keys(replacements).forEach((key) => {
                    if (htmlTokens.indexOf(key) !== -1) {
                        return;
                    }
                    const safe = escape(replacements[key] == null ? '' : replacements[key]).replace(/\n/g, '<br>');
                    html = html.split(key).join(safe);
                });
                Object.keys(slots).forEach((slot) => {
                    html = html.split(slot).join(slots[slot]);
                });
                return html;
            },
            get previewTo() { return (this.selected && this.selected.to) || ''; },
            get fromLine() {
                if (this.fromName && this.fromAddress) {
                    return this.fromName + ' (' + this.fromAddress + ')';
                }
                return this.fromName || this.fromAddress || 'Gönderen ayarlanmamış';
            }
        }"
    >
        <form method="POST" action="{{ route('admin.notifications.templates.update', $template) }}" class="bg-white rounded-xl shadow-sm p-5 space-y-4">
            @csrf
            @method('PATCH')
            <div>
                <x-input-label for="name" value="Ad" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $template->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="subject" value="Konu" />
                <x-text-input id="subject" name="subject" class="mt-1 block w-full" x-model="subject" required />
                <x-input-error :messages="$errors->get('subject')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="body" value="İçerik" />
                <textarea id="body" name="body" rows="10" x-ref="bodyField" x-model="body" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required></textarea>
                <x-input-error :messages="$errors->get('body')" class="mt-2" />
            </div>
            @include('admin.notifications.partials.placeholders', ['insertable' => true])
            <x-primary-button>Kaydet</x-primary-button>
        </form>

        <div class="bg-white rounded-xl shadow-sm p-5">
            @if (empty($previewSamples))
                <p class="text-xs text-amber-700 mb-3">{{ $previewKind === 'quote' ? 'Kayıtlı teklif yok. Bir teklif olunca yer tutucular doldurulabilir ve test edilebilir.' : 'Vadesi kayıtlı satış faturası yok. Fatura no/tarihi girilmiş bir kayıt olunca yer tutucular doldurulabilir ve test edilebilir.' }}</p>
            @else
                <div class="mb-4">
                    <x-input-label for="invoice_preview" value="{{ $previewKind === 'quote' ? 'Örnek teklif *' : 'Örnek fatura *' }}" />
                    <select
                        id="invoice_preview"
                        name="{{ $previewKind === 'quote' ? 'quote_id' : 'sales_invoice_id' }}"
                        form="notification-test-form"
                        required
                        x-model="invoiceId"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 text-sm"
                    >
                        <option value="">{{ $previewKind === 'quote' ? '— Teklif seçin —' : '— Fatura seçin —' }}</option>
                        @foreach ($previewSamples as $sample)
                            <option value="{{ $sample['id'] }}">{{ $sample['label'] }}</option>
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
                    aria-controls="notification-preview"
                >
                    <svg class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-90': previewOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    Mail önizlemesi
                </button>
                @if (! empty($previewSamples))
                    <form
                        action="{{ route('admin.notifications.templates.preview', $template) }}"
                        method="POST"
                        target="_blank"
                        class="shrink-0"
                        x-show="previewOpen"
                        x-cloak
                    >
                        @csrf
                        <input type="hidden" name="{{ $previewKind === 'quote' ? 'quote_id' : 'sales_invoice_id' }}" :value="invoiceId">
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

            <div id="notification-preview" x-show="previewOpen" x-cloak class="mt-3">
                <p class="text-xs text-gray-500 mb-3">{{ $previewKind === 'quote' ? 'Yazdığınız konu ve içerik, seçilen teklifin gerçek alanlarıyla dolar. Kaydetmeniz gerekmez.' : 'Yazdığınız konu ve içerik, seçilen faturanın gerçek alanlarıyla dolar. Kaydetmeniz gerekmez.' }}</p>
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
                                    <span class="text-slate-400" x-text="invoiceId ? 'Müşterinin e-posta adresi yok' : {{ \Illuminate\Support\Js::from($previewKind === 'quote' ? 'Teklif seçince müşteri adresi görünür' : 'Fatura seçince müşteri adresi görünür') }}"></span>
                                </template>
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <span class="w-14 shrink-0 text-slate-400">Konu</span>
                            <span class="font-medium text-slate-900 break-words" x-text="previewSubject"></span>
                        </div>
                    </header>
                    @if ($previewKind === 'quote')
                        <div class="px-4 py-4 text-sm text-slate-800 break-words leading-relaxed min-h-[8rem]" x-html="previewBodyHtml"></div>
                    @else
                        <div class="px-4 py-4 text-sm text-slate-800 whitespace-pre-wrap break-words leading-relaxed min-h-[8rem]" x-text="previewBody"></div>
                    @endif
                </article>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-1">Test maili gönder</h3>
            <p class="text-xs text-gray-500 mb-3">{{ $previewKind === 'quote' ? 'Bu sayfadaki konu ve içerik, seçilen teklifin gerçek alanlarıyla dolar. Müşteriye gitmez; yalnızca yazdığınız adrese gider. Kaydetmeden de deneyebilirsiniz. Ekteki PDF müşteri belgesidir.' : 'Kayıtlı şablon, yukarıda seçilen faturanın gerçek alanlarıyla dolar. Müşteriye gitmez; yalnızca yazdığınız adrese gider. Önce ayarı kaydedin.' }}</p>
            @if (empty($previewSamples))
                <p class="text-xs text-amber-700">{{ $previewKind === 'quote' ? 'Kayıtlı teklif yok. Bir teklif olunca test edilebilir.' : 'Vadesi kayıtlı satış faturası yok. Fatura no/tarihi girilmiş bir kayıt olunca test edilebilir.' }}</p>
            @else
                <form id="notification-test-form" action="{{ route('admin.notifications.templates.test', $template) }}" method="POST" class="space-y-3">
                    @csrf
                    @if ($previewKind === 'quote')
                        <input type="hidden" name="subject" :value="subject">
                        <input type="hidden" name="body" :value="body">
                    @endif
                    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-end">
                        <div class="flex-1 w-full">
                            <x-input-label for="test_email" value="Test e-posta adresi *" />
                            <x-text-input
                                id="test_email"
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
</x-app-layout>
