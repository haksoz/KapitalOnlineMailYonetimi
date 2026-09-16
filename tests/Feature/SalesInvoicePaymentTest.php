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
        $page1->assertDontSee('Diger Musteri', false);

        $page2 = $this->actingAs($user)->get(route('sales-invoices.index', [
            'search' => 'Aranan',
            'page' => 2,
        ]));
        $page2->assertOk();
        $page2->assertSee('Aranan', false);
        $page2->assertDontSee('Diger Musteri', false);
        $page2->assertSee('value="Aranan"', false);
    }
}
