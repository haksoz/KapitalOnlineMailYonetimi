<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Mail Yönetimi">
        <x-slot name="left">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    <div class="mb-4 max-w-2xl space-y-3">
        <p class="text-sm text-gray-600">
            Müşteri bildirimleri (sipariş, fatura, geciken ödeme) bu ayarlarla gönderilecek.
            <strong>Özel ayar kullan</strong> işaretlenirse aşağıdaki SMTP/log değerleri geçerli olur; işaretlenmezse <code class="text-xs bg-gray-100 px-1 rounded">.env</code> kullanılır.
        </p>
        @if ($mailSetting->use_custom)
            <p class="text-sm rounded-lg px-3 py-2 {{ $mailSetting->driver === 'smtp' ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800' }}">
                Aktif kaynak: <strong>veritabanı</strong>
                ({{ $mailSetting->driver === 'smtp' ? ('SMTP ' . ($mailSetting->host ?: 'sunucu yok') . ($mailSetting->port ? ':'.$mailSetting->port : '')) : 'log — gerçek gönderim yok, dosyaya yazılır' }}).
            </p>
        @else
            <p class="text-sm rounded-lg px-3 py-2 bg-slate-50 text-slate-700">
                Aktif kaynak: <strong>.env / config</strong>
                (MAIL_MAILER={{ $envMailer ?? 'log' }}{{ ! empty($envFromAddress) ? ', gönderen '.$envFromAddress : '' }}).
            </p>
        @endif
        <p class="text-xs text-gray-500">
            Bildirimler cari kartındaki e-posta adresine gider. E-postası boş müşterilere mail gönderilemez.
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl" x-data="{ driver: @js(old('driver', $mailSetting->driver)) }">
        <form action="{{ route('admin.mail-settings.update') }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <input type="hidden" name="use_custom" value="0">
                    <input type="checkbox" id="use_custom" name="use_custom" value="1" class="rounded border-gray-300 text-slate-600 focus:ring-slate-500"
                        @checked(old('use_custom', $mailSetting->use_custom))>
                    <x-input-label for="use_custom" value="Özel ayar kullan (veritabanındaki ayarlarla mail gönder)" class="!mb-0" />
                </div>
                <x-input-error :messages="$errors->get('use_custom')" />

                <div>
                    <x-input-label for="driver" value="Sürücü *" />
                    <select id="driver" name="driver" x-model="driver" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        <option value="smtp">SMTP</option>
                        <option value="log">Log (mailler dosyaya yazılır, gönderilmez)</option>
                    </select>
                    <x-input-error :messages="$errors->get('driver')" class="mt-1" />
                </div>

                <template x-if="driver === 'smtp'">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="host" value="SMTP Sunucu (host)" />
                            <x-text-input id="host" name="host" type="text" class="mt-1 block w-full" :value="old('host', $mailSetting->host)" placeholder="smtp.example.com" />
                            <x-input-error :messages="$errors->get('host')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="port" value="Port" />
                            <x-text-input id="port" name="port" type="number" class="mt-1 block w-full" :value="old('port', $mailSetting->port)" placeholder="587" min="1" max="65535" />
                            <p class="mt-1 text-xs text-gray-500">Genelde 587 (TLS), 465 (SSL) veya 25.</p>
                            <x-input-error :messages="$errors->get('port')" class="mt-1" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="username" value="Kullanıcı adı" />
                            <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="old('username', $mailSetting->username)" autocomplete="off" />
                            <x-input-error :messages="$errors->get('username')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="password" value="Şifre" />
                            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" placeholder="Değiştirmek için doldurun" autocomplete="new-password" />
                            <p class="mt-1 text-xs text-gray-500">
                                @if ($mailSetting->hasPassword())
                                    Kayıtlı bir şifre var. Boş bırakırsanız değişmez.
                                @else
                                    Henüz şifre kaydedilmemiş.
                                @endif
                            </p>
                            <x-input-error :messages="$errors->get('password')" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="encryption" value="Şifreleme" />
                        <select id="encryption" name="encryption" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="" @selected(old('encryption', $mailSetting->encryption) === null || old('encryption', $mailSetting->encryption) === '')>Yok (port 465 ise SSL varsayılır)</option>
                            <option value="tls" @selected(old('encryption', $mailSetting->encryption) === 'tls')>TLS (STARTTLS, genelde 587)</option>
                            <option value="ssl" @selected(old('encryption', $mailSetting->encryption) === 'ssl')>SSL (smtps, genelde 465)</option>
                        </select>
                        <x-input-error :messages="$errors->get('encryption')" class="mt-1" />
                    </div>
                </div>
                </template>

                <template x-if="driver !== 'smtp'">
                    <div>
                        <input type="hidden" name="host" value="{{ old('host', $mailSetting->host) }}">
                        <input type="hidden" name="port" value="{{ old('port', $mailSetting->port) }}">
                        <input type="hidden" name="username" value="{{ old('username', $mailSetting->username) }}">
                        <input type="hidden" name="encryption" value="{{ old('encryption', $mailSetting->encryption) }}">
                    </div>
                </template>

                <div class="pt-2 border-t border-gray-100">
                    <p class="text-sm font-medium text-gray-700 mb-2">Gönderen bilgisi</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="from_address" value="Gönderen e-posta" />
                            <x-text-input id="from_address" name="from_address" type="email" class="mt-1 block w-full" :value="old('from_address', $mailSetting->from_address)" placeholder="noreply@example.com" />
                            <x-input-error :messages="$errors->get('from_address')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="from_name" value="Gönderen adı" />
                            <x-text-input id="from_name" name="from_name" type="text" class="mt-1 block w-full" :value="old('from_name', $mailSetting->from_name)" placeholder="Mail Yönetimi" />
                            <x-input-error :messages="$errors->get('from_name')" class="mt-1" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <x-primary-button>Kaydet</x-primary-button>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">İptal</a>
            </div>
        </form>
    </div>

    <div class="mt-6 bg-white rounded-xl shadow-sm p-6 max-w-2xl">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">Test e-postası gönder</h2>
        <p class="text-xs text-gray-500 mb-4">Kayıtlı ayarlarla bir test e-postası gönderir. Önce ayarları kaydedin, sonra buradan deneyin.</p>
        <form action="{{ route('admin.mail-settings.test') }}" method="POST" class="flex flex-col sm:flex-row gap-3 items-start sm:items-end">
            @csrf
            <div class="flex-1 w-full">
                <x-input-label for="test_email" value="Test e-posta adresi *" />
                <x-text-input id="test_email" name="test_email" type="email" class="mt-1 block w-full" :value="old('test_email', auth()->user()->email ?? '')" required />
                <x-input-error :messages="$errors->get('test_email')" class="mt-1" />
            </div>
            <div>
                <x-primary-button>Test mail gönder</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
