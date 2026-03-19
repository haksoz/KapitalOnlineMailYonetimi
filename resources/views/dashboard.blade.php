<x-app-layout>
    <x-page-toolbar title="Dashboard" />

    <div class="space-y-4 sm:space-y-6">
        {{-- Karşılama ve hızlı kısayollar --}}
        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6">
            <p class="text-gray-600 text-sm sm:text-base mb-4 sm:mb-6">
                Abonelik ve Tedarikçi Fatura Yönetim Sistemi’ne hoş geldiniz.
            </p>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                {{-- Abonelikler: takvim / dönem --}}
                <a href="{{ route('subscriptions.index') }}" class="group flex flex-row lg:flex-col items-center justify-center gap-3 w-full min-h-[56px] sm:min-h-[64px] px-3 py-3.5 rounded-xl border-2 border-gray-200 bg-white shadow-sm hover:border-slate-500 hover:bg-slate-50 hover:shadow-md active:scale-[0.98] active:bg-slate-100 transition touch-manipulation">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 group-hover:bg-slate-200 group-hover:text-slate-800" aria-hidden="true">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" />
                        </svg>
                    </span>
                    <span class="font-semibold text-sm sm:text-base text-gray-900 text-center leading-tight">Abonelikler</span>
                </a>
                {{-- Siparişler: liste / sipariş --}}
                <a href="{{ route('pending-billings.index') }}" class="group flex flex-row lg:flex-col items-center justify-center gap-3 w-full min-h-[56px] sm:min-h-[64px] px-3 py-3.5 rounded-xl border-2 border-gray-200 bg-white shadow-sm hover:border-slate-500 hover:bg-slate-50 hover:shadow-md active:scale-[0.98] active:bg-slate-100 transition touch-manipulation">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 group-hover:bg-slate-200 group-hover:text-slate-800" aria-hidden="true">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                        </svg>
                    </span>
                    <span class="font-semibold text-sm sm:text-base text-gray-900 text-center leading-tight">Siparişler</span>
                </a>
                {{-- Abone Takip: grafik / izleme --}}
                <a href="{{ route('subscription-monitor.index') }}" class="group flex flex-row lg:flex-col items-center justify-center gap-3 w-full min-h-[56px] sm:min-h-[64px] px-3 py-3.5 rounded-xl border-2 border-gray-200 bg-white shadow-sm hover:border-slate-500 hover:bg-slate-50 hover:shadow-md active:scale-[0.98] active:bg-slate-100 transition touch-manipulation">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 group-hover:bg-slate-200 group-hover:text-slate-800" aria-hidden="true">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </span>
                    <span class="font-semibold text-sm sm:text-base text-gray-900 text-center leading-tight">Abone Takip</span>
                </a>
                {{-- Satış E-fatura: belge --}}
                <a href="{{ route('sales-invoices.index') }}" class="group flex flex-row lg:flex-col items-center justify-center gap-3 w-full min-h-[56px] sm:min-h-[64px] px-3 py-3.5 rounded-xl border-2 border-gray-200 bg-white shadow-sm hover:border-slate-500 hover:bg-slate-50 hover:shadow-md active:scale-[0.98] active:bg-slate-100 transition touch-manipulation">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 group-hover:bg-slate-200 group-hover:text-slate-800" aria-hidden="true">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </span>
                    <span class="font-semibold text-sm sm:text-base text-gray-900 text-center leading-tight">Satış E-fatura</span>
                </a>
            </div>
        </div>

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
    </div>
</x-app-layout>
