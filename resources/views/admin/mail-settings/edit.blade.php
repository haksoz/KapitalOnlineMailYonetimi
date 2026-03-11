<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">&larr;</a>
            <h1 class="text-xl font-semibold text-gray-800">Mail Yönetimi</h1>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="mb-4">
        <p class="text-sm text-gray-600">
            Sistemin otomatik mail atması gerektiğinde kullanılacak ayarlar. <strong>Özel ayar kullan</strong> işaretlenirse aşağıdaki değerler kullanılır; işaretlenmezse .env / config dosyasındaki mail ayarları kullanılır.
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
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

                <div>
                    <x-input-label for="driver" value="Sürücü *" />
                    <select id="driver" name="driver" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        <option value="smtp" @selected(old('driver', $mailSetting->driver) === 'smtp')>SMTP</option>
                        <option value="log" @selected(old('driver', $mailSetting->driver) === 'log')>Log (mailler dosyaya yazılır, gönderilmez)</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="host" value="SMTP Sunucu (host)" />
                        <x-text-input id="host" name="host" type="text" class="mt-1 block w-full" :value="old('host', $mailSetting->host)" placeholder="smtp.example.com" />
                    </div>
                    <div>
                        <x-input-label for="port" value="Port" />
                        <x-text-input id="port" name="port" type="number" class="mt-1 block w-full" :value="old('port', $mailSetting->port)" placeholder="587" min="1" max="65535" />
                        <p class="mt-1 text-xs text-gray-500">Genelde 587 (TLS), 465 (SSL) veya 25.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="username" value="Kullanıcı adı" />
                        <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="old('username', $mailSetting->username)" autocomplete="off" />
                    </div>
                    <div>
                        <x-input-label for="password" value="Şifre" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" placeholder="Değiştirmek için doldurun" autocomplete="new-password" />
                        <p class="mt-1 text-xs text-gray-500">Boş bırakırsanız mevcut şifre değişmez.</p>
                    </div>
                </div>

                <div>
                    <x-input-label for="encryption" value="Şifreleme" />
                    <select id="encryption" name="encryption" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        <option value="" @selected(old('encryption', $mailSetting->encryption) === null || old('encryption', $mailSetting->encryption) === '')>Yok</option>
                        <option value="tls" @selected(old('encryption', $mailSetting->encryption) === 'tls')>TLS</option>
                        <option value="ssl" @selected(old('encryption', $mailSetting->encryption) === 'ssl')>SSL</option>
                    </select>
                </div>

                <div class="pt-2 border-t border-gray-100">
                    <p class="text-sm font-medium text-gray-700 mb-2">Gönderen bilgisi</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="from_address" value="Gönderen e-posta" />
                            <x-text-input id="from_address" name="from_address" type="email" class="mt-1 block w-full" :value="old('from_address', $mailSetting->from_address)" placeholder="noreply@example.com" />
                        </div>
                        <div>
                            <x-input-label for="from_name" value="Gönderen adı" />
                            <x-text-input id="from_name" name="from_name" type="text" class="mt-1 block w-full" :value="old('from_name', $mailSetting->from_name)" placeholder="Mail Yönetimi" />
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
        <p class="text-xs text-gray-500 mb-4">Geçerli mail ayarlarıyla bir test e-postası göndermek için hedef adresi girin.</p>
        <form action="{{ route('admin.mail-settings.test') }}" method="POST" class="flex flex-col sm:flex-row gap-3 items-start sm:items-end">
            @csrf
            <div class="flex-1 w-full">
                <x-input-label for="test_email" value="Test e-posta adresi *" />
                <x-text-input id="test_email" name="test_email" type="email" class="mt-1 block w-full" :value="old('test_email', auth()->user()->email ?? '')" required />
            </div>
            <div>
                <x-primary-button>Test mail gönder</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
