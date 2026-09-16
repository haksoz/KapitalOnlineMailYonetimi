<?php

namespace Tests\Feature;

use App\Models\Cari;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoicePaymentTest extends TestCase
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

    private function makeInvoice(bool $isPaid = false): SalesInvoice
    {
        $customerCari = Cari::create([
            'name' => 'Musteri Odeme',
            'short_name' => 'Musteri Odeme',
            'cari_type' => 'customer',
            'tax_number' => '9988776655',
        ]);

        return SalesInvoice::create([
            'customer_cari_id' => $customerCari->id,
            'total_amount_tl' => 120.50,
            'order_number' => 'FTN000099',
            'is_paid' => $isPaid,
            'paid_at' => $isPaid ? now() : null,
        ]);
    }

    public function test_new_invoice_is_unpaid_by_default(): void
    {
        $invoice = $this->makeInvoice();

        $this->assertFalse($invoice->fresh()->is_paid);
        $this->assertNull($invoice->fresh()->paid_at);
    }

    public function test_index_shows_unpaid_badge_and_mark_paid_action(): void
    {
        $user = $this->makeUser();
        $this->makeInvoice();

        $response = $this->actingAs($user)->get(route('sales-invoices.index'));

        $response->assertOk();
        $response->assertSee('Ödenmedi', false);
        $response->assertSee('Ödendi işaretle', false);
        $response->assertDontSee('Ödenmedi işaretle', false);
        $response->assertSee('Vade gir', false);
    }

    public function test_show_displays_payment_status_and_action(): void
    {
        $user = $this->makeUser();
        $invoice = $this->makeInvoice();

        $response = $this->actingAs($user)->get(route('sales-invoices.show', $invoice));

        $response->assertOk();
        $response->assertSee('Ödeme durumu', false);
        $response->assertSee('Ödenmedi', false);
        $response->assertSee('Ödendi işaretle', false);
        $response->assertSee('Fatura bilgisi düzenle', false);
    }

    public function test_mark_paid_from_index_sets_paid_status(): void
    {
        $user = $this->makeUser();
        $invoice = $this->makeInvoice();

        $response = $this->actingAs($user)
            ->from(route('sales-invoices.index'))
            ->post(route('sales-invoices.mark-paid', $invoice));

        $response->assertRedirect(route('sales-invoices.index'));
        $response->assertSessionHas('success');

        $invoice->refresh();
        $this->assertTrue($invoice->is_paid);
        $this->assertNotNull($invoice->paid_at);
    }

    public function test_mark_paid_from_show_returns_to_details(): void
    {
        $user = $this->makeUser();
        $invoice = $this->makeInvoice();

        $response = $this->actingAs($user)
            ->from(route('sales-invoices.show', $invoice))
            ->post(route('sales-invoices.mark-paid', $invoice));

        $response->assertRedirect(route('sales-invoices.show', $invoice));
        $this->assertTrue($invoice->fresh()->is_paid);
    }

    public function test_mark_unpaid_clears_payment(): void
    {
        $user = $this->makeUser();
        $invoice = $this->makeInvoice(isPaid: true);

        $response = $this->actingAs($user)
            ->from(route('sales-invoices.show', $invoice))
            ->post(route('sales-invoices.mark-unpaid', $invoice));

        $response->assertRedirect(route('sales-invoices.show', $invoice));
        $response->assertSessionHas('success');

        $invoice->refresh();
        $this->assertFalse($invoice->is_paid);
        $this->assertNull($invoice->paid_at);
    }

    public function test_mark_paid_is_idempotent(): void
    {
        $user = $this->makeUser();
        $invoice = $this->makeInvoice(isPaid: true);
        $originalPaidAt = $invoice->paid_at;

        $response = $this->actingAs($user)
            ->from(route('sales-invoices.index'))
            ->post(route('sales-invoices.mark-paid', $invoice));

        $response->assertRedirect(route('sales-invoices.index'));
        $response->assertSessionHas('info');
        $this->assertEquals($originalPaidAt?->timestamp, $invoice->fresh()->paid_at?->timestamp);
    }

    public function test_index_pagination_preserves_search_query(): void
    {
        $user = $this->makeUser();

        $matchingCari = Cari::create([
            'name' => 'Aranan Musteri',
            'short_name' => 'Aranan',
            'cari_type' => 'customer',
            'tax_number' => '1111111111',
        ]);

        $otherCari = Cari::create([
            'name' => 'Diger Musteri',
            'short_name' => 'Diger',
            'cari_type' => 'customer',
            'tax_number' => '2222222222',
        ]);

        for ($i = 1; $i <= 16; $i++) {
            SalesInvoice::create([
                'customer_cari_id' => $matchingCari->id,
                'total_amount_tl' => 100,
                'order_number' => 'FTN' . str_pad((string) $i, 6, '0', STR_PAD_LEFT),
            ]);
        }

        SalesInvoice::create([
            'customer_cari_id' => $otherCari->id,
            'total_amount_tl' => 100,
            'order_number' => 'FTN999999',
        ]);

        $page1 = $this->actingAs($user)->get(route('sales-invoices.index', ['search' => 'Aranan']));
        $page1->assertOk();
        $page1->assertSee('search=Aranan', false);
        $page1->assertDontSee('FTN999999', false);

        $page2 = $this->actingAs($user)->get(route('sales-invoices.index', [
            'search' => 'Aranan',
            'page' => 2,
        ]));
        $page2->assertOk();
        $page2->assertSee('Aranan', false);
        $page2->assertDontSee('FTN999999', false);
        $page2->assertSee('value="Aranan"', false);
    }

    public function test_index_filters_by_paid_status(): void
    {
        $user = $this->makeUser();

        $paidCari = Cari::create([
            'name' => 'Odendi Musteri',
            'short_name' => 'Odendi Musteri',
            'cari_type' => 'customer',
            'tax_number' => '3333333333',
        ]);
        $unpaidCari = Cari::create([
            'name' => 'Odenmedi Musteri',
            'short_name' => 'Odenmedi Musteri',
            'cari_type' => 'customer',
            'tax_number' => '4444444444',
        ]);

        SalesInvoice::create([
            'customer_cari_id' => $paidCari->id,
            'total_amount_tl' => 100,
            'order_number' => 'FTNPAID01',
            'is_paid' => true,
            'paid_at' => now(),
        ]);
        SalesInvoice::create([
            'customer_cari_id' => $unpaidCari->id,
            'total_amount_tl' => 100,
            'order_number' => 'FTNUNPAID01',
            'is_paid' => false,
        ]);

        $paid = $this->actingAs($user)->get(route('sales-invoices.index', ['payment_status' => 'paid']));
        $paid->assertOk();
        $paid->assertSee('FTNPAID01', false);
        $paid->assertDontSee('FTNUNPAID01', false);
        $paid->assertSee('value="paid" selected', false);

        $unpaid = $this->actingAs($user)->get(route('sales-invoices.index', ['payment_status' => 'unpaid']));
        $unpaid->assertOk();
        $unpaid->assertSee('FTNUNPAID01', false);
        $unpaid->assertDontSee('FTNPAID01', false);
        $unpaid->assertSee('value="unpaid" selected', false);
    }

    public function test_index_combines_search_and_payment_status(): void
    {
        $user = $this->makeUser();

        $matchingCari = Cari::create([
            'name' => 'Ortak Musteri',
            'short_name' => 'Ortak',
            'cari_type' => 'customer',
            'tax_number' => '5555555555',
        ]);
        $otherCari = Cari::create([
            'name' => 'Baska Musteri',
            'short_name' => 'Baska',
            'cari_type' => 'customer',
            'tax_number' => '6666666666',
        ]);

        SalesInvoice::create([
            'customer_cari_id' => $matchingCari->id,
            'total_amount_tl' => 100,
            'order_number' => 'FTNORTAK01',
            'is_paid' => true,
            'paid_at' => now(),
        ]);
        SalesInvoice::create([
            'customer_cari_id' => $matchingCari->id,
            'total_amount_tl' => 100,
            'order_number' => 'FTNORTAK02',
            'is_paid' => false,
        ]);
        SalesInvoice::create([
            'customer_cari_id' => $otherCari->id,
            'total_amount_tl' => 100,
            'order_number' => 'FTNBASKA01',
            'is_paid' => false,
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.index', [
            'search' => 'Ortak',
            'payment_status' => 'unpaid',
        ]));

        $response->assertOk();
        $response->assertSee('FTNORTAK02', false);
        $response->assertDontSee('FTNORTAK01', false);
        $response->assertDontSee('FTNBASKA01', false);
        $response->assertSee('value="Ortak"', false);
        $response->assertSee('value="unpaid" selected', false);
    }

    public function test_index_filters_by_customer_and_invoice_period(): void
    {
        $user = $this->makeUser();

        $matchingCari = Cari::create([
            'name' => 'Donem Musteri',
            'short_name' => 'Donem',
            'cari_type' => 'customer',
            'tax_number' => '7777777777',
        ]);
        $otherCari = Cari::create([
            'name' => 'Harici Musteri',
            'short_name' => 'Harici',
            'cari_type' => 'customer',
            'tax_number' => '8888888888',
        ]);

        SalesInvoice::create([
            'customer_cari_id' => $matchingCari->id,
            'total_amount_tl' => 100,
            'order_number' => 'FTNDONEM01',
            'our_invoice_date' => '2026-03-15',
        ]);
        SalesInvoice::create([
            'customer_cari_id' => $matchingCari->id,
            'total_amount_tl' => 100,
            'order_number' => 'FTNDONEM02',
            'our_invoice_date' => '2026-04-10',
        ]);
        SalesInvoice::create([
            'customer_cari_id' => $otherCari->id,
            'total_amount_tl' => 100,
            'order_number' => 'FTNDONEM03',
            'our_invoice_date' => '2026-03-20',
        ]);

        $byCustomer = $this->actingAs($user)->get(route('sales-invoices.index', [
            'customer_cari_id' => $matchingCari->id,
        ]));
        $byCustomer->assertOk();
        $byCustomer->assertSee('FTNDONEM01', false);
        $byCustomer->assertSee('FTNDONEM02', false);
        $byCustomer->assertDontSee('FTNDONEM03', false);

        $byPeriod = $this->actingAs($user)->get(route('sales-invoices.index', [
            'customer_cari_id' => $matchingCari->id,
            'period_year' => 2026,
            'period_month' => 3,
        ]));
        $byPeriod->assertOk();
        $byPeriod->assertSee('FTNDONEM01', false);
        $byPeriod->assertDontSee('FTNDONEM02', false);
        $byPeriod->assertDontSee('FTNDONEM03', false);
        $byPeriod->assertSee('Filtreyi temizle', false);
    }
}
