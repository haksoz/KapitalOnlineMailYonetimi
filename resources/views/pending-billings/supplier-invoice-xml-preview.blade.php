<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <h1 class="text-lg sm:text-xl font-semibold text-gray-800 truncate">Alış faturası XML — Okunan bilgiler</h1>
        </div>
    </x-slot>

    <x-flash-messages />

    @if (!empty($unmatched))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl">
            <p class="text-sm font-medium text-red-800">Faturada olup siparişlerinizde bulunamayan kalemler</p>
            <ul class="mt-2 text-sm text-red-700 list-disc list-inside space-y-1">
                @foreach ($unmatched as $u)
                    <li>
                        Sözleşme no: <strong>{{ $u['sozlesme_no'] ?? '—' }}</strong>
                        @if (!empty($u['item_name']))
                            — {{ $u['item_name'] }}
                        @endif
                        @if (!empty($u['reason']))
                            ({{ $u['reason'] }})
                        @endif
                    </li>
                @endforeach
            </ul>
            <p class="mt-2 text-sm text-red-600">Abonelik tanımlı olmayabilir veya dönem eşleşmiyor. İşlem tamamlanmadı.</p>
        </div>
    @endif

    <div class="mb-4">
        <p class="text-sm text-gray-600">
            Aşağıda XML’den okunan fatura bilgileri ve kalemler listeleniyor. Her kalem için <strong>hizmet dönemi</strong> seçin (fatura geç kesildiyse fatura tarihinden farklı dönem seçebilirsiniz). Siparişte bulunmayan kalem varsa işlem yapılmaz. Doğruysa <strong>Eşle ve güncelle</strong> ile siparişleri güncelleyin.
        </p>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Fatura başlığı</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
                <div>
                    <dt class="text-gray-500">Tedarikçi</dt>
                    <dd class="font-medium text-gray-900">{{ $parsed['provider_cari_name'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Fatura no</dt>
                    <dd class="font-medium text-gray-900">{{ $parsed['invoice_no'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Fatura tarihi</dt>
                    <dd class="font-medium text-gray-900">{{ $parsed['issue_date'] ? \Carbon\Carbon::parse($parsed['issue_date'])->format('d.m.Y') : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Varsayılan hizmet dönemi</dt>
                    <dd class="font-medium text-gray-900">
                        @if ($defaultPeriodYear && $defaultPeriodMonth)
                            {{ \Carbon\Carbon::createFromDate($defaultPeriodYear, $defaultPeriodMonth, 1)->locale('tr')->translatedFormat('F Y') }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <h2 class="px-4 py-3 text-sm font-semibold text-gray-700 border-b border-gray-200">Kalemler — Her satır için hizmet dönemi seçin</h2>
            <form action="{{ route('pending-billings.supplier-invoice-xml-apply') }}" method="POST" id="supplier-invoice-xml-apply-form" onsubmit="return confirm('Siparişler bu faturaya göre güncellenecek. Devam?');">
                @csrf
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sözleşme no</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ürün</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Adet</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">KDV hariç toplam (₺)</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sistemdeki dönemler</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Son 3 sipariş</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hizmet dönemi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach(($parsed['lines'] ?? []) as $index => $line)
                                @php
                                    $periods = $linePeriods[$index] ?? [];
                                    $defaultSel = null;
                                    if ($defaultPeriodYear && $defaultPeriodMonth) {
                                        $defKey = $defaultPeriodYear . '-' . str_pad((string) $defaultPeriodMonth, 2, '0', STR_PAD_LEFT);
                                        foreach ($periods as $p) {
                                            $key = $p['year'] . '-' . str_pad((string) $p['month'], 2, '0', STR_PAD_LEFT);
                                            if ($key === $defKey) {
                                                $defaultSel = $key;
                                                break;
                                            }
                                        }
                                    }
                                    if ($defaultSel === null && count($periods) > 0) {
                                        $first = $periods[0];
                                        $defaultSel = $first['year'] . '-' . str_pad((string) $first['month'], 2, '0', STR_PAD_LEFT);
                                    }
                                    $oldPeriod = old('lines.'.$index.'.period');
                                    $selected = $oldPeriod !== null ? $oldPeriod : $defaultSel;
                                    $recentBillings = $lineRecentBillings[$index] ?? [];
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $line['sozlesme_no'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $line['item_name'] ?? '—' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">{{ isset($line['quantity']) ? number_format((float) $line['quantity'], 0, ',', '.') : '—' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-900">{{ isset($line['line_extension_amount_try']) ? number_format((float) $line['line_extension_amount_try'], 2, ',', '.') : '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        @if (count($periods) > 0)
                                            <ul class="space-y-0.5">
                                                @foreach ($periods as $p)
                                                    <li>{{ $p['label'] }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-amber-600">Sipariş yok</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        @if (count($recentBillings) > 0)
                                            <ul class="space-y-1">
                                                @foreach ($recentBillings as $rb)
                                                    <li class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                                        <span class="font-medium text-gray-900">{{ $rb['period_label'] }}</span>
                                                        <span class="text-gray-500">·</span>
                                                        <span class="@if($rb['status'] === 'pending') text-amber-600 @elseif($rb['status'] === 'invoiced') text-emerald-600 @else text-gray-500 @endif">{{ $rb['status_label'] }}</span>
                                                        <span class="text-gray-400 text-xs">Alış: {{ $rb['has_supplier_invoice'] ? 'Var' : 'Yok' }}</span>
                                                        <span class="text-gray-400 text-xs">Satış: {{ $rb['has_sales_invoice'] ? 'Var' : 'Yok' }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if (count($periods) > 0)
                                            <select name="lines[{{ $index }}][period]" class="rounded-lg border-gray-300 shadow-sm focus:ring-slate-500 focus:border-slate-500 text-sm block w-full max-w-xs">
                                                @foreach ($periods as $p)
                                                    @php $key = $p['year'] . '-' . str_pad((string) $p['month'], 2, '0', STR_PAD_LEFT); @endphp
                                                    <option value="{{ $key }}" {{ $selected === $key ? 'selected' : '' }}>{{ $p['label'] }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <span class="text-amber-600 text-sm">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if (empty($parsed['lines']))
                    <p class="px-4 py-6 text-center text-sm text-gray-500">Kalem bulunamadı.</p>
                @endif
                <div class="px-4 py-4 border-t border-gray-200 flex flex-wrap gap-3">
                    <button type="submit" form="supplier-invoice-xml-apply-form" class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded-lg font-semibold text-sm hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                        Eşle ve güncelle
                    </button>
                    <a href="{{ route('pending-billings.supplier-invoice-xml-cancel') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                        İptal
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
