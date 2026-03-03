<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class ExchangeRateController extends Controller
{
    public function index(Request $request): View
    {
        $usd = ExchangeRate::where('currency_code', 'USD')
            ->orderByDesc('effective_date')
            ->first();

        $eur = ExchangeRate::where('currency_code', 'EUR')
            ->orderByDesc('effective_date')
            ->first();

        $lastUpdatedDate = collect([
            $usd?->effective_date,
            $eur?->effective_date,
        ])->filter()->max();

        return view('exchange-rates.index', [
            'usd' => $usd,
            'eur' => $eur,
            'lastUpdatedDate' => $lastUpdatedDate,
        ]);
    }

    public function edit(ExchangeRate $exchangeRate): View
    {
        return view('exchange-rates.edit', [
            'rate' => $exchangeRate,
        ]);
    }

    public function update(Request $request, ExchangeRate $exchangeRate): RedirectResponse
    {
        $validated = $request->validate([
            'forex_buying' => ['nullable', 'numeric'],
            'forex_selling' => ['nullable', 'numeric'],
            'banknote_buying' => ['nullable', 'numeric'],
            'banknote_selling' => ['nullable', 'numeric'],
        ]);

        $exchangeRate->update($validated);

        return redirect()
            ->route('exchange-rates.index')
            ->with('success', 'Kur bilgisi güncellendi.');
    }

    public function fetchLatest(): RedirectResponse
    {
        try {
            $response = Http::timeout(10)->get('https://www.tcmb.gov.tr/kurlar/today.xml');

            if (! $response->ok()) {
                return back()->with('error', 'Merkez Bankası servisinden cevap alınamadı.');
            }

            $xml = @simplexml_load_string($response->body());

            if (! $xml) {
                return back()->with('error', 'Merkez Bankası kur verisi okunamadı.');
            }

            $dateAttr = (string) ($xml['Date'] ?? '');
            $effectiveDate = $dateAttr ? Carbon::parse($dateAttr)->toDateString() : Carbon::today()->toDateString();

            $targetCodes = ['USD', 'EUR'];

            foreach ($xml->Currency as $currency) {
                $code = (string) $currency['CurrencyCode'];

                if (! $code || ! in_array($code, $targetCodes, true)) {
                    continue;
                }

                $name = (string) ($currency->Isim ?? $currency->CurrencyName ?? $code);

                $data = [
                    'name' => $name,
                    'forex_buying' => $this->parseDecimal($currency->ForexBuying ?? null),
                    'forex_selling' => $this->parseDecimal($currency->ForexSelling ?? null),
                    'banknote_buying' => $this->parseDecimal($currency->BanknoteBuying ?? null),
                    'banknote_selling' => $this->parseDecimal($currency->BanknoteSelling ?? null),
                    'effective_date' => $effectiveDate,
                ];

                ExchangeRate::updateOrCreate(
                    [
                        'currency_code' => $code,
                        'effective_date' => $effectiveDate,
                    ],
                    $data
                );
            }

            return back()->with('success', 'Güncel kurlar Merkez Bankası\'ndan çekildi.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Kurlar çekilirken bir hata oluştu: ' . $e->getMessage());
        }
    }

    private function parseDecimal($value): ?float
    {
        if ($value === null) {
            return null;
        }

        $str = trim((string) $value);

        if ($str === '') {
            return null;
        }

        $str = str_replace(',', '.', $str);

        return is_numeric($str) ? (float) $str : null;
    }
}
