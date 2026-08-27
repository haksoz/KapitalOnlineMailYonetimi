<?php

namespace Tests\Feature;

use App\Http\Controllers\SubscriptionMonitorController;
use App\Models\Cari;
use App\Models\ExpenseSettlement;
use App\Models\ExpenseSettlementLine;
use App\Models\PendingBilling;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AdminCariLedgerReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseSettlementTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @return array{0: Cari, 1: Subscription, 2: PendingBilling}
     */
    private function makePendingOrder(string $periodStart = '2026-08-01', string $periodEnd = '2026-08-31'): array
    {
        $customerCari = Cari::create([
            'name' => 'Musteri Gider',
            'short_name' => 'Musteri Gider',
            'cari_type' => 'customer',
            'tax_number' => '9988776655',
        ]);

        $subscription = Subscription::create([
            'customer_cari_id' => $customerCari->id,
            'provider_cari_id' => $customerCari->id,
            'sozlesme_no' => 'SOZ-GDN-001',
            'baslangic_tarihi' => '2026-01-01',
            'bitis_tarihi' => '2027-01-01',
            'taahhut_tipi' => Subscription::TAAHHUT_MONTHLY_COMMITMENT,
            'faturalama_periyodu' => Subscription::FATURALAMA_MONTHLY,
            'durum' => Subscription::DURUM_ACTIVE,
            'auto_renew' => true,
            'quantity' => 1,
            'usd_birim_alis' => 10,
            'usd_birim_satis' => 20,
            'vat_rate' => 20,
        ]);

        $pendingBilling = PendingBilling::create([
            'subscription_id' => $subscription->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => PendingBilling::STATUS_PENDING,
            'expected_alis_tl' => 100,
            'expected_satis_tl' => 200,
        ]);

        return [$customerCari, $subscription, $pendingBilling];
    }

    public function test_store_creates_settlement_and_marks_pending_expensed(): void
    {
        $user = $this->makeUser();
        [$customerCari, , $pendingBilling] = $this->makePendingOrder();

        $response = $this->actingAs($user)->post(route('expense-settlements.store'), [
            'customer_cari_id' => $customerCari->id,
            'pending_billing_ids' => [$pendingBilling->id],
            'line_amounts' => [$pendingBilling->id => 200],
            'settlement_date' => '2026-08-15',
        ]);

        $settlement = ExpenseSettlement::query()->first();
        $this->assertNotNull($settlement);
        $response->assertRedirect(route('expense-settlements.show', $settlement));

        $this->assertSame('GDN000001', $settlement->gider_number);
        $this->assertSame('2026-08-15', $settlement->settlement_date->format('Y-m-d'));
        $this->assertEquals(200.0, (float) $settlement->total_amount_tl);

        $pendingBilling->refresh();
        $this->assertSame(PendingBilling::STATUS_EXPENSED, $pendingBilling->status);
        $this->assertEquals(200.0, (float) $pendingBilling->actual_satis_tl);
        $this->assertDatabaseHas('expense_settlement_lines', [
            'expense_settlement_id' => $settlement->id,
            'pending_billing_id' => $pendingBilling->id,
            'line_amount_tl' => 200,
        ]);
    }

    public function test_store_uses_overridden_line_amount_as_actual_satis(): void
    {
        $user = $this->makeUser();
        [$customerCari, , $pendingBilling] = $this->makePendingOrder();

        $response = $this->actingAs($user)->post(route('expense-settlements.store'), [
            'customer_cari_id' => $customerCari->id,
            'pending_billing_ids' => [$pendingBilling->id],
            'line_amounts' => [$pendingBilling->id => 275.50],
            'settlement_date' => '2026-08-15',
        ]);

        $settlement = ExpenseSettlement::query()->firstOrFail();
        $response->assertRedirect(route('expense-settlements.show', $settlement));
        $this->assertEquals(275.50, (float) $settlement->total_amount_tl);

        $pendingBilling->refresh();
        $this->assertEquals(275.50, (float) $pendingBilling->actual_satis_tl);
        $this->assertDatabaseHas('expense_settlement_lines', [
            'pending_billing_id' => $pendingBilling->id,
            'line_amount_tl' => 275.50,
        ]);
    }

    public function test_store_rejects_missing_line_amounts(): void
    {
        $user = $this->makeUser();
        [$customerCari, , $pendingBilling] = $this->makePendingOrder();

        $response = $this->actingAs($user)->from(route('expense-settlements.create', [
            'pending_billing_ids' => [$pendingBilling->id],
        ]))->post(route('expense-settlements.store'), [
            'customer_cari_id' => $customerCari->id,
            'pending_billing_ids' => [$pendingBilling->id],
            'settlement_date' => '2026-08-15',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('line_amounts');
        $this->assertDatabaseCount('expense_settlements', 0);
        $pendingBilling->refresh();
        $this->assertSame(PendingBilling::STATUS_PENDING, $pendingBilling->status);
    }

    public function test_revert_returns_orders_to_pending(): void
    {
        $user = $this->makeUser();
        [$customerCari, , $pendingBilling] = $this->makePendingOrder();

        $this->actingAs($user)->post(route('expense-settlements.store'), [
            'customer_cari_id' => $customerCari->id,
            'pending_billing_ids' => [$pendingBilling->id],
            'line_amounts' => [$pendingBilling->id => 200],
            'settlement_date' => '2026-08-15',
        ]);

        $settlement = ExpenseSettlement::query()->firstOrFail();

        $response = $this->actingAs($user)->post(route('expense-settlements.revert', $settlement));
        $response->assertRedirect(route('pending-billings.index', ['status' => 'pending']));

        $pendingBilling->refresh();
        $this->assertSame(PendingBilling::STATUS_PENDING, $pendingBilling->status);
        $this->assertNull($pendingBilling->actual_satis_tl);
        $this->assertDatabaseCount('expense_settlements', 0);
        $this->assertDatabaseCount('expense_settlement_lines', 0);
    }

    public function test_subscription_monitor_treats_expensed_as_completed(): void
    {
        $user = $this->makeUser();
        [$customerCari, $subscription, $pendingBilling] = $this->makePendingOrder('2026-08-01', '2026-08-31');

        $settlement = ExpenseSettlement::create([
            'customer_cari_id' => $customerCari->id,
            'gider_number' => 'GDN000099',
            'settlement_date' => '2026-08-20',
            'total_amount_tl' => 200,
        ]);
        ExpenseSettlementLine::create([
            'expense_settlement_id' => $settlement->id,
            'pending_billing_id' => $pendingBilling->id,
            'line_amount_tl' => 200,
        ]);
        $pendingBilling->update([
            'status' => PendingBilling::STATUS_EXPENSED,
            'actual_satis_tl' => 200,
        ]);

        $response = $this->actingAs($user)->get(route('subscription-monitor.index', [
            'year' => 2026,
            'month' => 8,
        ]));

        $response->assertOk();
        $response->assertSee(SubscriptionMonitorController::STATUS_TAMAMLANDI);
        $response->assertSee('GDN000099');
        $this->assertTrue($subscription->exists);
    }

    public function test_cari_ledger_includes_expensed_realized_sales(): void
    {
        [$customerCari, , $pendingBilling] = $this->makePendingOrder('2026-08-01', '2026-08-31');

        $settlement = ExpenseSettlement::create([
            'customer_cari_id' => $customerCari->id,
            'gider_number' => 'GDN000050',
            'settlement_date' => '2026-08-18',
            'total_amount_tl' => 200,
        ]);
        ExpenseSettlementLine::create([
            'expense_settlement_id' => $settlement->id,
            'pending_billing_id' => $pendingBilling->id,
            'line_amount_tl' => 200,
        ]);
        $pendingBilling->update([
            'status' => PendingBilling::STATUS_EXPENSED,
            'actual_satis_tl' => 200,
        ]);

        $service = app(AdminCariLedgerReportService::class);
        $report = $service->build([
            'cari_id' => $customerCari->id,
            'statuses' => [PendingBilling::STATUS_EXPENSED],
            'movement_type' => 'satis',
        ], includeGroupedTotals: false, includeGrandTotals: true);

        $this->assertSame(1, $report['meta']['record_count']);
        $row = $report['rows']->first();
        $this->assertNotNull($row);
        $this->assertSame(200.0, (float) $row['gerceklesen_satis_tl']);
        $this->assertSame('GDN000050', $row['satis_fatura_no']);
        $this->assertSame('2026-08-18', $row['islem_tarihi']);
        $this->assertSame(200.0, (float) ($report['grandTotals']['gerceklesen_satis_tl'] ?? 0));
    }
}
