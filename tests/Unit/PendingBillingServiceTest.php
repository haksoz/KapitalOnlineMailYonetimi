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

    public function test_try_subscription_amounts_ignore_exchange_rate(): void
    {
        $customerCari = Cari::create([
            'name' => 'Musteri B',
            'short_name' => 'Musteri B',
            'cari_type' => 'customer',
            'tax_number' => '9876543210',
        ]);

        $subscription = Subscription::create([
            'customer_cari_id' => $customerCari->id,
            'provider_cari_id' => $customerCari->id,
            'sozlesme_no' => 'SOZ-TRY-001',
            'baslangic_tarihi' => '2026-01-01',
            'bitis_tarihi' => '2027-01-01',
            'taahhut_tipi' => Subscription::TAAHHUT_MONTHLY_COMMITMENT,
            'faturalama_periyodu' => Subscription::FATURALAMA_MONTHLY,
            'durum' => Subscription::DURUM_ACTIVE,
            'auto_renew' => true,
            'quantity' => 2,
            'currency' => Subscription::CURRENCY_TRY,
            'usd_birim_alis' => 0,
            'usd_birim_satis' => 1500,
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

        // Destek: alış 0, satış 1500 TL × 2 adet = 3000 TL (kur yok)
        $this->assertEquals(0, (float) $pendingBilling->expected_alis_tl);
        $this->assertEquals(3000, (float) $pendingBilling->expected_satis_tl);
        $this->assertEquals(1, (float) $pendingBilling->exchange_rate_used);
    }

    public function test_try_subscription_with_purchase_and_sale_prices(): void
    {
        $customerCari = Cari::create([
            'name' => 'Musteri C',
            'short_name' => 'Musteri C',
            'cari_type' => 'customer',
            'tax_number' => '1112223334',
        ]);

        $subscription = Subscription::create([
            'customer_cari_id' => $customerCari->id,
            'provider_cari_id' => $customerCari->id,
            'sozlesme_no' => 'SOZ-TRY-002',
            'baslangic_tarihi' => '2026-01-01',
            'bitis_tarihi' => '2027-01-01',
            'taahhut_tipi' => Subscription::TAAHHUT_MONTHLY_COMMITMENT,
            'faturalama_periyodu' => Subscription::FATURALAMA_MONTHLY,
            'durum' => Subscription::DURUM_ACTIVE,
            'quantity' => 1,
            'currency' => Subscription::CURRENCY_TRY,
            'usd_birim_alis' => 1000,
            'usd_birim_satis' => 1200,
            'vat_rate' => 20,
        ]);

        $pendingBilling = PendingBilling::create([
            'subscription_id' => $subscription->id,
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'status' => PendingBilling::STATUS_PENDING,
        ]);

        $service = new PendingBillingService();
        $service->refreshAmountsForRecord($pendingBilling);
        $pendingBilling->refresh();

        $this->assertEquals(1000, (float) $pendingBilling->expected_alis_tl);
        $this->assertEquals(1200, (float) $pendingBilling->expected_satis_tl);
    }

    public function test_add_first_period_skips_future_start_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-26'));

        $customerCari = Cari::create([
            'name' => 'Musteri Future',
            'short_name' => 'Musteri Future',
            'cari_type' => 'customer',
            'tax_number' => '5555555555',
        ]);

        $subscription = Subscription::create([
            'customer_cari_id' => $customerCari->id,
            'provider_cari_id' => $customerCari->id,
            'sozlesme_no' => 'SOZ-FUTURE-001',
            'baslangic_tarihi' => '2026-09-01',
            'bitis_tarihi' => '2027-09-01',
            'taahhut_tipi' => Subscription::TAAHHUT_MONTHLY_COMMITMENT,
            'faturalama_periyodu' => Subscription::FATURALAMA_MONTHLY,
            'durum' => Subscription::DURUM_ACTIVE,
            'quantity' => 1,
            'currency' => Subscription::CURRENCY_TRY,
            'usd_birim_alis' => 0,
            'usd_birim_satis' => 500,
            'vat_rate' => 20,
        ]);

        $service = new PendingBillingService();
        $result = $service->addFirstPeriodForSubscription($subscription);

        $this->assertNull($result);
        $this->assertDatabaseMissing('pending_billings', [
            'subscription_id' => $subscription->id,
        ]);

        Carbon::setTestNow();
    }

    public function test_add_first_period_creates_when_start_is_today_or_past(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-26'));

        $customerCari = Cari::create([
            'name' => 'Musteri Today',
            'short_name' => 'Musteri Today',
            'cari_type' => 'customer',
            'tax_number' => '6666666666',
        ]);

        $subscription = Subscription::create([
            'customer_cari_id' => $customerCari->id,
            'provider_cari_id' => $customerCari->id,
            'sozlesme_no' => 'SOZ-TODAY-001',
            'baslangic_tarihi' => '2026-08-26',
            'bitis_tarihi' => '2027-08-26',
            'taahhut_tipi' => Subscription::TAAHHUT_MONTHLY_COMMITMENT,
            'faturalama_periyodu' => Subscription::FATURALAMA_MONTHLY,
            'durum' => Subscription::DURUM_ACTIVE,
            'quantity' => 1,
            'currency' => Subscription::CURRENCY_TRY,
            'usd_birim_alis' => 0,
            'usd_birim_satis' => 500,
            'vat_rate' => 20,
        ]);

        $service = new PendingBillingService();
        $result = $service->addFirstPeriodForSubscription($subscription);

        $this->assertNotNull($result);
        $this->assertSame(1, PendingBilling::where('subscription_id', $subscription->id)->count());
        $this->assertTrue(
            PendingBilling::where('subscription_id', $subscription->id)
                ->whereDate('period_start', '2026-08-26')
                ->where('status', PendingBilling::STATUS_PENDING)
                ->exists()
        );

        Carbon::setTestNow();
    }

    public function test_enqueue_due_periods_creates_order_on_future_start_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-01'));

        $customerCari = Cari::create([
            'name' => 'Musteri Enqueue',
            'short_name' => 'Musteri Enqueue',
            'cari_type' => 'customer',
            'tax_number' => '7777777777',
        ]);

        $subscription = Subscription::create([
            'customer_cari_id' => $customerCari->id,
            'provider_cari_id' => $customerCari->id,
            'sozlesme_no' => 'SOZ-ENQ-001',
            'baslangic_tarihi' => '2026-09-01',
            'bitis_tarihi' => '2027-09-01',
            'taahhut_tipi' => Subscription::TAAHHUT_MONTHLY_COMMITMENT,
            'faturalama_periyodu' => Subscription::FATURALAMA_MONTHLY,
            'durum' => Subscription::DURUM_ACTIVE,
            'quantity' => 1,
            'currency' => Subscription::CURRENCY_TRY,
            'usd_birim_alis' => 0,
            'usd_birim_satis' => 500,
            'vat_rate' => 20,
        ]);

        $service = new PendingBillingService();
        $added = $service->enqueueDuePeriods(Carbon::parse('2026-09-01'));

        $this->assertSame(1, $added);
        $this->assertTrue(
            PendingBilling::where('subscription_id', $subscription->id)
                ->whereDate('period_start', '2026-09-01')
                ->exists()
        );

        Carbon::setTestNow();
    }
}
