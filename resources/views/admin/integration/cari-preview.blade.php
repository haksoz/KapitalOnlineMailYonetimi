<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Cari API Önizleme</h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Açıklama --}}
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
            <strong>Entegrasyon Veri Standardı:</strong> Bu sayfa, sistemdeki cari verilerin dış entegrasyona gönderilecek
            standart JSON formatını göstermektedir. Gerçek veritabanı kolon isimleri yerine entegrasyon alan adları kullanılmaktadır.
        </div>

        {{-- Filtre --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <input type="text" id="searchInput" placeholder="Ad veya vergi no ara..." class="flex-1 rounded-lg border-gray-300 text-sm focus:ring-slate-500 focus:border-slate-500">
                <button onclick="loadData()" class="inline-flex items-center px-4 py-2 bg-slate-700 text-white text-sm font-medium rounded-lg hover:bg-slate-800">
                    Getir
                </button>
                <button onclick="copyAll()" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">
                    Tümünü Kopyala
                </button>
            </div>
        </div>

        {{-- Sonuç Alanı --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" id="resultsContainer">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Liste (<span id="totalCount">0</span> kayıt)</h3>
                    <span class="text-xs text-gray-400">API Format</span>
                </div>
                <div id="listContainer" class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto">
                    <p class="px-4 py-8 text-center text-sm text-gray-400">Verileri yüklemek için "Getir" butonuna tıklayın.</p>
                </div>
                <div id="paginationControls" class="px-4 py-3 border-t border-gray-100 flex items-center justify-between text-sm">
                    <span id="pageInfo" class="text-gray-500"></span>
                    <div class="flex items-center gap-2">
                        <button onclick="loadData(currentPage - 1)" id="prevBtn" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Önceki</button>
                        <span id="pageNumbers" class="flex gap-1"></span>
                        <button onclick="loadData(currentPage + 1)" id="nextBtn" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Sonraki</button>
                    </div>
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
        let currentPage = 1;
        let totalPages = 1;
        let totalRecords = 0;

        async function loadData(page = 1) {
            const search = document.getElementById('searchInput').value;
            const url = new URL('{{ route("admin.integration.cari-preview.data") }}', window.location.origin);
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
                container.innerHTML = '<p class="px-4 py-8 text-center text-sm text-gray-400">Kayıt bulunamadı.</p>';
                return;
            }

            container.innerHTML = allData.map((item, i) => `
                <div onclick="selectItem(${i})" class="px-4 py-3 cursor-pointer hover:bg-slate-50 transition-colors ${selectedIndex === i ? 'bg-slate-100' : ''}">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900">${item.name}</p>
                            <p class="text-xs text-gray-500 mt-0.5">VN: ${item.tax_number ?? '-'} &bull; ${item.country_code} &bull; <span class="capitalize">${item.type}</span></p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">${item.status}</span>
                    </div>
                </div>
            `).join('');
        }

        function renderPagination() {
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const pageInfo = document.getElementById('pageInfo');
            const pageNumbers = document.getElementById('pageNumbers');

            prevBtn.disabled = currentPage <= 1;
            nextBtn.disabled = currentPage >= totalPages;
            pageInfo.textContent = `${currentPage} / ${totalPages} (Toplam ${totalRecords} kayıt)`;

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
            el.className = 'fixed bottom-4 right-4 bg-slate-800 text-white text-sm px-4 py-2 rounded-lg shadow-lg z-50 transition-opacity';
            document.body.appendChild(el);
            setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 2000);
        }

        document.getElementById('searchInput').addEventListener('keydown', e => { if (e.key === 'Enter') loadData(); });

        loadData();
    </script>
</x-app-layout>
