<?php

namespace Tests\Feature;

use App\Models\Cari;
use App\Models\NotificationDefinition;
use App\Models\NotificationSend;
use App\Models\PendingBilling;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use App\Models\Subscription;
use App\Models\User;
use App\Services\InvoiceNotificationDispatcher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoiceNotificationTest extends TestCase
{
    use RefreshDatabase;

    private int $taxSeq = 1;

    private function makeAdmin(): User
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
    private function makePendingOrder(int $dueDays = 7, ?string $email = 'musteri@example.com'): array
    {
        $customerCari = Cari::create([
            'name' => 'Musteri Bildiri',
            'short_name' => 'Musteri Bildiri',
            'cari_type' => 'customer',
            'tax_number' => (string) (5500000000 + $this->taxSeq++),
            'email' => $email,
        ]);

        $subscription = Subscription::create([
            'customer_cari_id' => $customerCari->id,
            'provider_cari_id' => $customerCari->id,
            'sozlesme_no' => 'SOZ-NTF-001',
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
            'odeme_vadesi_gun' => $dueDays,
        ]);

        $pendingBilling = PendingBilling::create([
            'subscription_id' => $subscription->id,
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'status' => PendingBilling::STATUS_INVOICED,
            'expected_alis_tl' => 100,
            'expected_satis_tl' => 200,
            'actual_satis_tl' => 200,
        ]);

        return [$customerCari, $subscription, $pendingBilling];
    }

    private function makeNumberedInvoice(int $dueDays = 7, ?string $email = 'musteri@example.com'): SalesInvoice
    {
        [$customerCari, , $pendingBilling] = $this->makePendingOrder($dueDays, $email);

        $invoice = SalesInvoice::create([
            'customer_cari_id' => $customerCari->id,
            'total_amount_tl' => 200,
            'order_number' => 'FTN000101',
            'our_invoice_number' => 'ABC2026001',
            'our_invoice_date' => '2026-09-01',
            'is_paid' => false,
        ]);
        SalesInvoiceLine::create([
            'sales_invoice_id' => $invoice->id,
            'pending_billing_id' => $pendingBilling->id,
            'line_amount_tl' => 200,
        ]);
        $invoice->refreshDueDate();

        return $invoice->fresh(['customerCari', 'lines.pendingBilling.subscription']);
    }

    public function test_entering_invoice_number_sets_due_date_from_subscription(): void
    {
        $admin = $this->makeAdmin();
        [$customerCari, , $pendingBilling] = $this->makePendingOrder(10);

        $invoice = SalesInvoice::create([
            'customer_cari_id' => $customerCari->id,
            'total_amount_tl' => 200,
            'order_number' => 'FTN000102',
        ]);
        SalesInvoiceLine::create([
            'sales_invoice_id' => $invoice->id,
            'pending_billing_id' => $pendingBilling->id,
            'line_amount_tl' => 200,
        ]);

        $this->assertNull($invoice->fresh()->due_date);

        $this->actingAs($admin)->patch(route('sales-invoices.update-invoice-details', $invoice), [
            'our_invoice_number' => 'XYZ-1',
            'our_invoice_date' => '2026-09-01',
        ]);

        $invoice->refresh();
        $this->assertSame('2026-09-11', $invoice->due_date?->format('Y-m-d'));
    }

    public function test_due_date_can_be_set_manually_and_overrides_subscription(): void
    {
        $admin = $this->makeAdmin();
        [$customerCari, , $pendingBilling] = $this->makePendingOrder(10);

        $invoice = SalesInvoice::create([
            'customer_cari_id' => $customerCari->id,
            'total_amount_tl' => 200,
            'order_number' => 'FTN000103',
        ]);
        SalesInvoiceLine::create([
            'sales_invoice_id' => $invoice->id,
            'pending_billing_id' => $pendingBilling->id,
            'line_amount_tl' => 200,
        ]);

        $this->actingAs($admin)
            ->get(route('sales-invoices.invoice-details', $invoice))
            ->assertOk()
            ->assertSee('Vade tarihi', false);

        $this->actingAs($admin)->patch(route('sales-invoices.update-invoice-details', $invoice), [
            'our_invoice_number' => 'XYZ-2',
            'our_invoice_date' => '2026-09-01',
            'due_date' => '2026-10-15',
        ])->assertRedirect(route('sales-invoices.index'));

        $this->assertSame('2026-10-15', $invoice->fresh()->due_date?->format('Y-m-d'));

        $this->actingAs($admin)->patch(route('sales-invoices.update-invoice-details', $invoice), [
            'our_invoice_number' => 'XYZ-2',
            'our_invoice_date' => '2026-09-01',
            'due_date' => '2026-10-15',
        ]);

        $this->assertSame('2026-10-15', $invoice->fresh()->due_date?->format('Y-m-d'));
    }

    public function test_due_date_can_be_entered_when_subscription_has_no_term(): void
    {
        $admin = $this->makeAdmin();
        [$customerCari, $subscription, $pendingBilling] = $this->makePendingOrder(10);
        $subscription->update(['odeme_vadesi_gun' => null]);

        $invoice = SalesInvoice::create([
            'customer_cari_id' => $customerCari->id,
            'total_amount_tl' => 200,
            'our_invoice_number' => 'ESKI-1',
            'our_invoice_date' => '2026-08-01',
            'order_number' => 'FTN000104',
        ]);
        SalesInvoiceLine::create([
            'sales_invoice_id' => $invoice->id,
            'pending_billing_id' => $pendingBilling->id,
            'line_amount_tl' => 200,
        ]);

        $this->assertNull($invoice->fresh()->computeDueDate());

        $this->actingAs($admin)->patch(route('sales-invoices.update-invoice-details', $invoice), [
            'our_invoice_number' => 'ESKI-1',
            'our_invoice_date' => '2026-08-01',
            'due_date' => '2026-08-20',
        ]);

        $this->assertSame('2026-08-20', $invoice->fresh()->due_date?->format('Y-m-d'));
    }

    public function test_clearing_due_date_falls_back_to_subscription_term(): void
    {
        $admin = $this->makeAdmin();
        $invoice = $this->makeNumberedInvoice(10);
        $invoice->update(['due_date' => '2026-12-31']);

        $this->actingAs($admin)->patch(route('sales-invoices.update-invoice-details', $invoice), [
            'our_invoice_number' => $invoice->our_invoice_number,
            'our_invoice_date' => $invoice->our_invoice_date->format('Y-m-d'),
            'due_date' => '',
        ]);

        $this->assertSame('2026-09-11', $invoice->fresh()->due_date?->format('Y-m-d'));
    }

    public function test_due_date_uses_shortest_payment_term_on_mixed_lines(): void
    {
        [$customerCari, $subscription, $pendingBilling] = $this->makePendingOrder(15);

        $secondPending = PendingBilling::create([
            'subscription_id' => $subscription->id,
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
            'status' => PendingBilling::STATUS_INVOICED,
            'expected_satis_tl' => 200,
            'actual_satis_tl' => 200,
        ]);
        $subscription->update(['odeme_vadesi_gun' => 15]);

        $otherSub = Subscription::create([
            'customer_cari_id' => $customerCari->id,
            'provider_cari_id' => $customerCari->id,
            'sozlesme_no' => 'SOZ-NTF-002',
            'baslangic_tarihi' => '2026-01-01',
            'bitis_tarihi' => '2027-01-01',
            'taahhut_tipi' => Subscription::TAAHHUT_MONTHLY_COMMITMENT,
            'faturalama_periyodu' => Subscription::FATURALAMA_MONTHLY,
            'durum' => Subscription::DURUM_ACTIVE,
            'auto_renew' => true,
            'quantity' => 1,
            'odeme_vadesi_gun' => 5,
        ]);
        $otherPending = PendingBilling::create([
            'subscription_id' => $otherSub->id,
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'status' => PendingBilling::STATUS_INVOICED,
            'actual_satis_tl' => 50,
        ]);

        $invoice = SalesInvoice::create([
            'customer_cari_id' => $customerCari->id,
            'total_amount_tl' => 250,
            'our_invoice_number' => 'MIX-1',
            'our_invoice_date' => '2026-09-01',
        ]);
        SalesInvoiceLine::create([
            'sales_invoice_id' => $invoice->id,
            'pending_billing_id' => $pendingBilling->id,
            'line_amount_tl' => 200,
        ]);
        SalesInvoiceLine::create([
            'sales_invoice_id' => $invoice->id,
            'pending_billing_id' => $otherPending->id,
            'line_amount_tl' => 50,
        ]);
        $this->assertTrue($secondPending->exists);

        $invoice->refreshDueDate();
        $this->assertSame('2026-09-06', $invoice->fresh()->due_date?->format('Y-m-d'));
    }

    public function test_reminder_is_eligible_until_due_date_not_after(): void
    {
        $invoice = $this->makeNumberedInvoice(7);
        $reminder = NotificationDefinition::query()->where('key', NotificationDefinition::KEY_INVOICE_DUE_REMINDER)->firstOrFail();
        $dispatcher = app(InvoiceNotificationDispatcher::class);

        $this->assertTrue($dispatcher->isEligible($reminder, $invoice, Carbon::parse('2026-09-01')));
        $this->assertTrue($dispatcher->isEligible($reminder, $invoice, Carbon::parse('2026-09-08')));
        $this->assertFalse($dispatcher->isEligible($reminder, $invoice, Carbon::parse('2026-09-09')));
    }

    public function test_overdue_is_eligible_only_after_due_date(): void
    {
        $invoice = $this->makeNumberedInvoice(7);
        $overdue = NotificationDefinition::query()->where('key', NotificationDefinition::KEY_INVOICE_OVERDUE)->firstOrFail();
        $dispatcher = app(InvoiceNotificationDispatcher::class);

        $this->assertFalse($dispatcher->isEligible($overdue, $invoice, Carbon::parse('2026-09-08')));
        $this->assertTrue($dispatcher->isEligible($overdue, $invoice, Carbon::parse('2026-09-09')));
    }

    public function test_paid_and_missing_email_and_missing_number_are_skipped(): void
    {
        $dispatcher = app(InvoiceNotificationDispatcher::class);
        $reminder = NotificationDefinition::query()->where('key', NotificationDefinition::KEY_INVOICE_DUE_REMINDER)->firstOrFail();

        $paid = $this->makeNumberedInvoice(7);
        $paid->update(['is_paid' => true, 'paid_at' => now()]);
        $this->assertFalse($dispatcher->isEligible($reminder, $paid->fresh(['customerCari']), Carbon::parse('2026-09-02')));

        $noMail = $this->makeNumberedInvoice(7, null);
        $this->assertFalse($dispatcher->isEligible($reminder, $noMail, Carbon::parse('2026-09-02')));

        $noNumber = $this->makeNumberedInvoice(7);
        $noNumber->update(['our_invoice_number' => null]);
        $this->assertFalse($dispatcher->isEligible($reminder, $noNumber->fresh(['customerCari']), Carbon::parse('2026-09-02')));
    }

    public function test_dispatch_sends_reminder_and_respects_interval(): void
    {
        Mail::fake();
        $invoice = $this->makeNumberedInvoice(7);
        NotificationDefinition::query()
            ->where('key', NotificationDefinition::KEY_INVOICE_DUE_REMINDER)
            ->update(['is_enabled' => true, 'start_after_days' => 0, 'interval_days' => 7]);
        NotificationDefinition::query()
            ->where('key', NotificationDefinition::KEY_INVOICE_OVERDUE)
            ->update(['is_enabled' => false]);

        $dispatcher = app(InvoiceNotificationDispatcher::class);
        $this->assertSame(1, $dispatcher->dispatch(Carbon::parse('2026-09-01')));
        $this->assertDatabaseCount('notification_sends', 1);

        $this->assertSame(0, $dispatcher->dispatch(Carbon::parse('2026-09-02')));
        $this->assertDatabaseCount('notification_sends', 1);

        $this->assertSame(1, $dispatcher->dispatch(Carbon::parse('2026-09-08')));
        $this->assertDatabaseCount('notification_sends', 2);
        $this->assertTrue($invoice->exists);
    }

    public function test_non_admin_cannot_view_notification_settings(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->get(route('admin.notifications.edit'))->assertForbidden();
    }

    public function test_admin_can_save_notification_settings(): void
    {
        $admin = $this->makeAdmin();
        $reminder = NotificationDefinition::query()->where('key', NotificationDefinition::KEY_INVOICE_DUE_REMINDER)->firstOrFail();
        $overdue = NotificationDefinition::query()->where('key', NotificationDefinition::KEY_INVOICE_OVERDUE)->firstOrFail();

        $response = $this->actingAs($admin)->get(route('admin.notifications.edit'));
        $response->assertOk();
        $response->assertSee('Bildiri Yönetimi', false);

        $this->actingAs($admin)->patch(route('admin.notifications.update'), [
            'definitions' => [
                [
                    'id' => $reminder->id,
                    'is_enabled' => '1',
                    'start_after_days' => 2,
                    'interval_days' => 5,
                    'subject' => 'Hatirlatma {fatura_no}',
                    'body' => 'Merhaba {musteri}',
                ],
                [
                    'id' => $overdue->id,
                    'is_enabled' => '0',
                    'start_after_days' => 1,
                    'interval_days' => 3,
                    'subject' => 'Gecikti {fatura_no}',
                    'body' => 'Vade gecti',
                ],
            ],
        ])->assertRedirect(route('admin.notifications.edit'));

        $reminder->refresh();
        $this->assertTrue($reminder->is_enabled);
        $this->assertSame(2, $reminder->start_after_days);
        $this->assertSame(5, $reminder->interval_days);
        $this->assertSame('Hatirlatma {fatura_no}', $reminder->subject);

        $overdue->refresh();
        $this->assertFalse($overdue->is_enabled);
    }

    public function test_admin_sees_due_dated_invoice_in_test_mail_form(): void
    {
        $admin = $this->makeAdmin();
        $invoice = $this->makeNumberedInvoice(7);

        $this->actingAs($admin)
            ->get(route('admin.notifications.edit'))
            ->assertOk()
            ->assertSee('Test maili gönder', false)
            ->assertSee($invoice->our_invoice_number, false)
            ->assertSee('vade '.$invoice->due_date->format('d.m.Y'), false);
    }

    public function test_admin_can_send_test_mail_with_invoice_placeholders(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin();
        $invoice = $this->makeNumberedInvoice(7);
        $reminder = NotificationDefinition::query()
            ->where('key', NotificationDefinition::KEY_INVOICE_DUE_REMINDER)
            ->firstOrFail();
        $reminder->update([
            'subject' => 'Hatirlatma {fatura_no}',
            'body' => 'Sayin {musteri} vade {vade_tarihi} tutar {tutar} ftn {ftn}',
        ]);

        $this->actingAs($admin)->post(route('admin.notifications.test', $reminder), [
            'test_email' => 'gozlem@example.com',
            'sales_invoice_id' => $invoice->id,
        ])->assertRedirect(route('admin.notifications.edit'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('notification_sends', 0);

        $replacements = app(InvoiceNotificationDispatcher::class)->replacements($invoice->fresh('customerCari'));
        $this->assertSame('Musteri Bildiri', $replacements['{musteri}']);
        $this->assertSame('ABC2026001', $replacements['{fatura_no}']);
        $this->assertSame('01.09.2026', $replacements['{fatura_tarihi}']);
        $this->assertSame($invoice->due_date->format('d.m.Y'), $replacements['{vade_tarihi}']);
        $this->assertSame('200,00 ₺', $replacements['{tutar}']);
        $this->assertSame('FTN000101', $replacements['{ftn}']);
        $this->assertSame(
            'Hatirlatma ABC2026001',
            $reminder->fresh()->renderSubject($replacements)
        );
        $this->assertSame(
            'Sayin Musteri Bildiri vade '.$invoice->due_date->format('d.m.Y').' tutar 200,00 ₺ ftn FTN000101',
            $reminder->fresh()->renderBody($replacements)
        );
    }

    public function test_non_admin_cannot_send_notification_test_mail(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $invoice = $this->makeNumberedInvoice(7);
        $reminder = NotificationDefinition::query()
            ->where('key', NotificationDefinition::KEY_INVOICE_DUE_REMINDER)
            ->firstOrFail();

        $this->actingAs($user)->post(route('admin.notifications.test', $reminder), [
            'test_email' => 'gozlem@example.com',
            'sales_invoice_id' => $invoice->id,
        ])->assertForbidden();
    }
}
