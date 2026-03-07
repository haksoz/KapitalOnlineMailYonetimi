<?php

namespace App\Console\Commands;

use App\Services\ExchangeRateService;
use Illuminate\Console\Command;

class FetchExchangeRatesCommand extends Command
{
    protected $signature = 'exchange-rates:fetch';

    protected $description = 'TCMB\'den güncel kurları çeker (günlük scheduler ile kullanılır).';

    public function handle(ExchangeRateService $exchangeRateService): int
    {
        $result = $exchangeRateService->fetchFromTcmb();

        if ($result['success']) {
            $this->info($result['message']);
            if (! empty($result['effective_date'])) {
                $this->info('Etkin tarih: ' . $result['effective_date']);
            }
            return self::SUCCESS;
        }

        $this->error($result['message']);
        return self::FAILURE;
    }
}
