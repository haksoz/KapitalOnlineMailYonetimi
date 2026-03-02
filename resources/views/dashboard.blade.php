<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg sm:text-xl font-semibold text-gray-800 truncate">Dashboard</h1>
    </x-slot>

    <div class="space-y-4 sm:space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6">
            <p class="text-gray-600 text-sm sm:text-base mb-4 sm:mb-6">Abonelik ve Tedarikçi Fatura Yönetim Sistemi’ne hoş geldiniz.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <a href="{{ route('companies.index') }}" class="flex flex-col min-h-[72px] sm:min-h-0 p-4 rounded-lg border border-gray-200 hover:border-slate-400 hover:bg-gray-50 active:bg-gray-100 transition touch-manipulation">
                    <span class="font-medium text-gray-900">Müşteriler</span>
                    <p class="text-sm text-gray-500 mt-1">Müşteri listesi ve yönetimi</p>
                </a>
                <a href="{{ route('suppliers.index') }}" class="flex flex-col min-h-[72px] sm:min-h-0 p-4 rounded-lg border border-gray-200 hover:border-slate-400 hover:bg-gray-50 active:bg-gray-100 transition touch-manipulation">
                    <span class="font-medium text-gray-900">Tedarikçiler</span>
                    <p class="text-sm text-gray-500 mt-1">Tedarikçi listesi ve yönetimi</p>
                </a>
                <a href="{{ route('service-providers.index') }}" class="flex flex-col min-h-[72px] sm:min-h-0 p-4 rounded-lg border border-gray-200 hover:border-slate-400 hover:bg-gray-50 active:bg-gray-100 transition touch-manipulation">
                    <span class="font-medium text-gray-900">Servis Sağlayıcılar</span>
                    <p class="text-sm text-gray-500 mt-1">Microsoft, Hostinger vb.</p>
                </a>
                <a href="{{ route('products.index') }}" class="flex flex-col min-h-[72px] sm:min-h-0 p-4 rounded-lg border border-gray-200 hover:border-slate-400 hover:bg-gray-50 active:bg-gray-100 transition touch-manipulation">
                    <span class="font-medium text-gray-900">Ürünler</span>
                    <p class="text-sm text-gray-500 mt-1">Ürün listesi ve yönetimi</p>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
