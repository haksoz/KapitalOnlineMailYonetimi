<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg sm:text-xl font-semibold text-gray-800 truncate">Dashboard</h1>
    </x-slot>

    <div class="space-y-4 sm:space-y-6">
        {{-- Yıl geneli özet --}}
        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                {{ $stats['year'] ?? now()->year }} Yılı Özet
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-4 rounded-lg border border-gray-200">
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Toplam Satış (KDV hariç)</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900">
                        {{ number_format($stats['total_sales_year'] ?? 0, 2, ',', '.') }} ₺
                    </div>
                    <div class="mt-2 text-xs text-gray-500 space-y-0.5">
                        <div>
                            <span class="font-medium text-gray-700">Sistem toplamı:</span>
                            {{ number_format($stats['total_sales_year_system'] ?? 0, 2, ',', '.') }} ₺
                        </div>
                        @php
                            $salesDiff = $stats['total_sales_diff_year'] ?? 0;
                        @endphp
                        @if ($salesDiff != 0)
                            <div>
                                <span class="font-medium text-gray-700">Farklar toplamı:</span>
                                <span class="{{ $salesDiff >= 0 ? 'text-amber-700' : 'text-slate-700' }}">
                                    {{ number_format($salesDiff, 2, ',', '.') }} ₺
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="p-4 rounded-lg border border-gray-200">
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Toplam Alış (KDV hariç)</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900">
                        {{ number_format($stats['total_purchases_year'] ?? 0, 2, ',', '.') }} ₺
                    </div>
                    <div class="mt-2 text-xs text-gray-500 space-y-0.5">
                        <div>
                            <span class="font-medium text-emerald-700">Faturalanmış Alış:</span>
                            <span class="font-semibold text-emerald-700">
                                {{ number_format($stats['purchases_invoiced_year'] ?? 0, 2, ',', '.') }} ₺
                            </span>
                        </div>
                        <div>
                            <span class="font-medium text-rose-700">Faturalanmamış Alış:</span>
                            <span class="font-semibold text-rose-700">
                                {{ number_format($stats['purchases_pending_year'] ?? 0, 2, ',', '.') }} ₺
                            </span>
                        </div>
                    </div>
                </div>
                <div class="p-4 rounded-lg border border-gray-200">
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Yıl Bilançosu (Satış − Alış)</div>
                    @php
                        $yearBalance = $stats['year_balance'] ?? 0;
                        $yearBalanceClass = $yearBalance >= 0 ? 'text-emerald-700' : 'text-rose-700';
                    @endphp
                    <div class="mt-1 text-2xl font-semibold {{ $yearBalanceClass }}">
                        {{ number_format($yearBalance, 2, ',', '.') }} ₺
                    </div>
                </div>
                <div class="p-4 rounded-lg border border-gray-200">
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Aktif Abone / Abonelik</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900">
                        {{ $stats['active_customer_count'] ?? 0 }}
                        <span class="text-sm font-normal text-gray-500">/ {{ $stats['active_subscriptions_count'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Önceki ay ve bu ay görünümü --}}
        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Dönem Görünümü</h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {{-- Önceki ay --}}
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">
                        Önceki Ay — {{ $stats['prev_month_label'] ?? now()->copy()->subMonthNoOverflow()->locale('tr')->translatedFormat('F Y') }}
                    </div>
                    <dl class="mt-2 space-y-1 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Beklenen satış (KDV hariç)</dt>
                            <dd class="font-medium text-gray-900">
                                {{ number_format($stats['prev_month_expected_sales'] ?? 0, 2, ',', '.') }} ₺
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Beklenen alış (KDV hariç)</dt>
                            <dd class="font-medium text-gray-900">
                                {{ number_format($stats['prev_month_expected_purchases'] ?? 0, 2, ',', '.') }} ₺
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Kesinleşen satış (KDV hariç)</dt>
                            @php
                                $pActSales = $stats['prev_month_actual_sales'] ?? 0;
                            @endphp
                            <dd class="font-medium text-gray-900">
                                {{ number_format($pActSales, 2, ',', '.') }} ₺
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Kesinleşen alış (KDV hariç)</dt>
                            @php
                                $pActPurch = $stats['prev_month_actual_purchases'] ?? 0;
                            @endphp
                            <dd class="font-medium text-gray-900">
                                {{ number_format($pActPurch, 2, ',', '.') }} ₺
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Ay bilançosu (Kesinleşen satış − alış)</dt>
                            @php
                                $pBal = $stats['prev_month_actual_balance'] ?? 0;
                                $pBalClass = $pBal >= 0 ? 'text-emerald-700' : 'text-rose-700';
                            @endphp
                            <dd class="font-medium {{ $pBalClass }}">
                                {{ number_format($pBal, 2, ',', '.') }} ₺
                            </dd>
                        </div>
                    </dl>
                </div>
                {{-- Bu Ay --}}
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">
                        Bu Ay — {{ $stats['this_month_label'] ?? now()->locale('tr')->translatedFormat('F Y') }}
                    </div>
                    <dl class="mt-2 space-y-1 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Bekleyen sipariş sayısı</dt>
                            <dd class="font-medium text-gray-900">{{ $stats['pending_billings_count'] ?? 0 }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Beklenen satış (KDV hariç)</dt>
                            <dd class="font-medium text-gray-900">
                                {{ number_format($stats['this_month_expected_sales'] ?? 0, 2, ',', '.') }} ₺
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Beklenen alış (KDV hariç)</dt>
                            <dd class="font-medium text-gray-900">
                                {{ number_format($stats['this_month_expected_purchases'] ?? 0, 2, ',', '.') }} ₺
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Kesinleşen satış (KDV hariç)</dt>
                            @php
                                $tActSales = $stats['this_month_actual_sales'] ?? 0;
                            @endphp
                            <dd class="font-medium text-gray-900">
                                {{ number_format($tActSales, 2, ',', '.') }} ₺
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Kesinleşen alış (KDV hariç)</dt>
                            @php
                                $tActPurch = $stats['this_month_actual_purchases'] ?? 0;
                            @endphp
                            <dd class="font-medium text-gray-900">
                                {{ number_format($tActPurch, 2, ',', '.') }} ₺
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Ay bilançosu (Kesinleşen satış − alış)</dt>
                            @php
                                $tBal = $stats['this_month_actual_balance'] ?? 0;
                                $tBalClass = $tBal >= 0 ? 'text-emerald-700' : 'text-rose-700';
                            @endphp
                            <dd class="font-medium {{ $tBalClass }}">
                                {{ number_format($tBal, 2, ',', '.') }} ₺
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Hızlı erişim kutuları --}}
        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6">
            <p class="text-gray-600 text-sm sm:text-base mb-4 sm:mb-6">
                Abonelik ve Tedarikçi Fatura Yönetim Sistemi’ne hoş geldiniz.
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <a href="{{ route('caris.index') }}" class="flex flex-col min-h-[72px] sm:min-h-0 p-4 rounded-lg border border-gray-200 hover:border-slate-400 hover:bg-gray-50 active:bg-gray-100 transition touch-manipulation">
                    <span class="font-medium text-gray-900">Cariler</span>
                    <p class="text-sm text-gray-500 mt-1">Müşteri ve tedarikçi carilerinin yönetimi</p>
                </a>
                <a href="{{ route('service-providers.index') }}" class="flex flex-col min-h-[72px] sm:min-h-0 p-4 rounded-lg border border-gray-200 hover:border-slate-400 hover:bg-gray-50 active:bg-gray-100 transition touch-manipulation">
                    <span class="font-medium text-gray-900">Servis Sağlayıcılar</span>
                    <p class="text-sm text-gray-500 mt-1">Microsoft, Hostinger vb.</p>
                </a>
                <a href="{{ route('products.index') }}" class="flex flex-col min-h-[72px] sm:min-h-0 p-4 rounded-lg border border-gray-200 hover:border-slate-400 hover:bg-gray-50 active:bg-gray-100 transition touch-manipulation">
                    <span class="font-medium text-gray-900">Ürünler</span>
                    <p class="text-sm text-gray-500 mt-1">Ürün listesi ve yönetimi</p>
                </a>
                <a href="{{ route('subscription-monitor.index') }}" class="flex flex-col min-h-[72px] sm:min-h-0 p-4 rounded-lg border border-gray-200 hover:border-slate-400 hover:bg-gray-50 active:bg-gray-100 transition touch-manipulation">
                    <span class="font-medium text-gray-900">Abone Takip</span>
                    <p class="text-sm text-gray-500 mt-1">Cari bazında sipariş / fatura görünümü</p>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
