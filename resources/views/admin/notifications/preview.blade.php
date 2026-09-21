<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }} — Mail önizlemesi</title>
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased bg-slate-100 min-h-screen">
    <div class="max-w-2xl mx-auto px-4 py-8">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Mail önizlemesi</p>
                <h1 class="text-sm font-semibold text-slate-800">{{ $definition_name }}</h1>
                @if ($invoice_number !== '')
                    <p class="text-xs text-slate-500 mt-0.5">Fatura {{ $invoice_number }}</p>
                @endif
            </div>
            <a href="{{ route('admin.notifications.edit') }}" class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                Bildiri yönetimine dön
            </a>
        </div>

        <article class="bg-white rounded-xl shadow-sm overflow-hidden border border-slate-200">
            <header class="border-b border-slate-100 px-5 py-4 space-y-2 text-sm">
                <div class="flex gap-3">
                    <span class="w-16 shrink-0 text-slate-400">Kimden</span>
                    <span class="text-slate-800">
                        @if ($from_name !== '')
                            {{ $from_name }}
                            @if ($from_address !== '')
                                <span class="text-slate-500">&lt;{{ $from_address }}&gt;</span>
                            @endif
                        @elseif ($from_address !== '')
                            {{ $from_address }}
                        @else
                            <span class="text-slate-400">Gönderen ayarlanmamış</span>
                        @endif
                    </span>
                </div>
                <div class="flex gap-3">
                    <span class="w-16 shrink-0 text-slate-400">Kime</span>
                    <span class="text-slate-800">
                        @if ($to !== '')
                            {{ $to }}
                        @else
                            <span class="text-amber-700">Müşterinin e-posta adresi yok</span>
                        @endif
                    </span>
                </div>
                <div class="flex gap-3">
                    <span class="w-16 shrink-0 text-slate-400">Konu</span>
                    <span class="font-medium text-slate-900">{{ $subject }}</span>
                </div>
            </header>
            <div class="px-5 py-6 text-sm text-slate-800 whitespace-pre-wrap break-words leading-relaxed">{{ $body }}</div>
        </article>

        <p class="mt-4 text-xs text-slate-500">
            Bu ekran gönderilen düz metin e-postanın görünümüdür. Gerçek gönderim yapılmaz.
        </p>
    </div>
</body>
</html>
