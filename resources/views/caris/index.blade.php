<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Cariler">
        <x-slot name="right">
            <a href="{{ route('caris.create') }}" class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 bg-slate-800 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition w-full sm:w-auto touch-manipulation">
                Yeni Cari
            </a>
        </x-slot>
    </x-page-toolbar>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <form action="{{ route('caris.index') }}" method="GET" class="p-4">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Kısa ad, ünvan, e-posta veya vergi numarası ile ara..."
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-transparent">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 bg-slate-800 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition">
                        Ara
                    </button>
                    @if (request('search'))
                        <a href="{{ route('caris.index') }}" class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 bg-gray-200 border border-transparent rounded-lg font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition">
                            Temizle
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <p class="text-xs text-gray-500 mb-3">E-posta ve vade tabloda düzenlenir. Alandan çıkınca veya Enter ile kaydedilir.</p>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kısa Ad / Ünvan</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E-posta</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bildirim</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vade</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ülke</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vergi No</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cari Tipi</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">İşlem</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($caris as $cari)
                        <tr
                            class="hover:bg-gray-50"
                            x-data="window.cariQuickRow({
                                url: {{ \Illuminate\Support\Js::from(route('caris.quick-update', $cari)) }},
                                email: {{ \Illuminate\Support\Js::from((string) ($cari->email ?? '')) }},
                                notificationsEnabled: {{ $cari->notifications_enabled ? 'true' : 'false' }},
                                vadeli: {{ $cari->hasPaymentTerm() ? 'true' : 'false' }},
                                days: {{ \Illuminate\Support\Js::from($cari->odeme_vadesi_gun) }}
                            })"
                        >
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $cari->short_name ?: $cari->name }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex items-center gap-2 min-w-[14rem]">
                                    <input
                                        type="email"
                                        x-model="email"
                                        :disabled="emailBusy"
                                        autocomplete="off"
                                        placeholder="e-posta gir"
                                        class="block w-56 max-w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 disabled:bg-gray-50"
                                        @blur="saveEmail()"
                                        @keydown.enter.prevent="saveEmail()"
                                    >
                                    <span x-show="emailSaved" x-cloak class="text-emerald-600 text-xs font-medium">Kaydedildi</span>
                                </div>
                                <p x-show="emailError" x-text="emailError" x-cloak class="mt-1 text-xs text-red-600"></p>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                <label class="inline-flex items-center gap-2 text-sm" :class="hasEmail ? 'text-gray-700' : 'text-gray-400'">
                                    <input
                                        type="checkbox"
                                        class="rounded border-gray-300 text-slate-600 focus:ring-slate-500 disabled:cursor-not-allowed"
                                        x-model="notificationsEnabled"
                                        :disabled="emailBusy || !hasEmail"
                                        @change="saveNotify()"
                                    >
                                    <span x-text="notificationsEnabled && hasEmail ? 'Açık' : 'Kapalı'"></span>
                                </label>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                <div class="flex items-center gap-2">
                                    <label class="inline-flex items-center gap-1.5 text-sm text-gray-700">
                                        <input
                                            type="checkbox"
                                            class="rounded border-gray-300 text-slate-600 focus:ring-slate-500"
                                            x-model="vadeli"
                                            :disabled="termBusy"
                                            @change="onVadeliToggle()"
                                        >
                                        Vadeli
                                    </label>
                                    <span x-show="vadeli" class="inline-flex items-center gap-1">
                                        <input
                                            type="number"
                                            min="0"
                                            max="3650"
                                            x-model="days"
                                            :disabled="termBusy"
                                            class="w-16 rounded-md border-gray-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 disabled:bg-gray-50"
                                            @blur="saveTerm()"
                                            @keydown.enter.prevent="saveTerm()"
                                        >
                                        <span class="text-xs text-gray-500">gün</span>
                                    </span>
                                    <span x-show="!vadeli" class="text-xs text-gray-500">Peşin</span>
                                    <span x-show="termSaved" x-cloak class="text-emerald-600 text-xs font-medium">Kaydedildi</span>
                                </div>
                                <p x-show="termError" x-text="termError" x-cloak class="mt-1 text-xs text-red-600"></p>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                {{ $cari->country_code ?? 'TR' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                {{ $cari->tax_number ?: '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                @php
                                    $labels = [
                                        'customer' => 'Müşteri',
                                        'supplier' => 'Tedarikçi',
                                        'both' => 'Müşteri + Tedarikçi',
                                    ];
                                @endphp
                                {{ $labels[$cari->cari_type] ?? '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                <a href="{{ route('caris.edit', $cari) }}" class="text-slate-600 hover:text-slate-900 font-medium">Düzenle</a>
                                <form action="{{ route('caris.destroy', $cari) }}" method="POST" class="inline-block ml-3" onsubmit="return confirm('Bu cariyi silmek istediğinize emin misiniz?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Sil</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">Henüz cari eklenmemiş.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($caris->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                {{ $caris->links() }}
            </div>
        @endif
    </div>

    <script>
        window.cariQuickRow = function (initial) {
            return {
                url: initial.url,
                email: initial.email || '',
                savedEmail: initial.email || '',
                notificationsEnabled: !!initial.notificationsEnabled,
                vadeli: !!initial.vadeli,
                days: initial.days === null || initial.days === undefined ? 7 : initial.days,
                savedVadeli: !!initial.vadeli,
                savedDays: initial.days === null || initial.days === undefined ? null : initial.days,
                emailBusy: false,
                termBusy: false,
                emailError: '',
                termError: '',
                emailSaved: false,
                termSaved: false,
                get hasEmail() {
                    return String(this.email || '').trim() !== '';
                },
                csrf() {
                    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                },
                applyEmail(data) {
                    this.email = data.email || '';
                    this.savedEmail = data.email || '';
                    this.notificationsEnabled = !!data.notifications_enabled;
                },
                applyTerm(data) {
                    this.vadeli = !!data.has_payment_term;
                    this.savedVadeli = this.vadeli;
                    this.savedDays = data.odeme_vadesi_gun === null || data.odeme_vadesi_gun === undefined
                        ? null
                        : data.odeme_vadesi_gun;
                    this.days = this.savedDays === null ? 7 : this.savedDays;
                },
                async patch(payload) {
                    const response = await fetch(this.url, {
                        method: 'PATCH',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.csrf(),
                        },
                        body: JSON.stringify(payload),
                    });
                    const data = await response.json().catch(() => ({}));
                    if (! response.ok) {
                        const fromErrors = data.errors ? Object.values(data.errors).flat()[0] : null;
                        throw new Error(fromErrors || data.message || 'Kaydedilemedi.');
                    }
                    return data;
                },
                async saveEmail() {
                    const next = String(this.email || '').trim();
                    if (next === this.savedEmail) {
                        return;
                    }
                    this.emailError = '';
                    this.emailBusy = true;
                    try {
                        const data = await this.patch({
                            email: next === '' ? null : next,
                            notifications_enabled: next !== '' && this.notificationsEnabled,
                        });
                        this.applyEmail(data);
                        this.emailSaved = true;
                        setTimeout(() => { this.emailSaved = false; }, 1500);
                    } catch (error) {
                        this.emailError = error.message;
                        this.email = this.savedEmail;
                    } finally {
                        this.emailBusy = false;
                    }
                },
                async saveNotify() {
                    if (! this.hasEmail) {
                        this.notificationsEnabled = false;
                        return;
                    }
                    this.emailError = '';
                    this.emailBusy = true;
                    try {
                        const data = await this.patch({
                            email: String(this.email || '').trim(),
                            notifications_enabled: this.notificationsEnabled,
                        });
                        this.applyEmail(data);
                    } catch (error) {
                        this.notificationsEnabled = ! this.notificationsEnabled;
                        this.emailError = error.message;
                    } finally {
                        this.emailBusy = false;
                    }
                },
                onVadeliToggle() {
                    if (this.vadeli && (this.days === null || this.days === '')) {
                        this.days = 7;
                    }
                    this.saveTerm();
                },
                async saveTerm() {
                    if (this.vadeli === this.savedVadeli && (! this.vadeli || Number(this.days) === Number(this.savedDays))) {
                        return;
                    }
                    this.termError = '';
                    this.termBusy = true;
                    try {
                        const payload = { is_vadeli: this.vadeli };
                        if (this.vadeli) {
                            payload.odeme_vadesi_gun = Number(this.days);
                        }
                        const data = await this.patch(payload);
                        this.applyTerm(data);
                        this.termSaved = true;
                        setTimeout(() => { this.termSaved = false; }, 1500);
                    } catch (error) {
                        this.termError = error.message;
                        this.vadeli = this.savedVadeli;
                        this.days = this.savedDays === null ? 7 : this.savedDays;
                    } finally {
                        this.termBusy = false;
                    }
                },
            };
        };
    </script>
</x-app-layout>
