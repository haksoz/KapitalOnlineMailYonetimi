<?php

namespace Tests\Unit;

use App\Models\Cari;
use App\Models\ExchangeRate;
use App\Models\PendingBilling;
use App\Models\Subscription;
use App\Services\PendingBillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendingBillingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_expected_sales_updates_when_subscription_prices_change_after_purchase_invoice(): void
    {
        $customerCari = Cari::create([
            'name' => 'Musteri A',
            'short_name' => 'Musteri A',
            'cari_type' => 'customer',
            'tax_number' => '1234567890',
        ]);

        $subscription = Subscription::create([
            'customer_cari_id' => $customerCari->id,
            'provider_cari_id' => $customerCari->id,
            'sozlesme_no' => 'SOZ-001',
            'baslangic_tarihi' => '2026-01-01',
            'bitis_tarihi' => '2027-01-01',
            'taahhut_tipi' => Subscription::TAAHHUT_MONTHLY_COMMITMENT,
            'faturalama_periyodu' => Subscription::FATURALAMA_MONTHLY,
            'durum' => Subscription::DURUM_ACTIVE,
            'auto_renew' => true,
            'quantity' => 1,
            'usd_birim_alis' => 10,
            'usd_birim_satis' => 12,
            'vat_rate' => 20,
        ]);

        $pendingBilling = PendingBilling::create([
            'subscription_id' => $subscription->id,
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-28',
            'status' => PendingBilling::STATUS_PENDING,
        ]);

        ExchangeRate::create([
            'currency_code' => 'USD',
            'effective_date' => Carbon::today()->toDateString(),
            'forex_selling' => 30,
        ]);

        $service = new PendingBillingService();
        $service->refreshAmountsForRecord($pendingBilling);
        $pendingBilling->refresh();

        // 10 USD alış × 1 adet × 30 kur = 300 TL beklenen alış
        $this->assertEquals(300, (float) $pendingBilling->expected_alis_tl);
        // 300 TL × (12/10) = 360 TL beklenen satış
        $this->assertEquals(360, (float) $pendingBilling->expected_satis_tl);

        // Alış faturası girilir
        $pendingBilling->update([
            'supplier_invoice_number' => 'ALS-001',
            'supplier_invoice_date' => '2026-02-15',
            'actual_alis_tl' => 250,
            'expected_alis_tl' => 0,
        ]);

        $service->refreshAmountsForRecord($pendingBilling);
        $pendingBilling->refresh();

        // 250 TL × (12/10) = 300 TL beklenen satış
        $this->assertEquals(300, (float) $pendingBilling->expected_satis_tl);

        // Abonelik satış fiyatı değişir
        $subscription->update(['usd_birim_satis' => 15]);

        $pendingBilling->refresh();

        // 250 TL × (15/10) = 375 TL beklenen satış güncellenmeli
        $this->assertEquals(375, (float) $pendingBilling->expected_satis_tl);
    }
}
