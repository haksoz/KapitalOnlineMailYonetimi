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
            SMTP ayarı <a href="{{ route('admin.mail-settings.edit') }}" class="text-slate-700 font-medium underline">Mail Yönetimi</a> sayfasındadır.
        </p>
        <p class="text-xs text-gray-500">
            Yer tutucular:
            <code class="bg-gray-100 px-1 rounded">{musteri}</code>
            <code class="bg-gray-100 px-1 rounded">{fatura_no}</code>
            <code class="bg-gray-100 px-1 rounded">{fatura_tarihi}</code>
            <code class="bg-gray-100 px-1 rounded">{vade_tarihi}</code>
            <code class="bg-gray-100 px-1 rounded">{tutar}</code>
            <code class="bg-gray-100 px-1 rounded">{ftn}</code>
        </p>
    </div>

    <form id="notification-settings-form" action="{{ route('admin.notifications.update') }}" method="POST" class="hidden">
        @csrf
        @method('PATCH')
    </form>

    <div class="max-w-3xl space-y-4">
        @foreach ($definitions as $index => $definition)
            @php
                $isReminder = $definition->key === \App\Models\NotificationDefinition::KEY_INVOICE_DUE_REMINDER;
                $startHint = $isReminder
                    ? 'Fatura tarihinden kaç gün sonra ilk hatırlatma gitsin (0 = fatura günü). Vade dolunca bu bildiri durur.'
                    : 'Vade tarihinin ertesi günden kaç gün sonra ilk gecikme maili gitsin (0 = vade ertesi).';
            @endphp
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">{{ $definition->name }}</h2>
                        <p class="text-xs text-gray-500 mt-1">{{ $startHint }}</p>
                    </div>
                    <div class="flex items-center gap-2">
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

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                        :value="old('definitions.'.$index.'.subject', $definition->subject)"
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
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 text-sm"
                    >{{ old('definitions.'.$index.'.body', $definition->body) }}</textarea>
                    <x-input-error :messages="$errors->get('definitions.'.$index.'.body')" class="mt-1" />
                </div>

                <div class="mt-6 pt-4 border-t border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700 mb-1">Test maili gönder</h3>
                    <p class="text-xs text-gray-500 mb-3">Kayıtlı şablon, seçilen faturanın gerçek alanlarıyla dolar. Müşteriye gitmez; yalnızca yazdığınız adrese gider. Önce ayarı kaydedin.</p>
                    @if ($sampleInvoices->isEmpty())
                        <p class="text-xs text-amber-700">Vadesi kayıtlı satış faturası yok. Fatura no/tarihi girilmiş bir kayıt olunca test edilebilir.</p>
                    @else
                        <form action="{{ route('admin.notifications.test', $definition) }}" method="POST" class="space-y-3">
                            @csrf
                            <div>
                                <x-input-label for="invoice_{{ $definition->id }}" value="Örnek fatura *" />
                                <select
                                    id="invoice_{{ $definition->id }}"
                                    name="sales_invoice_id"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 text-sm"
                                >
                                    <option value="">— Fatura seçin —</option>
                                    @foreach ($sampleInvoices as $invoice)
                                        <option value="{{ $invoice->id }}">
                                            {{ $invoice->customerCari?->short_name ?: $invoice->customerCari?->name ?? 'Müşteri' }}
                                            · {{ $invoice->our_invoice_number }}
                                            · vade {{ $invoice->due_date?->format('d.m.Y') }}
                                            @if ($invoice->total_amount_tl !== null)
                                                · {{ number_format((float) $invoice->total_amount_tl, 2, ',', '.') }} ₺
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
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
        @endforeach

        <div class="flex gap-3">
            <x-primary-button form="notification-settings-form">Kaydet</x-primary-button>
            <a href="{{ route('admin.mail-settings.edit') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">İptal</a>
        </div>
    </div>
</x-app-layout>
