<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} @isset($title) — {{ $title }} @endisset</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100 min-h-screen" x-data="{ sidebarOpen: false }" :class="{ 'overflow-hidden': sidebarOpen }">
    <div class="min-h-screen flex flex-col lg:flex-row">
        <!-- Sidebar (desktop): sabit genişlik, opak arka plan, z-index ile üstte -->
        <aside class="layout-desktop-sidebar hidden lg:flex lg:flex-shrink-0 lg:w-64 lg:flex-col lg:fixed lg:inset-y-0 lg:left-0 lg:z-20 bg-slate-800 text-white" aria-label="Ana menü">
            <div class="flex flex-col flex-grow pt-5 pb-4 overflow-y-auto overscroll-contain w-full bg-slate-800 min-h-full">
                <div class="flex items-center flex-shrink-0 px-4">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 min-h-[44px] items-center">
                        <x-application-logo class="block h-9 w-auto fill-current text-white shrink-0" />
                        <span class="font-semibold text-base lg:text-lg truncate">Mail Yönetimi</span>
                    </a>
                </div>
                <nav class="mt-6 flex-1 px-3 space-y-0.5" role="navigation">
                    <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700 hover:text-white' }}">
                        {{ __('Dashboard') }}
                    </a>
                    <div class="pt-4 pb-2">
                        <p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Master Veriler</p>
                    </div>
                    <a href="{{ route('caris.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('caris.*') ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700 hover:text-white' }}">Cariler</a>
                    <a href="{{ route('service-providers.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('service-providers.*') ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700 hover:text-white' }}">Servis Sağlayıcılar</a>
                    <a href="{{ route('products.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('products.*') ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700 hover:text-white' }}">Ürünler</a>
                    <a href="{{ route('exchange-rates.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('exchange-rates.*') ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700 hover:text-white' }}">Kurlar</a>
                    <div class="pt-4 pb-2">
                        <p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Abonelikler</p>
                    </div>
                    <a href="{{ route('subscriptions.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('subscriptions.*') && !request()->routeIs('pending-billings.*') && !request()->routeIs('sales-invoices.*') ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700 hover:text-white' }}">Abonelikler</a>
                    <a href="{{ route('pending-billings.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('pending-billings.*') ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700 hover:text-white' }}">Siparişler</a>
                    <a href="{{ route('sales-invoices.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('sales-invoices.*') ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700 hover:text-white' }}">Satış Faturası</a>
                    <div class="pt-4 pb-2">
                        <p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Admin</p>
                    </div>
                    <a href="{{ route('triggers.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('triggers.*') ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700 hover:text-white' }}">Tetikleyiciler</a>
                </nav>
            </div>
        </aside>

        <!-- Mobile sidebar overlay -->
        <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false" aria-hidden="true" x-cloak></div>

        <!-- Mobile sidebar panel -->
        <aside x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="fixed inset-y-0 left-0 z-50 w-64 max-w-[85vw] bg-slate-800 text-white lg:hidden flex flex-col" role="dialog" aria-label="Menü" x-cloak>
            <div class="flex items-center justify-between flex-shrink-0 h-14 px-4 border-b border-slate-700">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 min-h-[44px] items-center" @click="sidebarOpen = false">
                    <x-application-logo class="block h-9 w-auto fill-current text-white shrink-0" />
                    <span class="font-semibold truncate">Mail Yönetimi</span>
                </a>
                <button type="button" @click="sidebarOpen = false" class="min-w-[44px] min-h-[44px] flex items-center justify-center -mr-2 rounded-lg text-gray-400 hover:text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white/30" aria-label="Menüyü kapat">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto overscroll-contain py-4 px-3 space-y-0.5" role="navigation">
                <a href="{{ route('dashboard') }}" class="flex items-center min-h-[44px] px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('dashboard') ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700 hover:text-white' }}" @click="sidebarOpen = false">Dashboard</a>
                <div class="pt-4 pb-2"><p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Master Veriler</p></div>
                <a href="{{ route('caris.index') }}" class="flex items-center min-h-[44px] px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('caris.*') ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700 hover:text-white' }}" @click="sidebarOpen = false">Cariler</a>
                <a href="{{ route('service-providers.index') }}" class="flex items-center min-h-[44px] px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('service-providers.*') ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700 hover:text-white' }}" @click="sidebarOpen = false">Servis Sağlayıcılar</a>
                <a href="{{ route('products.index') }}" class="flex items-center min-h-[44px] px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('products.*') ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700 hover:text-white' }}" @click="sidebarOpen = false">Ürünler</a>
                <a href="{{ route('exchange-rates.index') }}" class="flex items-center min-h-[44px] px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('exchange-rates.*') ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700 hover:text-white' }}" @click="sidebarOpen = false">Kurlar</a>
                <div class="pt-4 pb-2"><p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Abonelikler</p></div>
                <a href="{{ route('subscriptions.index') }}" class="flex items-center min-h-[44px] px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('subscriptions.*') && !request()->routeIs('pending-billings.*') && !request()->routeIs('sales-invoices.*') ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700 hover:text-white' }}" @click="sidebarOpen = false">Abonelikler</a>
                <a href="{{ route('pending-billings.index') }}" class="flex items-center min-h-[44px] px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('pending-billings.*') ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700 hover:text-white' }}" @click="sidebarOpen = false">Siparişler</a>
                <a href="{{ route('sales-invoices.index') }}" class="flex items-center min-h-[44px] px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('sales-invoices.*') ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700 hover:text-white' }}" @click="sidebarOpen = false">Satış Faturası</a>
                <div class="pt-4 pb-2"><p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Admin</p></div>
                <a href="{{ route('triggers.index') }}" class="flex items-center min-h-[44px] px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('triggers.*') ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700 hover:text-white' }}" @click="sidebarOpen = false">Tetikleyiciler</a>
            </nav>
        </aside>

        <div class="flex flex-col flex-1 w-full min-w-0 lg:ml-64">
            <!-- Top header -->
            <header class="bg-white border-b border-gray-200 sticky top-0 z-30 shrink-0">
                <div class="flex items-center justify-between gap-2 min-h-14 h-14 px-3 sm:px-6 lg:px-8">
                    <button type="button" @click="sidebarOpen = true" class="lg:hidden min-w-[44px] min-h-[44px] flex items-center justify-center -ml-1 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2" aria-label="Menüyü aç">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    @isset($header)
                        <div class="flex-1 min-w-0 flex items-center">
                            <div class="min-w-0 truncate">
                                {{ $header }}
                            </div>
                        </div>
                    @else
                        <div class="flex-1 min-w-0"></div>
                    @endisset
                    <div class="flex items-center shrink-0">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button type="button" class="inline-flex items-center min-h-[44px] px-2 sm:px-3 py-2 border border-transparent text-sm font-medium rounded-lg text-gray-600 bg-white hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                                    <span class="truncate max-w-[140px] sm:max-w-[200px]">{{ Auth::user()->name }}</span>
                                    <svg class="ms-1.5 h-4 w-4 shrink-0 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">{{ __('Log Out') }}</x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>
            </header>

            <main class="flex-1 w-full min-w-0 p-4 sm:p-6 lg:p-8 overflow-x-hidden">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
