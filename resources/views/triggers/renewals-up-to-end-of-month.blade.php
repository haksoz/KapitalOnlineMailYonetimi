@php
    use Carbon\Carbon;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h1 class="text-lg sm:text-xl font-semibold text-gray-800 truncate">Bu ay sonuna kadar bitiş güncelle</h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    Bitiş tarihi <strong>{{ $upTo->locale('tr')->translatedFormat('d F Y') }}</strong> (ay sonu) veya öncesinde olan otomatik yenilemeli abonelikler. İstediğiniz carileri seçip güncelleyin.
                </p>
            </div>
            <a href="{{ route('triggers.index') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                ← Tetikleyiciler
            </a>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @if ($subscriptions->isEmpty())
            <div class="px-4 py-8 text-center text-sm text-gray-500">
                Bu ay sonuna kadar bitiş tarihi geçmiş (otomatik yenilemeli) abonelik yok.
            </div>
        @else
            <form action="{{ route('triggers.run-renewals-up-to-end-of-month') }}" method="POST" id="renew-form">
                @csrf
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="checkbox" id="select-all" class="rounded border-gray-300 text-slate-600 focus:ring-slate-500" aria-label="Tümünü seç">
                                    </label>
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Cari</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Sözleşme no</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Mevcut bitiş tarihi</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Taahhüt</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach ($subscriptions as $sub)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <input type="checkbox" name="subscription_ids[]" value="{{ $sub->id }}" class="renew-checkbox rounded border-gray-300 text-slate-600 focus:ring-slate-500" aria-label="Seç">
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ $sub->customerCari?->short_name ?: $sub->customerCari?->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">{{ $sub->sozlesme_no ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $sub->bitis_tarihi ? $sub->bitis_tarihi->format('d.m.Y') : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        @switch($sub->taahhut_tipi ?? '')
                                            @case('monthly_commitment')
                                                Aylık taahhütlü
                                                @break
                                            @case('monthly_no_commitment')
                                                Aylık taahhütsüz
                                                @break
                                            @case('annual_commitment')
                                                Yıllık taahhütlü
                                                @break
                                            @default
                                                —
                                        @endswitch
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 flex flex-wrap items-center gap-3">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-slate-700 text-white rounded-lg font-semibold text-sm hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                        Seçilenleri bu ay sonuna kadar güncelle
                    </button>
                    <span class="text-sm text-gray-500">Sadece işaretlediğiniz aboneliklerin bitiş tarihi ay sonunu geçene kadar uzatılacak.</span>
                </div>
            </form>

            <script>
                (function () {
                    var form = document.getElementById('renew-form');
                    var selectAll = document.getElementById('select-all');
                    var checkboxes = document.querySelectorAll('.renew-checkbox');

                    if (selectAll && checkboxes.length) {
                        selectAll.addEventListener('change', function () {
                            checkboxes.forEach(function (cb) { cb.checked = selectAll.checked; });
                        });
                    }

                    if (form) {
                        form.addEventListener('submit', function (e) {
                            var any = Array.prototype.some.call(checkboxes, function (cb) { return cb.checked; });
                            if (!any) {
                                e.preventDefault();
                                alert('En az bir abonelik seçin.');
                            }
                        });
                    }
                })();
            </script>
        @endif
    </div>
</x-app-layout>
