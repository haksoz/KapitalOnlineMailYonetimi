<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class ExchangeRateService
{
    /**
     * TCMB'den güncel kurları çeker ve exchange_rates tablosuna yazar.
     * İşlem günü için efektif satış kuru olarak forex_selling kullanılır.
     *
     * @return array{success: bool, message: string}
     */
    public function fetchFromTcmb(): array
    {
        try {
            $response = Http::timeout(10)->get('https://www.tcmb.gov.tr/kurlar/today.xml');

            if (! $response->ok()) {
                return ['success' => false, 'message' => 'Merkez Bankası servisinden cevap alınamadı.'];
            }

            $xml = @simplexml_load_string($response->body());

            if (! $xml) {
                return ['success' => false, 'message' => 'Merkez Bankası kur verisi okunamadı.'];
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

            return ['success' => true, 'message' => 'Güncel kurlar Merkez Bankası\'ndan çekildi.', 'effective_date' => $effectiveDate];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Kurlar çekilirken bir hata oluştu: ' . $e->getMessage()];
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
