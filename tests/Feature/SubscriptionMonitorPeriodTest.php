<?php

namespace Tests\Feature;

use App\Models\Cari;
use App\Models\PendingBilling;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionMonitorPeriodTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_cancelled_subscription_ending_on_billing_day_is_not_listed_as_missing(): void
    {
        Carbon::setTestNow('2026-10-01');
        $user = $this->makeUser();
        $this->makeSubscription(
            shortName: 'Iptal Eylul Sonu',
            taxNumber: '1110000001',
            baslangic: '2025-09-09',
            bitis: '2026-09-09',
            durum: Subscription::DURUM_CANCELLED,
            autoRenew: false,
        );

        $response = $this->actingAs($user)->get(route('subscription-monitor.index', [
            'year' => 2026,
            'month' => 9,
        ]));

        $response->assertOk();
        $response->assertDontSee('Iptal Eylul Sonu');
        $response->assertDontSee('Bu ay için siparişleri oluştur');
        $response->assertSee('Seçilen ay için aktif aboneliği olan cari bulunamadı.');
    }

    public function test_cancelled_subscription_with_a_due_period_stays_on_the_monitor(): void
    {
        Carbon::setTestNow('2026-10-01');
        $user = $this->makeUser();
        $this->makeSubscription(
            shortName: 'Iptal Eylul Donemli',
            taxNumber: '1110000002',
            baslangic: '2025-09-09',
            bitis: '2026-09-20',
            durum: Subscription::DURUM_CANCELLED,
            autoRenew: false,
        );

        $response = $this->actingAs($user)->get(route('subscription-monitor.index', [
            'year' => 2026,
            'month' => 9,
        ]));

        $response->assertOk();
        $response->assertSee('Iptal Eylul Donemli');
        $response->assertSee('Eksik sipariş var');
    }

    public function test_active_auto_renew_subscription_ending_on_billing_day_can_still_be_ordered(): void
    {
        Carbon::setTestNow('2026-10-01');
        $user = $this->makeUser();
        $this->makeSubscription(
            shortName: 'Aktif Yenilenecek',
            taxNumber: '1110000003',
            baslangic: '2025-09-09',
            bitis: '2026-09-09',
            durum: Subscription::DURUM_ACTIVE,
            autoRenew: true,
        );

        $response = $this->actingAs($user)->get(route('subscription-monitor.index', [
            'year' => 2026,
            'month' => 9,
        ]));

        $response->assertOk();
        $response->assertSee('Aktif Yenilenecek');
        $response->assertSee('Eksik sipariş var');
        $response->assertSee('Bu ay için siparişleri oluştur');
    }

    public function test_cancelled_subscription_with_existing_month_order_stays_visible_without_create_button(): void
    {
        Carbon::setTestNow('2026-10-01');
        $user = $this->makeUser();
        $subscription = $this->makeSubscription(
            shortName: 'Iptal Siparisi Var',
            taxNumber: '1110000004',
            baslangic: '2025-09-09',
            bitis: '2026-09-09',
            durum: Subscription::DURUM_CANCELLED,
            autoRenew: false,
        );

        PendingBilling::create([
            'subscription_id' => $subscription->id,
            'period_start' => '2026-09-09',
            'period_end' => '2026-10-08',
            'status' => PendingBilling::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->get(route('subscription-monitor.index', [
            'year' => 2026,
            'month' => 9,
        ]));

        $response->assertOk();
        $response->assertSee('Iptal Siparisi Var');
        $response->assertSee('Faturalandırılmamış siparişler var');
        $response->assertDontSee('Bu ay için siparişleri oluştur');
    }

    private function makeUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function makeSubscription(
        string $shortName,
        string $taxNumber,
        string $baslangic,
        string $bitis,
        string $durum,
        bool $autoRenew,
    ): Subscription {
        $customerCari = Cari::create([
            'name' => $shortName,
            'short_name' => $shortName,
            'cari_type' => 'customer',
            'tax_number' => $taxNumber,
        ]);

        return Subscription::create([
            'customer_cari_id' => $customerCari->id,
            'provider_cari_id' => $customerCari->id,
            'sozlesme_no' => 'SOZ-'.$taxNumber,
            'baslangic_tarihi' => $baslangic,
            'bitis_tarihi' => $bitis,
            'taahhut_tipi' => Subscription::TAAHHUT_MONTHLY_NO_COMMITMENT,
            'faturalama_periyodu' => Subscription::FATURALAMA_MONTHLY,
            'durum' => $durum,
            'auto_renew' => $autoRenew,
            'quantity' => 1,
            'usd_birim_alis' => 10,
            'usd_birim_satis' => 20,
            'vat_rate' => 20,
        ]);
    }
}
