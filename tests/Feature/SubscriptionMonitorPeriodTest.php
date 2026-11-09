<?php

namespace Tests\Feature;

use App\Models\Cari;
use App\Models\PendingBilling;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Console\Scheduling\Schedule;
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

    public function test_deleted_order_is_not_listed_as_a_missing_period(): void
    {
        Carbon::setTestNow('2026-10-01');
        $user = $this->makeUser();
        $subscription = $this->makeSubscription(
            shortName: 'Silinen Donem',
            taxNumber: '1110000005',
            baslangic: '2025-09-09',
            bitis: '2026-10-09',
            durum: Subscription::DURUM_ACTIVE,
            autoRenew: false,
        );

        PendingBilling::create([
            'subscription_id' => $subscription->id,
            'period_start' => '2026-09-09',
            'period_end' => '2026-10-08',
            'status' => PendingBilling::STATUS_PENDING,
            'is_deleted' => true,
        ]);

        $response = $this->actingAs($user)->get(route('subscription-monitor.index', [
            'year' => 2026,
            'month' => 9,
        ]));

        $response->assertOk();
        $response->assertDontSee('Silinen Donem');
        $response->assertSee('Seçilen ay için aktif aboneliği olan cari bulunamadı.');
    }

    public function test_scheduled_renewal_creates_the_new_monthly_order_on_the_same_istanbul_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-09 00:00:00', 'Europe/Istanbul'));
        $subscription = $this->makeSubscription(
            shortName: 'Aylik Otomatik',
            taxNumber: '1110000006',
            baslangic: '2026-10-09',
            bitis: '2026-11-09',
            durum: Subscription::DURUM_ACTIVE,
            autoRenew: true,
        );

        $start = now();
        $runs = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains($event->command ?? '', 'subscriptions:process-renewals')
                || str_contains($event->command ?? '', 'pending-billings:enqueue'))
            ->map(fn ($event) => [
                'at' => $event->nextRunDate($start, 0, true),
                'command' => str_contains($event->command, 'subscriptions:process-renewals')
                    ? 'subscriptions:process-renewals' : 'pending-billings:enqueue',
            ])
            ->sortBy(fn ($run) => $run['at']->getTimestamp());

        $this->assertCount(2, $runs);
        foreach ($runs as $run) {
            Carbon::setTestNow($run['at']);
            $this->artisan($run['command'])->assertSuccessful();
        }

        $this->assertSame('2026-12-09', $subscription->fresh()->bitis_tarihi->toDateString());
        $order = PendingBilling::where('subscription_id', $subscription->id)->sole();
        $this->assertSame('2026-11-09', $order->period_start->toDateString());
        $this->assertSame(PendingBilling::STATUS_PENDING, $order->status);

        $this->artisan('pending-billings:enqueue')->assertSuccessful();
        $this->assertSame(1, PendingBilling::where('subscription_id', $subscription->id)->count());
    }

    public function test_single_order_repair_only_creates_the_selected_subscription_and_month(): void
    {
        Carbon::setTestNow('2026-11-09');
        $user = $this->makeUser();
        $subscription = $this->makeSubscription('Tekli', '1110000007', '2026-04-06', '2026-12-06', Subscription::DURUM_ACTIVE, true);
        $other = $this->makeSubscription('Diger', '1110000008', '2026-04-06', '2026-12-06', Subscription::DURUM_ACTIVE, true);
        $other->update(['customer_cari_id' => $subscription->customer_cari_id]);

        $this->actingAs($user)->get(route('subscription-monitor.index', ['year' => 2026, 'month' => 11]))
            ->assertOk()->assertSee(route('subscription-monitor.enqueue-missing-for-subscription', $subscription));

        $payload = ['status' => 'eksik', 'search' => 'Tekli', 'per_page' => 15, 'page' => 2, 'year' => 2026, 'month' => 11];
        $route = route('subscription-monitor.enqueue-missing-for-subscription', $subscription);
        $this->post($route, $payload)->assertSessionHas('success')
            ->assertRedirect(route('subscription-monitor.index', $payload));

        $order = PendingBilling::sole();
        $this->assertEquals($subscription->id, $order->subscription_id);
        $this->assertSame('2026-11-06', $order->period_start->toDateString());
        $this->assertSame('2026-12-05', $order->period_end->toDateString());
        $this->assertSame('2026-12-06', $subscription->fresh()->bitis_tarihi->toDateString());
        $this->assertSame('2026-12-06', $other->fresh()->bitis_tarihi->toDateString());

        $this->post($route, $payload)->assertSessionHas('info');
        $this->assertSame(1, PendingBilling::count());
    }

    public function test_single_order_repair_extends_only_the_selected_subscription_when_renewal_was_missed(): void
    {
        Carbon::setTestNow('2026-11-09');
        $subscription = $this->makeSubscription('Geciken', '1110000009', '2026-10-06', '2026-11-06', Subscription::DURUM_ACTIVE, true);
        $other = $this->makeSubscription('Geciken Diger', '1110000010', '2026-10-06', '2026-11-06', Subscription::DURUM_ACTIVE, true);
        $other->update(['customer_cari_id' => $subscription->customer_cari_id]);

        $this->actingAs($this->makeUser())->post(route('subscription-monitor.enqueue-missing-for-subscription', $subscription), [
            'year' => 2026, 'month' => 11,
        ])->assertSessionHas('success');

        $this->assertSame('2026-12-06', $subscription->fresh()->bitis_tarihi->toDateString());
        $this->assertSame('2026-11-06', $other->fresh()->bitis_tarihi->toDateString());
        $this->assertSame(1, PendingBilling::count());
    }

    public function test_single_order_repair_does_not_duplicate_existing_or_deleted_orders(): void
    {
        Carbon::setTestNow('2026-11-09');
        $this->actingAs($this->makeUser());
        foreach ([PendingBilling::STATUS_PENDING, PendingBilling::STATUS_INVOICED, PendingBilling::STATUS_POSTPONED, 'deleted'] as $index => $status) {
            $subscription = $this->makeSubscription('Mevcut '.$index, '112000000'.$index, '2026-10-06', '2026-11-06', Subscription::DURUM_ACTIVE, true);
            PendingBilling::create([
                'subscription_id' => $subscription->id,
                'period_start' => '2026-11-06', 'period_end' => '2026-12-05',
                'status' => $status === 'deleted' ? PendingBilling::STATUS_PENDING : $status,
                'is_deleted' => $status === 'deleted',
            ]);

            $this->post(route('subscription-monitor.enqueue-missing-for-subscription', $subscription), [
                'year' => 2026, 'month' => 11,
            ])->assertSessionHas('info');

            $this->assertSame(1, PendingBilling::withDeleted()->where('subscription_id', $subscription->id)->count());
            $this->assertSame('2026-11-06', $subscription->fresh()->bitis_tarihi->toDateString());
        }
    }

    public function test_single_order_repair_does_not_reactivate_or_extend_nonrenewing_subscriptions(): void
    {
        Carbon::setTestNow('2026-11-09');
        $this->actingAs($this->makeUser());
        foreach ([Subscription::DURUM_ACTIVE, Subscription::DURUM_CANCELLED] as $index => $status) {
            $subscription = $this->makeSubscription('Yenilenmeyen '.$index, '113000000'.$index, '2026-10-06', '2026-11-06', $status, false);
            $this->post(route('subscription-monitor.enqueue-missing-for-subscription', $subscription), [
                'year' => 2026, 'month' => 11,
            ])->assertSessionHas('info');
            $this->assertSame('2026-11-06', $subscription->fresh()->bitis_tarihi->toDateString());
            $this->assertSame($status, $subscription->fresh()->durum);
        }
        $this->assertDatabaseCount('pending_billings', 0);
    }

    public function test_single_order_repair_validates_the_month_and_requires_authentication(): void
    {
        $subscription = $this->makeSubscription('Dogrulama', '1140000000', '2026-10-06', '2026-12-06', Subscription::DURUM_ACTIVE, true);
        $route = route('subscription-monitor.enqueue-missing-for-subscription', $subscription);
        $this->post($route, ['year' => 2026, 'month' => 11])->assertRedirect(route('login'));
        $this->actingAs($this->makeUser())->post($route, ['year' => 2026, 'month' => 13])
            ->assertSessionHasErrors('month');
        $this->assertDatabaseCount('pending_billings', 0);
    }

    public function test_contract_search_limits_details_and_totals_and_survives_single_order_repair(): void
    {
        Carbon::setTestNow('2026-11-09');
        $subscription = $this->makeSubscription('Sozlesme Cari', '1150000001', '2026-10-06', '2026-12-06', Subscription::DURUM_ACTIVE, true);
        $subscription->update(['sozlesme_no' => '2503087060']);
        $other = $this->makeSubscription('Diger Cari', '1150000002', '2026-10-06', '2026-12-06', Subscription::DURUM_ACTIVE, true);
        $other->update(['customer_cari_id' => $subscription->customer_cari_id, 'sozlesme_no' => '2210061688']);
        PendingBilling::create([
            'subscription_id' => $other->id,
            'period_start' => '2026-11-06', 'period_end' => '2026-12-05',
            'status' => PendingBilling::STATUS_PENDING,
        ]);

        $this->actingAs($this->makeUser());
        $filters = ['sozlesme_no' => '  308706  ', 'year' => 2026, 'month' => 11];
        $this->get(route('subscription-monitor.index', $filters))
            ->assertOk()
            ->assertSee('Sözleşme: 2503087060')
            ->assertDontSee('2210061688')
            ->assertDontSee('Bu ay için siparişleri oluştur')
            ->assertSee('Bu ayın siparişini oluştur')
            ->assertViewHas('totals', fn ($totals) => $totals['subscription_count'] === 1 && $totals['pending_count'] === 0)
            ->assertViewHas('customerSummaries', fn ($rows) => count($rows->first()['details']) === 1);

        $this->post(route('subscription-monitor.enqueue-missing-for-subscription', $subscription), $filters)
            ->assertSessionHas('success')
            ->assertRedirect(route('subscription-monitor.index', [
                'sozlesme_no' => '308706', 'year' => 2026, 'month' => 11,
            ]));

        $this->get(route('subscription-monitor.index', $filters))
            ->assertOk()->assertViewHas('totals', fn ($totals) => $totals['subscription_count'] === 1 && $totals['pending_count'] === 1);
        $this->get(route('subscription-monitor.index', array_merge($filters, ['sozlesme_no' => 'BULUNAMAYAN'])))
            ->assertOk()->assertViewHas('totals', fn ($totals) => $totals['customer_count'] === 0);
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
