<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">API Ayarları / Entegrasyon Yönetimi</h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 space-y-8">

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-sm text-green-800 flex items-start gap-3">
                <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('new_plain_token'))
            <div class="bg-amber-50 border-2 border-amber-400 rounded-lg p-4 space-y-2">
                <p class="text-sm font-semibold text-amber-800">API Anahtarı oluşturuldu — Yalnızca bir kez gösterilir, kaydedin!</p>
                <div class="flex items-center gap-2">
                    <code id="newToken" class="flex-1 bg-white border border-amber-300 rounded px-3 py-2 text-sm font-mono text-gray-900 break-all">{{ session('new_plain_token') }}</code>
                    <button onclick="copyToken()" class="px-3 py-2 bg-amber-500 text-white text-xs font-medium rounded hover:bg-amber-600">Kopyala</button>
                </div>
            </div>
        @endif

        {{-- SEKME NAVİGASYONU --}}
        <div x-data="{ tab: 'integrations' }" class="space-y-6">
            <div class="border-b border-gray-200">
                <nav class="flex gap-1 overflow-x-auto">
                    @foreach([['integrations','Entegrasyonlar'],['keys','API Anahtarları'],['webhooks','Webhook Ayarları'],['logs','Event Log']] as [$id,$label])
                    <button @click="tab = '{{ $id }}'"
                        :class="tab === '{{ $id }}' ? 'border-slate-700 text-slate-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium transition-colors">
                        {{ $label }}
                    </button>
                    @endforeach
                </nav>
            </div>

            {{-- ==================== ENTEGRASYONLAR ==================== --}}
            <div x-show="tab === 'integrations'" class="space-y-6">

                {{-- Entegrasyon Listesi --}}
                @forelse($integrations as $integration)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $integration->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                {{ $integration->is_active ? 'Aktif' : 'Pasif' }}
                            </span>
                            <h3 class="text-base font-semibold text-gray-900">{{ $integration->name }}</h3>
                            <span class="text-xs text-gray-400 font-mono">{{ $integration->slug }}</span>
                            <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-mono">{{ $integration->api_version }}</span>
                        </div>
                        <div class="text-xs text-gray-400 space-y-0.5 text-right">
                            @if($integration->last_accessed_at)
                                <div>Son erişim: {{ $integration->last_accessed_at->diffForHumans() }}</div>
                            @endif
                            @if($integration->last_synced_at)
                                <div>Son sync: {{ $integration->last_synced_at->diffForHumans() }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="px-5 py-4 grid sm:grid-cols-2 gap-4">
                        <div>
                            @if($integration->base_url)
                                <p class="text-xs text-gray-500 mb-1">Base URL</p>
                                <div class="flex items-center gap-2">
                                    <code class="text-xs bg-gray-50 border border-gray-200 rounded px-2 py-1 flex-1 break-all">{{ $integration->base_url }}</code>
                                    <button onclick="navigator.clipboard.writeText('{{ $integration->base_url }}')" class="text-xs text-slate-500 hover:text-slate-700">Kopyala</button>
                                </div>
                            @endif
                            @if($integration->description)
                                <p class="text-xs text-gray-500 mt-2">{{ $integration->description }}</p>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('admin.api-settings.integrations.update', $integration) }}" class="space-y-2">
                            @csrf @method('PATCH')
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-xs text-gray-500">Ad</label>
                                    <input type="text" name="name" value="{{ $integration->name }}" class="mt-0.5 w-full rounded border-gray-300 text-sm">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Versiyon</label>
                                    <input type="text" name="api_version" value="{{ $integration->api_version }}" class="mt-0.5 w-full rounded border-gray-300 text-sm">
                                </div>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Base URL</label>
                                <input type="url" name="base_url" value="{{ $integration->base_url }}" placeholder="https://..." class="mt-0.5 w-full rounded border-gray-300 text-sm">
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" {{ $integration->is_active ? 'checked' : '' }} class="rounded border-gray-300">
                                    Aktif
                                </label>
                                <button type="submit" class="ml-auto px-3 py-1.5 bg-slate-700 text-white text-xs rounded hover:bg-slate-800">Kaydet</button>
                            </div>
                        </form>
                    </div>
                </div>
                @empty
                <div class="bg-gray-50 border border-dashed border-gray-300 rounded-xl p-8 text-center text-sm text-gray-500">
                    Henüz entegrasyon tanımlanmamış.
                </div>
                @endforelse

                {{-- Yeni Entegrasyon --}}
                <div x-data="{ open: false }" class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <button @click="open = !open" class="w-full px-5 py-4 flex items-center gap-2 text-sm font-medium text-slate-700 hover:bg-gray-50 rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Yeni Entegrasyon Ekle
                    </button>
                    <div x-show="open" x-cloak class="px-5 pb-5 border-t border-gray-100">
                        <form method="POST" action="{{ route('admin.api-settings.integrations.store') }}" class="grid sm:grid-cols-2 gap-4 pt-4">
                            @csrf
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Ad <span class="text-red-500">*</span></label>
                                <input type="text" name="name" required placeholder="Master System" class="w-full rounded border-gray-300 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Slug <span class="text-red-500">*</span></label>
                                <input type="text" name="slug" required placeholder="master-system" pattern="[a-z0-9\-]+" class="w-full rounded border-gray-300 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Base URL</label>
                                <input type="url" name="base_url" placeholder="https://..." class="w-full rounded border-gray-300 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">API Versiyonu</label>
                                <input type="text" name="api_version" value="v1" class="w-full rounded border-gray-300 text-sm">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs text-gray-600 mb-1">Açıklama</label>
                                <textarea name="description" rows="2" class="w-full rounded border-gray-300 text-sm"></textarea>
                            </div>
                            <div class="sm:col-span-2 flex justify-end">
                                <button type="submit" class="px-4 py-2 bg-slate-700 text-white text-sm font-medium rounded hover:bg-slate-800">Oluştur</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ==================== API ANAHTARLARI ==================== --}}
            <div x-show="tab === 'keys'" class="space-y-6">
                @foreach($integrations as $integration)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-800">{{ $integration->name }} — Anahtarlar</h3>
                        <span class="text-xs text-gray-400">{{ $integration->apiKeys->count() }} anahtar</span>
                    </div>

                    {{-- Anahtar Listesi --}}
                    @if($integration->apiKeys->isNotEmpty())
                    <div class="divide-y divide-gray-100">
                        @foreach($integration->apiKeys as $key)
                        <div class="px-5 py-3 flex flex-wrap items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-medium text-gray-900">{{ $key->name }}</span>
                                    <span class="text-xs bg-{{ $key->permission_level === 'admin' ? 'red' : ($key->permission_level === 'write' ? 'yellow' : 'blue') }}-100 text-{{ $key->permission_level === 'admin' ? 'red' : ($key->permission_level === 'write' ? 'yellow' : 'blue') }}-800 px-2 py-0.5 rounded font-medium">{{ strtoupper($key->permission_level) }}</span>
                                    @if($key->revoked_at)
                                        <span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded">İptal</span>
                                    @elseif($key->isExpired())
                                        <span class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded">Süresi Dolmuş</span>
                                    @elseif($key->is_active)
                                        <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded">Aktif</span>
                                    @else
                                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">Pasif</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-400 mt-1 flex flex-wrap gap-3">
                                    <span>Prefix: <code class="font-mono">{{ $key->token_prefix }}••••</code></span>
                                    <span>Oluşturuldu: {{ $key->created_at->format('d.m.Y') }}</span>
                                    @if($key->last_used_at)
                                        <span>Son kullanım: {{ $key->last_used_at->diffForHumans() }}</span>
                                    @endif
                                    @if($key->expires_at)
                                        <span>Geçerlilik: {{ $key->expires_at->format('d.m.Y') }}</span>
                                    @endif
                                    @if($key->allowed_ips)
                                        <span>IP: <code class="font-mono">{{ $key->allowed_ips }}</code></span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if(! $key->revoked_at)
                                <form method="POST" action="{{ route('admin.api-settings.keys.toggle', $key) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-xs px-2 py-1 border border-gray-300 rounded hover:bg-gray-50 text-gray-600">
                                        {{ $key->is_active ? 'Pasif Yap' : 'Aktifleştir' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.api-settings.keys.revoke', $key) }}"
                                    onsubmit="return confirm('Bu anahtarı iptal etmek istediğinizden emin misiniz?')">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-xs px-2 py-1 bg-red-50 border border-red-200 text-red-700 rounded hover:bg-red-100">İptal Et</button>
                                </form>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="px-5 py-4 text-sm text-gray-400">Bu entegrasyon için henüz anahtar oluşturulmamış.</p>
                    @endif

                    {{-- Yeni Anahtar Formu --}}
                    <div x-data="{ open: false }" class="border-t border-gray-100">
                        <button @click="open = !open" class="w-full px-5 py-3 flex items-center gap-2 text-xs font-medium text-slate-600 hover:bg-gray-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Yeni Anahtar Oluştur
                        </button>
                        <div x-show="open" x-cloak class="px-5 pb-5">
                            <form method="POST" action="{{ route('admin.api-settings.keys.generate', $integration) }}" class="grid sm:grid-cols-3 gap-3">
                                @csrf
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Anahtar Adı <span class="text-red-500">*</span></label>
                                    <input type="text" name="name" required placeholder="Master System Read Key" class="w-full rounded border-gray-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Yetki Seviyesi</label>
                                    <select name="permission_level" class="w-full rounded border-gray-300 text-sm">
                                        <option value="read">READ — Yalnızca okuma</option>
                                        <option value="write">WRITE — Okuma + Yazma</option>
                                        <option value="admin">ADMIN — Tam erişim</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Son Kullanma Tarihi</label>
                                    <input type="date" name="expires_at" min="{{ now()->addDay()->toDateString() }}" class="w-full rounded border-gray-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Rate Limit (istek/dk)</label>
                                    <input type="number" name="rate_limit_per_minute" value="60" min="1" max="10000" class="w-full rounded border-gray-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">İzinli IP'ler (virgülle)</label>
                                    <input type="text" name="allowed_ips" placeholder="192.168.1.1, 10.0.0.0/8" class="w-full rounded border-gray-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Açıklama</label>
                                    <input type="text" name="description" placeholder="Opsiyonel" class="w-full rounded border-gray-300 text-sm">
                                </div>
                                <div class="sm:col-span-3 flex justify-end">
                                    <button type="submit" class="px-4 py-2 bg-slate-700 text-white text-sm font-medium rounded hover:bg-slate-800">Oluştur</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach

                @if($integrations->isEmpty())
                <div class="bg-gray-50 border border-dashed border-gray-300 rounded-xl p-8 text-center text-sm text-gray-500">
                    Önce "Entegrasyonlar" sekmesinden bir entegrasyon oluşturun.
                </div>
                @endif
            </div>

            {{-- ==================== WEBHOOK AYARLARI ==================== --}}
            <div x-show="tab === 'webhooks'" class="space-y-6">
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
                    <strong>Hazırlık Aşaması:</strong> Webhook altyapısı kurulu. Gerçek gönderim mantığı ileriki sürümde eklenecek.
                </div>

                @foreach($integrations as $integration)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-800">{{ $integration->name }} — Webhook'lar</h3>
                    </div>

                    @foreach($integration->webhookSettings as $webhook)
                    <div class="px-5 py-3 flex flex-wrap items-center gap-3 border-b border-gray-50">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-900">{{ $webhook->name }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $webhook->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $webhook->is_active ? 'Aktif' : 'Pasif' }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-400 mt-1 flex gap-3 flex-wrap">
                                <code class="font-mono">{{ $webhook->callback_url }}</code>
                                @if($webhook->last_triggered_at)
                                    <span>Son tetikleme: {{ $webhook->last_triggered_at->diffForHumans() }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="testWebhook({{ $webhook->id }}, this)"
                                class="text-xs px-2 py-1 bg-blue-50 border border-blue-200 text-blue-700 rounded hover:bg-blue-100">
                                Test Gönder
                            </button>
                            <form method="POST" action="{{ route('admin.api-settings.webhooks.toggle', $webhook) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs px-2 py-1 border border-gray-300 rounded hover:bg-gray-50 text-gray-600">
                                    {{ $webhook->is_active ? 'Pasif Yap' : 'Aktifleştir' }}
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach

                    <div x-data="{ open: false }" class="border-t border-gray-100">
                        <button @click="open = !open" class="w-full px-5 py-3 flex items-center gap-2 text-xs font-medium text-slate-600 hover:bg-gray-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Webhook Ekle
                        </button>
                        <div x-show="open" x-cloak class="px-5 pb-5">
                            <form method="POST" action="{{ route('admin.api-settings.webhooks.store', $integration) }}" class="grid sm:grid-cols-2 gap-3">
                                @csrf
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Ad <span class="text-red-500">*</span></label>
                                    <input type="text" name="name" required placeholder="Order Created" class="w-full rounded border-gray-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Callback URL <span class="text-red-500">*</span></label>
                                    <input type="url" name="callback_url" required placeholder="https://master.example.com/webhook" class="w-full rounded border-gray-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Retry Sayısı</label>
                                    <input type="number" name="retry_count" value="3" min="0" max="10" class="w-full rounded border-gray-300 text-sm">
                                </div>
                                <div class="flex items-end">
                                    <label class="flex items-center gap-2 text-xs text-gray-600 cursor-pointer">
                                        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300">
                                        Hemen aktifleştir
                                    </label>
                                </div>
                                <div class="sm:col-span-2 flex justify-end">
                                    <button type="submit" class="px-4 py-2 bg-slate-700 text-white text-sm font-medium rounded hover:bg-slate-800">Ekle</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- ==================== EVENT LOG ==================== --}}
            <div x-show="tab === 'logs'" class="space-y-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-3">
                        <h3 class="text-sm font-semibold text-gray-800">Son API İstekleri</h3>
                        <div class="flex gap-2">
                            <select id="logStatusFilter" class="rounded border-gray-300 text-xs" onchange="loadLogs()">
                                <option value="">Tümü</option>
                                <option value="success">Başarılı</option>
                                <option value="error">Hatalı</option>
                            </select>
                            <button onclick="loadLogs()" class="px-3 py-1.5 bg-slate-700 text-white text-xs rounded hover:bg-slate-800">Yenile</button>
                        </div>
                    </div>
                    <div id="logContainer">
                        @if($recentLogs->isEmpty())
                        <p class="px-5 py-8 text-center text-sm text-gray-400">Henüz log kaydı yok.</p>
                        @else
                        <div class="divide-y divide-gray-100 text-xs">
                            @foreach($recentLogs as $log)
                            <div class="px-5 py-3 flex flex-wrap items-start gap-3">
                                <span class="font-mono font-bold text-{{ $log->isSuccess() ? 'green' : 'red' }}-600 shrink-0 w-10">{{ $log->status_code ?? '—' }}</span>
                                <span class="font-mono bg-gray-100 text-gray-700 px-1.5 py-0.5 rounded shrink-0">{{ $log->method }}</span>
                                <span class="font-mono text-gray-800 flex-1 min-w-0 truncate">{{ $log->endpoint }}</span>
                                <span class="text-gray-400 shrink-0">{{ $log->duration_ms ? $log->duration_ms.'ms' : '' }}</span>
                                <span class="text-gray-400 shrink-0">{{ $log->requested_at->format('d.m.Y H:i:s') }}</span>
                                @if($log->error_message)
                                    <span class="w-full text-red-500 mt-1">{{ Str::limit($log->error_message, 120) }}</span>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function copyToken() {
            const text = document.getElementById('newToken').textContent.trim();
            navigator.clipboard.writeText(text);
            showToast('Token kopyalandı!');
        }

        async function testWebhook(id, btn) {
            btn.disabled = true;
            btn.textContent = 'Test ediliyor...';
            try {
                const res = await fetch(`/admin/api-settings/webhooks/${id}/test`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });
                const data = await res.json();
                if (data.success) {
                    showToast(`Başarılı — HTTP ${data.status_code} (${data.duration_ms}ms)`);
                } else {
                    showToast(`Başarısız — ${data.message ?? 'HTTP ' + data.status_code}`, true);
                }
            } catch(e) {
                showToast('İstek gönderilemedi: ' + e.message, true);
            } finally {
                btn.disabled = false;
                btn.textContent = 'Test Gönder';
            }
        }

        function showToast(msg, isError = false) {
            const el = document.createElement('div');
            el.textContent = msg;
            el.className = `fixed bottom-4 right-4 ${isError ? 'bg-red-700' : 'bg-slate-800'} text-white text-sm px-4 py-2 rounded-lg shadow-lg z-50`;
            document.body.appendChild(el);
            setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 3000);
        }
    </script>
</x-app-layout>
