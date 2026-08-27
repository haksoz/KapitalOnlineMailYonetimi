<?php

namespace Tests\Feature;

use App\Models\Cari;
use App\Models\PendingBilling;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoiceLineAmountTest extends TestCase
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
    private function makePendingOrder(): array
    {
        $customerCari = Cari::create([
            'name' => 'Musteri Fatura',
            'short_name' => 'Musteri Fatura',
            'cari_type' => 'customer',
            'tax_number' => '1122334455',
        ]);

        $subscription = Subscription::create([
            'customer_cari_id' => $customerCari->id,
            'provider_cari_id' => $customerCari->id,
            'sozlesme_no' => 'SOZ-FTN-001',
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
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'status' => PendingBilling::STATUS_PENDING,
            'expected_alis_tl' => 100,
            'expected_satis_tl' => 200,
        ]);

        return [$customerCari, $subscription, $pendingBilling];
    }

    public function test_store_uses_overridden_line_amount_as_actual_satis(): void
    {
        $user = $this->makeUser();
        [$customerCari, , $pendingBilling] = $this->makePendingOrder();

        $response = $this->actingAs($user)->post(route('sales-invoices.store'), [
            'customer_cari_id' => $customerCari->id,
            'pending_billing_ids' => [$pendingBilling->id],
            'line_amounts' => [$pendingBilling->id => 333.25],
        ]);

        $invoice = SalesInvoice::query()->firstOrFail();
        $response->assertRedirect(route('sales-invoices.show', $invoice));
        $this->assertEquals(333.25, (float) $invoice->total_amount_tl);

        $pendingBilling->refresh();
        $this->assertSame(PendingBilling::STATUS_INVOICED, $pendingBilling->status);
        $this->assertEquals(333.25, (float) $pendingBilling->actual_satis_tl);
        $this->assertDatabaseHas('sales_invoice_lines', [
            'sales_invoice_id' => $invoice->id,
            'pending_billing_id' => $pendingBilling->id,
            'line_amount_tl' => 333.25,
        ]);
    }

    public function test_store_adds_accumulated_fark_on_top_of_overridden_amount(): void
    {
        $user = $this->makeUser();
        [$customerCari, $subscription, $pendingBilling] = $this->makePendingOrder();

        $prior = PendingBilling::create([
            'subscription_id' => $subscription->id,
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'status' => PendingBilling::STATUS_INVOICED,
            'expected_alis_tl' => 100,
            'expected_satis_tl' => 200,
            'actual_satis_tl' => 180,
            'fee_difference_tl' => 25,
        ]);
        $this->assertTrue($prior->exists);

        $response = $this->actingAs($user)->post(route('sales-invoices.store'), [
            'customer_cari_id' => $customerCari->id,
            'pending_billing_ids' => [$pendingBilling->id],
            'line_amounts' => [$pendingBilling->id => 300],
            'add_fark' => [$pendingBilling->id],
        ]);

        $invoice = SalesInvoice::query()->latest('id')->firstOrFail();
        $response->assertRedirect(route('sales-invoices.show', $invoice));
        $this->assertEquals(325.0, (float) $invoice->total_amount_tl);

        $pendingBilling->refresh();
        $this->assertEquals(325.0, (float) $pendingBilling->actual_satis_tl);
        $this->assertDatabaseHas('sales_invoice_lines', [
            'pending_billing_id' => $pendingBilling->id,
            'line_amount_tl' => 325,
        ]);
    }

    public function test_store_rejects_negative_line_amount(): void
    {
        $user = $this->makeUser();
        [$customerCari, , $pendingBilling] = $this->makePendingOrder();

        $response = $this->actingAs($user)->from(route('sales-invoices.create', [
            'pending_billing_ids' => [$pendingBilling->id],
        ]))->post(route('sales-invoices.store'), [
            'customer_cari_id' => $customerCari->id,
            'pending_billing_ids' => [$pendingBilling->id],
            'line_amounts' => [$pendingBilling->id => -10],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('line_amounts.'.$pendingBilling->id);
        $this->assertDatabaseCount('sales_invoices', 0);
        $this->assertDatabaseCount('sales_invoice_lines', 0);
    }
}
