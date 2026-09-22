<x-app-layout>
    <x-flash-messages />

    <x-page-toolbar title="Bildirim Yönetimi">
        <x-slot name="left">
            <a href="{{ route('admin.mail-settings.edit') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 touch-manipulation" aria-label="Mail yönetimine dön">
                <span aria-hidden="true">&larr;</span>
            </a>
        </x-slot>
    </x-page-toolbar>

    @include('admin.notifications.partials.tabs')

    <div class="mb-4 max-w-3xl space-y-2">
        <p class="text-sm text-gray-600">E-posta metinleri burada düzenlenir. Kuralın hangi olayda çalışacağı Kurallar sekmesindedir.</p>
        <div class="text-xs text-gray-600 bg-white border border-gray-200 rounded-lg p-3">
            <p class="font-medium text-gray-700 mb-2">Mail içeriğinde kullanabileceğiniz yer tutucular</p>
            <ul class="space-y-1">
                <li><code class="bg-gray-100 px-1 rounded">{musteri}</code> — müşteri adı</li>
                <li><code class="bg-gray-100 px-1 rounded">{fatura_no}</code> — fatura numarası</li>
                <li><code class="bg-gray-100 px-1 rounded">{fatura_tarihi}</code> — fatura tarihi</li>
                <li><code class="bg-gray-100 px-1 rounded">{vade_tarihi}</code> — vade tarihi</li>
                <li><code class="bg-gray-100 px-1 rounded">{tutar}</code> — kesilen faturanın <strong>KDV dahil</strong> toplamı</li>
                <li><code class="bg-gray-100 px-1 rounded">{ftn}</code> — fatura takip no</li>
            </ul>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden max-w-4xl">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ad</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kanal</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Konu</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($templates as $template)
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $template->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $template->channel }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ \Illuminate\Support\Str::limit($template->subject, 60) }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            <a href="{{ route('admin.notifications.templates.edit', $template) }}" class="text-slate-600 hover:text-slate-900 font-medium">Düzenle</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">Şablon yok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
