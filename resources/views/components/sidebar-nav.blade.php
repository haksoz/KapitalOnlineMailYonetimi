@props(['mobile' => false])

<nav {{ $attributes->merge(['role' => 'navigation']) }}>
    <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" :close-on-click="$mobile">Dashboard</x-sidebar-link>

    <x-sidebar-group id="master" label="Master Veriler">
        <x-sidebar-link :href="route('caris.index')" :active="request()->routeIs('caris.*')" :close-on-click="$mobile">Cariler</x-sidebar-link>
        <x-sidebar-link :href="route('service-providers.index')" :active="request()->routeIs('service-providers.*')" :close-on-click="$mobile">Servis Sağlayıcılar</x-sidebar-link>
        <x-sidebar-link :href="route('products.index')" :active="request()->routeIs('products.*')" :close-on-click="$mobile">Ürünler</x-sidebar-link>
        <x-sidebar-link :href="route('exchange-rates.index')" :active="request()->routeIs('exchange-rates.*')" :close-on-click="$mobile">Kurlar</x-sidebar-link>
    </x-sidebar-group>

    <x-sidebar-group id="subscriptions" label="Abonelikler">
        <x-sidebar-link :href="route('subscriptions.index')" :active="request()->routeIs('subscriptions.*') && ! request()->routeIs('pending-billings.*') && ! request()->routeIs('sales-invoices.*') && ! request()->routeIs('expense-settlements.*')" :close-on-click="$mobile">Abonelikler</x-sidebar-link>
        <x-sidebar-link :href="route('pending-billings.index')" :active="request()->routeIs('pending-billings.*')" :close-on-click="$mobile">Siparişler</x-sidebar-link>
        <x-sidebar-link :href="route('sales-invoices.index')" :active="request()->routeIs('sales-invoices.*')" :close-on-click="$mobile">Satış E-Fatura</x-sidebar-link>
        <x-sidebar-link :href="route('expense-settlements.index')" :active="request()->routeIs('expense-settlements.*')" :close-on-click="$mobile">Giderleştirmeler</x-sidebar-link>
        <x-sidebar-link :href="route('subscription-monitor.index')" :active="request()->routeIs('subscription-monitor.*')" :close-on-click="$mobile">Abone Takip</x-sidebar-link>
    </x-sidebar-group>

    <x-sidebar-group id="admin" label="Admin">
        @if(Auth::user()?->isAdmin())
            <x-sidebar-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" :close-on-click="$mobile">Kullanıcı Yönetimi</x-sidebar-link>
            <x-sidebar-link :href="route('admin.mail-settings.edit')" :active="request()->routeIs('admin.mail-settings.*')" :close-on-click="$mobile">Mail Yönetimi</x-sidebar-link>
            <x-sidebar-link :href="route('admin.notifications.edit')" :active="request()->routeIs('admin.notifications.*')" :close-on-click="$mobile">Bildiri Yönetimi</x-sidebar-link>
            <x-sidebar-link :href="route('admin.reports.cari-ledger')" :active="request()->routeIs('admin.reports.cari-ledger*')" :close-on-click="$mobile">Hesap Dökümü Raporu</x-sidebar-link>
        @endif
        <x-sidebar-link :href="route('triggers.index')" :active="request()->routeIs('triggers.*')" :close-on-click="$mobile">Tetikleyiciler</x-sidebar-link>
    </x-sidebar-group>

    @if(Auth::user()?->isAdmin())
        <x-sidebar-group id="integration" label="Entegrasyon">
            <x-sidebar-link :href="route('admin.api-settings.index')" :active="request()->routeIs('admin.api-settings.*')" :close-on-click="$mobile">API Ayarları</x-sidebar-link>
            <x-sidebar-link :href="route('admin.integration.cari-preview')" :active="request()->routeIs('admin.integration.cari-preview')" :close-on-click="$mobile">Cari API Önizleme</x-sidebar-link>
            <x-sidebar-link :href="route('admin.integration.subscription-preview')" :active="request()->routeIs('admin.integration.subscription-preview')" :close-on-click="$mobile">Abonelik API Önizleme</x-sidebar-link>
            <x-sidebar-link :href="route('admin.integration.product-preview')" :active="request()->routeIs('admin.integration.product-preview')" :close-on-click="$mobile">Ürün API Önizleme</x-sidebar-link>
            <x-sidebar-link :href="route('admin.integration.open-order-preview')" :active="request()->routeIs('admin.integration.open-order-preview')" :close-on-click="$mobile">Açık Siparişler API</x-sidebar-link>
            <x-sidebar-link :href="route('admin.integration.invoiced-order-preview')" :active="request()->routeIs('admin.integration.invoiced-order-preview')" :close-on-click="$mobile">Faturalanmış Siparişler API</x-sidebar-link>
        </x-sidebar-group>
    @endif
</nav>
