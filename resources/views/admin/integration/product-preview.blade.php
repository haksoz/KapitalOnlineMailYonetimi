<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ürün API Önizleme</h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Aciklama --}}
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
            <strong>Entegrasyon Veri Standardi:</strong> Bu sayfa, urun verilerinin dis entegrasyona gonderilecek
            standart JSON formatini gostermektedir. Sadece satis fiyatlari (USD) dahil edilmistir.
        </div>

        {{-- Filtre --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <input type="text" id="searchInput" placeholder="Urun adi veya stok kodu..." class="rounded-lg border-gray-300 text-sm focus:ring-slate-500 focus:border-slate-500 flex-1 max-w-sm">
                <button onclick="loadData()" class="inline-flex items-center px-4 py-2 bg-slate-700 text-white text-sm font-medium rounded-lg hover:bg-slate-800">
                    Getir
                </button>
                <button onclick="copyAll()" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">
                    Tumunu Kopyala
                </button>
            </div>
        </div>

        {{-- Sonuc Alani --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Liste (<span id="totalCount">0</span> kayit)</h3>
                    <span class="text-xs text-gray-400">API Format</span>
                </div>
                <div id="listContainer" class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto">
                    <p class="px-4 py-8 text-center text-sm text-gray-400">Verileri yuklemek icin "Getir" butonuna tiklayin.</p>
                </div>
                <div id="paginationControls" class="px-4 py-3 border-t border-gray-100 flex items-center justify-between text-sm">
                    <span id="pageInfo" class="text-gray-500"></span>
                    <div class="flex items-center gap-2">
                        <button onclick="loadData(currentPage - 1)" id="prevBtn" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Onceki</button>
                        <span id="pageNumbers" class="flex gap-1"></span>
                        <button onclick="loadData(currentPage + 1)" id="nextBtn" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Sonraki</button>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">JSON Onizleme</h3>
                    <button onclick="copySelected()" class="text-xs text-emerald-600 hover:text-emerald-800 font-medium">Kopyala</button>
                </div>
                <pre id="jsonPreview" class="p-4 text-xs text-gray-700 bg-gray-50 rounded-b-xl overflow-auto max-h-[600px] font-mono whitespace-pre-wrap">Listeden bir kayit secin...</pre>
            </div>
        </div>

        {{-- Alan Aciklamalari --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-4 py-3 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700">Alan Sozlesmesi</h3>
            </div>
            <div class="p-4 overflow-x-auto">
                <table class="min-w-full text-xs text-left">
                    <thead>
                        <tr class="text-gray-500 uppercase tracking-wider">
                            <th class="pb-2 pr-4 font-semibold">API Alani</th>
                            <th class="pb-2 pr-4 font-semibold">DB Karsiligi</th>
                            <th class="pb-2 font-semibold">Aciklama</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        <tr><td class="py-1.5 pr-4 font-mono text-slate-700">id</td><td class="py-1.5 pr-4 font-mono text-gray-400">id</td><td class="py-1.5">Urun ID</td></tr>
                        <tr class="bg-slate-50"><td class="py-1.5 pr-4 font-mono text-slate-700">name</td><td class="py-1.5 pr-4 font-mono text-gray-400">name</td><td class="py-1.5">Urun adi</td></tr>
                        <tr><td class="py-1.5 pr-4 font-mono text-slate-700">stock_code</td><td class="py-1.5 pr-4 font-mono text-gray-400">stock_code</td><td class="py-1.5">Stok kodu</td></tr>
                        <tr class="bg-slate-50"><td class="py-1.5 pr-4 font-mono text-slate-700">description</td><td class="py-1.5 pr-4 font-mono text-gray-400">description</td><td class="py-1.5">Aciklama</td></tr>
                        <tr class="bg-blue-100"><td class="py-1.5 pr-4 font-mono text-slate-700 font-bold" colspan="3">AYLIK TAAHUTLU</td></tr>
                        <tr class="bg-emerald-50"><td class="py-1.5 pr-4 font-mono text-slate-700 font-bold">satis_usd_monthly_commitment</td><td class="py-1.5 pr-4 font-mono text-gray-400 font-bold">satis_usd_monthly_commitment</td><td class="py-1.5 font-bold text-emerald-700">Satis fiyati (USD)</td></tr>
                        <tr class="bg-gray-100"><td class="py-1.5 pr-4 font-mono text-slate-700 font-bold" colspan="3">AYLIK TAAHUTSUZ</td></tr>
                        <tr class="bg-emerald-50"><td class="py-1.5 pr-4 font-mono text-slate-700 font-bold">satis_usd_monthly_no_commitment</td><td class="py-1.5 pr-4 font-mono text-gray-400 font-bold">satis_usd_monthly_no_commitment</td><td class="py-1.5 font-bold text-emerald-700">Satis fiyati (USD)</td></tr>
                        <tr class="bg-emerald-100"><td class="py-1.5 pr-4 font-mono text-slate-700 font-bold" colspan="3">YILLIK TAAHUTLU</td></tr>
                        <tr class="bg-emerald-50"><td class="py-1.5 pr-4 font-mono text-slate-700 font-bold">satis_usd_yearly_commitment</td><td class="py-1.5 pr-4 font-mono text-gray-400 font-bold">satis_usd_yearly_commitment</td><td class="py-1.5 font-bold text-emerald-700">Satis fiyati (USD)</td></tr>
                        <tr class="bg-blue-50"><td class="py-1.5 pr-4 font-mono text-slate-700">service_provider.id</td><td class="py-1.5 pr-4 font-mono text-gray-400">service_provider_id</td><td class="py-1.5">Tedarikci ID</td></tr>
                        <tr class="bg-blue-50"><td class="py-1.5 pr-4 font-mono text-slate-700">service_provider.name</td><td class="py-1.5 pr-4 font-mono text-gray-400">service_providers.name</td><td class="py-1.5">Tedarikci adi</td></tr>
                        <tr class="bg-blue-50"><td class="py-1.5 pr-4 font-mono text-slate-700">service_provider.code</td><td class="py-1.5 pr-4 font-mono text-gray-400">service_providers.code</td><td class="py-1.5">Tedarikci kodu</td></tr>
                        <tr><td class="py-1.5 pr-4 font-mono text-slate-700">created_at</td><td class="py-1.5 pr-4 font-mono text-gray-400">created_at</td><td class="py-1.5">Olusturulma tarihi</td></tr>
                        <tr class="bg-slate-50"><td class="py-1.5 pr-4 font-mono text-slate-700">updated_at</td><td class="py-1.5 pr-4 font-mono text-gray-400">updated_at</td><td class="py-1.5">Son guncelleme tarihi</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tum JSON --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Tum Veri (JSON Array)</h3>
                <button onclick="copyAll()" class="text-xs text-emerald-600 hover:text-emerald-800 font-medium">Kopyala</button>
            </div>
            <pre id="allJsonPreview" class="p-4 text-xs text-gray-700 bg-gray-50 rounded-b-xl overflow-auto max-h-[400px] font-mono whitespace-pre-wrap">Henuz veri yuklenmedi...</pre>
        </div>
    </div>

    <script>
        let allData = [];
        let selectedIndex = null;
        let currentPage = 1;
        let totalPages = 1;
        let totalRecords = 0;

        async function loadData(page = 1) {
            const search = document.getElementById('searchInput').value;
            const url = new URL('{{ route("admin.integration.product-preview.data") }}', window.location.origin);
            if (search) url.searchParams.set('search', search);
            url.searchParams.set('page', page);

            const res = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            const json = await res.json();
            allData = json.data || [];
            currentPage = json.meta?.current_page ?? 1;
            totalPages = json.meta?.last_page ?? 1;
            totalRecords = json.meta?.total ?? allData.length;

            document.getElementById('totalCount').textContent = totalRecords;
            renderList();
            renderPagination();
            document.getElementById('allJsonPreview').textContent = JSON.stringify(allData, null, 2);
        }

        function renderList() {
            const container = document.getElementById('listContainer');
            if (!allData.length) {
                container.innerHTML = '<p class="px-4 py-8 text-center text-sm text-gray-400">Kayit bulunamadi.</p>';
                return;
            }

            container.innerHTML = allData.map((item, i) => {
                const mcSatis = item.satis_usd_monthly_commitment != null ? Number(item.satis_usd_monthly_commitment).toFixed(2) + ' USD' : '-';
                const mncSatis = item.satis_usd_monthly_no_commitment != null ? Number(item.satis_usd_monthly_no_commitment).toFixed(2) + ' USD' : '-';
                const ycSatis = item.satis_usd_yearly_commitment != null ? Number(item.satis_usd_yearly_commitment).toFixed(2) + ' USD' : '-';

                return `<div onclick="selectItem(${i})" class="px-4 py-3 cursor-pointer hover:bg-slate-50 transition-colors ${selectedIndex === i ? 'bg-slate-100' : ''}">
                    <div class="flex items-center justify-between">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-900 truncate">${item.name}</p>
                            <p class="text-xs text-gray-500 mt-0.5">Kod: ${item.stock_code ?? '-'} &bull; ${item.service_provider?.name ?? '-'}</p>
                            <div class="mt-1 space-y-0.5 text-xs">
                                <div class="flex items-center gap-1">
                                    <span class="px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 font-medium">Aylik T.</span>
                                    <span class="font-semibold text-emerald-600">${mcSatis}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 font-medium">Aylik T.siz</span>
                                    <span class="font-semibold text-emerald-600">${mncSatis}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 font-medium">Yillik T.</span>
                                    <span class="font-semibold text-emerald-600">${ycSatis}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
            }).join('');
        }

        function renderPagination() {
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const pageInfo = document.getElementById('pageInfo');
            const pageNumbers = document.getElementById('pageNumbers');

            prevBtn.disabled = currentPage <= 1;
            nextBtn.disabled = currentPage >= totalPages;
            pageInfo.textContent = `${currentPage} / ${totalPages} (Toplam ${totalRecords} kayit)`;

            let html = '';
            const maxVisible = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
            let endPage = Math.min(totalPages, startPage + maxVisible - 1);

            if (endPage - startPage < maxVisible - 1) {
                startPage = Math.max(1, endPage - maxVisible + 1);
            }

            if (startPage > 1) {
                html += `<button onclick="loadData(1)" class="px-2 py-1 border rounded hover:bg-gray-50">1</button>`;
                if (startPage > 2) html += `<span class="px-1">...</span>`;
            }

            for (let i = startPage; i <= endPage; i++) {
                html += `<button onclick="loadData(${i})" class="px-2 py-1 border rounded ${i === currentPage ? 'bg-slate-700 text-white' : 'hover:bg-gray-50'}">${i}</button>`;
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) html += `<span class="px-1">...</span>`;
                html += `<button onclick="loadData(${totalPages})" class="px-2 py-1 border rounded hover:bg-gray-50">${totalPages}</button>`;
            }

            pageNumbers.innerHTML = html;
        }

        function selectItem(i) {
            selectedIndex = i;
            document.getElementById('jsonPreview').textContent = JSON.stringify(allData[i], null, 2);
            renderList();
        }

        function copySelected() {
            if (selectedIndex === null) return;
            navigator.clipboard.writeText(JSON.stringify(allData[selectedIndex], null, 2));
            showToast('Kopyalandi!');
        }

        function copyAll() {
            if (!allData.length) return;
            navigator.clipboard.writeText(JSON.stringify(allData, null, 2));
            showToast('Tumu kopyalandi!');
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
