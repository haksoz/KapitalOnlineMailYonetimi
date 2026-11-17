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

    public function test_invalid_xml_returns_to_input_with_message_and_preserves_text(): void
    {
        $this->actingAs($this->makeUser())
            ->post(route('sales-invoices.store-sales-invoice-xml'), ['xml_content' => 'yanlışlıkla yazılan metin'])
            ->assertRedirect(route('sales-invoices.sales-invoice-xml'))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'Geçerli bir XML'))
            ->assertSessionHasInput('xml_content', 'yanlışlıkla yazılan metin');
        $this->assertDatabaseCount('sales_invoices', 0);
    }

    public function test_xml_matching_includes_multiple_periods_before_invoice_month(): void
    {
        [$customer, $subscription, $firstPeriod] = $this->makePendingOrder();
        $customer->update(['country_code' => 'TR']);
        $secondPeriod = PendingBilling::create([
            'subscription_id' => $subscription->id,
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
            'status' => PendingBilling::STATUS_PENDING,
        ]);
        $invoice = SalesInvoice::create(['customer_cari_id' => $customer->id, 'total_amount_tl' => 400]);
        foreach ([$firstPeriod, $secondPeriod] as $period) {
            $invoice->lines()->create(['pending_billing_id' => $period->id, 'line_amount_tl' => 200]);
        }
        $otherCustomer = Cari::create(['name' => 'Other', 'cari_type' => 'customer']);
        $otherInvoice = SalesInvoice::create(['customer_cari_id' => $otherCustomer->id]);
        $otherPeriod = $firstPeriod->replicate();
        $otherPeriod->period_start = '2026-06-01';
        $otherPeriod->period_end = '2026-06-30';
        $otherPeriod->save();
        $otherInvoice->lines()->create(['pending_billing_id' => $otherPeriod->id, 'line_amount_tl' => 200]);
        $issuedInvoice = SalesInvoice::create(['customer_cari_id' => $customer->id, 'our_invoice_number' => 'ISSUED']);
        $issuedPeriod = $firstPeriod->replicate();
        $issuedPeriod->period_start = '2026-07-01';
        $issuedPeriod->period_end = '2026-07-31';
        $issuedPeriod->save();
        $issuedInvoice->lines()->create(['pending_billing_id' => $issuedPeriod->id, 'line_amount_tl' => 200]);
        $xml = <<<'XML'
<Invoice xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2">
    <cbc:ID>TEST-XML</cbc:ID>
    <cbc:IssueDate>2026-11-13</cbc:IssueDate>
    <cac:AccountingCustomerParty><cac:Party><cac:PartyIdentification><cbc:ID schemeID="VKN">1122334455</cbc:ID></cac:PartyIdentification></cac:Party></cac:AccountingCustomerParty>
    <cac:InvoiceLine><cac:Item><cbc:Name>Service *SOZ-FTN-001*</cbc:Name></cac:Item></cac:InvoiceLine>
    <cac:LegalMonetaryTotal><cbc:TaxExclusiveAmount currencyID="TRY">400.01</cbc:TaxExclusiveAmount></cac:LegalMonetaryTotal>
</Invoice>
XML;
        $this->actingAs($this->makeUser())
            ->post(route('sales-invoices.store-sales-invoice-xml'), ['xml_content' => $xml])
            ->assertRedirect(route('sales-invoices.sales-invoice-xml-match-preview'))
            ->assertSessionHas('sales_invoice_xml_parsed', fn ($data) => array_column($data['candidates'], 'id') === [$invoice->id]);
        $this->assertNull($invoice->fresh()->our_invoice_number);
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
