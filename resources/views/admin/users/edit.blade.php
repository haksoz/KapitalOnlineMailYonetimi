<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Kullanıcı Düzenle">
        <x-slot name="left">
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation" aria-label="Geri">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-xl">
        <p class="text-sm text-gray-600 mb-4">{{ $user->name }} — {{ $user->email }}</p>

        <form action="{{ route('admin.users.update', $user) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="space-y-4">
                <div>
                    <x-input-label for="role" value="Rol *" />
                    <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                        <option value="user" @selected(old('role', $user->role) === 'user')>Kullanıcı</option>
                        <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" value="1" class="rounded border-gray-300 text-slate-600 focus:ring-slate-500"
                        @checked(old('is_active', $user->is_active))>
                    <x-input-label for="is_active" value="Aktif (giriş yapabilir)" class="!mb-0" />
                </div>
                <p class="text-xs text-gray-500">Pasif kullanıcılar giriş yapamaz.</p>

                <div class="border-t border-gray-200 pt-4">
                    <p class="text-sm font-medium text-gray-700 mb-2">Parola sıfırla</p>
                    <p class="text-xs text-gray-500 mb-3">Yeni parola girmek zorunlu değildir. Sadece değiştirmek istediğinizde doldurun.</p>
                    <div class="space-y-3">
                        <div>
                            <x-input-label for="password" value="Yeni parola" />
                            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="password_confirmation" value="Yeni parola (tekrar)" />
                            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <x-primary-button>Güncelle</x-primary-button>
                <a href="{{ route('admin.users.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">İptal</a>
            </div>
        </form>
    </div>
</x-app-layout>
