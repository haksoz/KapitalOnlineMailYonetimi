<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
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

    public function fetchLatest(ExchangeRateService $exchangeRateService): RedirectResponse
    {
        $result = $exchangeRateService->fetchFromTcmb();

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }
}
