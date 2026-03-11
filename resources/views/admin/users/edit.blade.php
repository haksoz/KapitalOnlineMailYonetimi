<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.users.index') }}" class="text-gray-500 hover:text-gray-700">&larr;</a>
            <h1 class="text-xl font-semibold text-gray-800">Kullanıcı Düzenle</h1>
        </div>
    </x-slot>

    <x-flash-messages />

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
            </div>

            <div class="mt-6 flex gap-3">
                <x-primary-button>Güncelle</x-primary-button>
                <a href="{{ route('admin.users.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">İptal</a>
            </div>
        </form>
    </div>
</x-app-layout>
