<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sipariş (Pending Billing) API Önizleme</h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Açıklama --}}
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
            <strong>Entegrasyon Veri Standardı:</strong> Bu sayfa, sipariş/fatura beklentisi kayıtlarının dış entegrasyona
            gönderilecek standart JSON formatını göstermektedir. Müşteri eşleşmesi için <span class="font-semibold">customer.tax_number</span> ana alandır.
        </div>

        {{-- Filtreler --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex flex-col sm:flex-row gap-3 flex-wrap">
                <select id="statusFilter" class="rounded-lg border-gray-300 text-sm focus:ring-slate-500 focus:border-slate-500">
                    <option value="">Tüm Durumlar</option>
                    <option value="pending">Beklemede</option>
                    <option value="invoiced">Faturalandı</option>
                    <option value="postponed">Ertelendi</option>
                    <option value="cancelled">İptal</option>
                </select>
                <select id="yearFilter" class="rounded-lg border-gray-300 text-sm focus:ring-slate-500 focus:border-slate-500">
                    <option value="">Tüm Yıllar</option>
                    @for($y = now()->year; $y >= now()->year - 4; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
                <select id="monthFilter" class="rounded-lg border-gray-300 text-sm focus:ring-slate-500 focus:border-slate-500">
                    <option value="">Tüm Aylar</option>
                    @foreach(['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'] as $i => $m)
                        <option value="{{ $i + 1 }}">{{ $m }}</option>
                    @endforeach
                </select>
                <button onclick="loadData()" class="inline-flex items-center px-4 py-2 bg-slate-700 text-white text-sm font-medium rounded-lg hover:bg-slate-800">
                    Getir
                </button>
                <button onclick="copyAll()" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">
                    Tümünü Kopyala
                </button>
            </div>
        </div>

        {{-- Sonuç Alanı --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Liste (<span id="totalCount">0</span> kayıt)</h3>
                    <span class="text-xs text-gray-400">API Format</span>
                </div>
                <div id="listContainer" class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto">
                    <p class="px-4 py-8 text-center text-sm text-gray-400">Verileri yüklemek için "Getir" butonuna tıklayın.</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">JSON Önizleme</h3>
                    <button onclick="copySelected()" class="text-xs text-emerald-600 hover:text-emerald-800 font-medium">Kopyala</button>
                </div>
                <pre id="jsonPreview" class="p-4 text-xs text-gray-700 bg-gray-50 rounded-b-xl overflow-auto max-h-[600px] font-mono whitespace-pre-wrap">Listeden bir kayıt seçin...</pre>
            </div>
        </div>

        {{-- Alan Açıklamaları --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-4 py-3 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700">Alan Sözleşmesi</h3>
            </div>
            <div class="p-4 overflow-x-auto">
                <table class="min-w-full text-xs text-left">
                    <thead>
                        <tr class="text-gray-500 uppercase tracking-wider">
                            <th class="pb-2 pr-4 font-semibold">API Alanı</th>
                            <th class="pb-2 pr-4 font-semibold">DB Karşılığı</th>
                            <th class="pb-2 font-semibold">Açıklama</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        <tr><td class="py-1.5 pr-4 font-mono text-slate-700">id</td><td class="py-1.5 pr-4 font-mono text-gray-400">id</td><td class="py-1.5">Sipariş ID</td></tr>
                        <tr class="bg-slate-50"><td class="py-1.5 pr-4 font-mono text-slate-700">customer.uuid</td><td class="py-1.5 pr-4 font-mono text-gray-400">subscription.customerCari.uuid</td><td class="py-1.5">Müşteri UUID</td></tr>
                        <tr class="bg-slate-50"><td class="py-1.5 pr-4 font-mono text-slate-700">customer.name</td><td class="py-1.5 pr-4 font-mono text-gray-400">subscription.customerCari.name</td><td class="py-1.5">Müşteri adı</td></tr>
                        <tr class="bg-slate-50"><td class="py-1.5 pr-4 font-mono text-slate-700">customer.country_code</td><td class="py-1.5 pr-4 font-mono text-gray-400">subscription.customerCari.country_code</td><td class="py-1.5">Ülke kodu</td></tr>
                        <tr class="bg-slate-50"><td class="py-1.5 pr-4 font-mono text-slate-700">customer.tax_number</td><td class="py-1.5 pr-4 font-mono text-gray-400">subscription.customerCari.tax_number</td><td class="py-1.5 font-semibold text-amber-700">Ana eşleşme alanı</td></tr>
                        <tr class="bg-blue-50"><td class="py-1.5 pr-4 font-mono text-slate-700">subscription.uuid</td><td class="py-1.5 pr-4 font-mono text-gray-400">subscription.uuid</td><td class="py-1.5">Abonelik UUID</td></tr>
                        <tr class="bg-blue-50"><td class="py-1.5 pr-4 font-mono text-slate-700">subscription.internal_contract_no</td><td class="py-1.5 pr-4 font-mono text-gray-400">subscription.sozlesme_no</td><td class="py-1.5">Sözleşme no</td></tr>
                        <tr class="bg-blue-50"><td class="py-1.5 pr-4 font-mono text-slate-700">subscription.product_name</td><td class="py-1.5 pr-4 font-mono text-gray-400">subscription.product.name</td><td class="py-1.5">Ürün adı</td></tr>
                        <tr class="bg-blue-50"><td class="py-1.5 pr-4 font-mono text-slate-700">subscription.billing_cycle</td><td class="py-1.5 pr-4 font-mono text-gray-400">subscription.faturalama_periyodu</td><td class="py-1.5">Faturalama periyodu</td></tr>
                        <tr><td class="py-1.5 pr-4 font-mono text-slate-700">period_start</td><td class="py-1.5 pr-4 font-mono text-gray-400">period_start</td><td class="py-1.5">Dönem başlangıcı</td></tr>
                        <tr><td class="py-1.5 pr-4 font-mono text-slate-700">period_end</td><td class="py-1.5 pr-4 font-mono text-gray-400">period_end</td><td class="py-1.5">Dönem bitişi</td></tr>
                        <tr><td class="py-1.5 pr-4 font-mono text-slate-700">status</td><td class="py-1.5 pr-4 font-mono text-gray-400">status</td><td class="py-1.5">pending / invoiced / postponed / cancelled</td></tr>
                        <tr><td class="py-1.5 pr-4 font-mono text-slate-700">expected_sales_tl</td><td class="py-1.5 pr-4 font-mono text-gray-400">expected_satis_tl</td><td class="py-1.5">Beklenen satış tutarı (TL)</td></tr>
                        <tr><td class="py-1.5 pr-4 font-mono text-slate-700">actual_sales_tl</td><td class="py-1.5 pr-4 font-mono text-gray-400">actual_satis_tl</td><td class="py-1.5">Gerçekleşen satış tutarı (TL)</td></tr>
                        <tr><td class="py-1.5 pr-4 font-mono text-slate-700">created_at</td><td class="py-1.5 pr-4 font-mono text-gray-400">created_at</td><td class="py-1.5">Oluşturulma tarihi (ISO 8601)</td></tr>
                        <tr><td class="py-1.5 pr-4 font-mono text-slate-700">updated_at</td><td class="py-1.5 pr-4 font-mono text-gray-400">updated_at</td><td class="py-1.5">Son güncelleme tarihi (ISO 8601)</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tüm JSON --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Tüm Veri (JSON Array)</h3>
                <button onclick="copyAll()" class="text-xs text-emerald-600 hover:text-emerald-800 font-medium">Kopyala</button>
            </div>
            <pre id="allJsonPreview" class="p-4 text-xs text-gray-700 bg-gray-50 rounded-b-xl overflow-auto max-h-[400px] font-mono whitespace-pre-wrap">Henüz veri yüklenmedi...</pre>
        </div>
    </div>

    <script>
        let allData = [];
        let selectedIndex = null;

        const statusColors = {
            pending:   'bg-yellow-100 text-yellow-800',
            invoiced:  'bg-green-100 text-green-800',
            postponed: 'bg-blue-100 text-blue-800',
            cancelled: 'bg-red-100 text-red-800',
        };

        const statusLabels = {
            pending:   'Beklemede',
            invoiced:  'Faturalandı',
            postponed: 'Ertelendi',
            cancelled: 'İptal',
        };

        async function loadData() {
            const status = document.getElementById('statusFilter').value;
            const year   = document.getElementById('yearFilter').value;
            const month  = document.getElementById('monthFilter').value;

            const url = new URL('{{ route("admin.integration.pending-billing-preview.data") }}', window.location.origin);
            if (status) url.searchParams.set('status', status);
            if (year)   url.searchParams.set('period_year', year);
            if (month)  url.searchParams.set('period_month', month);

            const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            const json = await res.json();
            allData = json.data || [];

            document.getElementById('totalCount').textContent = json.meta?.total ?? allData.length;
            renderList();
            document.getElementById('allJsonPreview').textContent = JSON.stringify(allData, null, 2);
        }

        function renderList() {
            const container = document.getElementById('listContainer');
            if (!allData.length) {
                container.innerHTML = '<p class="px-4 py-8 text-center text-sm text-gray-400">Kayıt bulunamadı.</p>';
                return;
            }

            container.innerHTML = allData.map((item, i) => {
                const color = statusColors[item.status] ?? 'bg-gray-100 text-gray-700';
                const label = statusLabels[item.status] ?? item.status;
                const expSales = item.expected_sales_tl != null ? Number(item.expected_sales_tl).toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ₺' : '-';
                const actSales = item.actual_sales_tl  != null ? Number(item.actual_sales_tl).toLocaleString('tr-TR',  {minimumFractionDigits: 2}) + ' ₺' : '-';
                return `
                <div onclick="selectItem(${i})" class="px-4 py-3 cursor-pointer hover:bg-slate-50 transition-colors ${selectedIndex === i ? 'bg-slate-100' : ''}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-900 truncate">${item.customer?.name ?? '-'}</p>
                            <p class="text-xs text-gray-500 mt-0.5">${item.subscription?.product_name ?? '-'} &bull; ${item.period_start ?? '-'} / ${item.period_end ?? '-'}</p>
                            <p class="text-xs text-gray-400 mt-0.5">Beklenen: ${expSales} &bull; Gerçekleşen: ${actSales}</p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${color} shrink-0">${label}</span>
                    </div>
                </div>`;
            }).join('');
        }

        function selectItem(i) {
            selectedIndex = i;
            document.getElementById('jsonPreview').textContent = JSON.stringify(allData[i], null, 2);
            renderList();
        }

        function copySelected() {
            if (selectedIndex === null) return;
            navigator.clipboard.writeText(JSON.stringify(allData[selectedIndex], null, 2));
            showToast('Kopyalandı!');
        }

        function copyAll() {
            if (!allData.length) return;
            navigator.clipboard.writeText(JSON.stringify(allData, null, 2));
            showToast('Tümü kopyalandı!');
        }

        function showToast(msg) {
            const el = document.createElement('div');
            el.textContent = msg;
            el.className = 'fixed bottom-4 right-4 bg-slate-800 text-white text-sm px-4 py-2 rounded-lg shadow-lg z-50';
            document.body.appendChild(el);
            setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 2000);
        }

        loadData();
    </script>
</x-app-layout>
