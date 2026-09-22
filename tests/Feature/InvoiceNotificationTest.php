<?php

namespace Tests\Feature;

use App\Automation\DedupePolicy;
use App\Automation\EventType;
use App\Automation\JobStatus;
use App\Automation\TimingMode;
use App\Models\AutomationJob;
use App\Models\AutomationRule;
use App\Models\Cari;
use App\Models\NotificationTemplate;
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
            'notifications_enabled' => $email !== null && $email !== '',
            'odeme_vadesi_gun' => $dueDays,
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

    private function invoiceRule(EventType $type): AutomationRule
    {
        return AutomationRule::query()->where('event_type', $type)->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $timing
     */
    private function configureInvoiceRule(EventType $type, bool $enabled, array $timing = []): AutomationRule
    {
        $rule = $this->invoiceRule($type);
        $rule->update([
            'is_enabled' => $enabled,
            'timing' => array_merge($rule->timing ?? [], $timing),
        ]);

        return $rule->fresh();
    }

    private function invoiceTemplate(string $legacyKey = 'invoice_due_reminder'): NotificationTemplate
    {
        return NotificationTemplate::query()->where('legacy_key', $legacyKey)->firstOrFail();
    }

    public function test_entering_invoice_number_sets_due_date_from_cari(): void
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

    public function test_entering_invoice_net_and_gross_persists_vat_from_invoice(): void
    {
        $admin = $this->makeAdmin();
        [$customerCari, , $pendingBilling] = $this->makePendingOrder(10);

        $invoice = SalesInvoice::create([
            'customer_cari_id' => $customerCari->id,
            'total_amount_tl' => 200,
            'order_number' => 'FTN000198',
        ]);
        SalesInvoiceLine::create([
            'sales_invoice_id' => $invoice->id,
            'pending_billing_id' => $pendingBilling->id,
            'line_amount_tl' => 200,
        ]);

        $this->actingAs($admin)->patch(route('sales-invoices.update-invoice-details', $invoice), [
            'our_invoice_number' => 'XYZ-NET',
            'our_invoice_date' => '2026-09-01',
            'invoice_total_net_tl' => 200,
            'invoice_total_gross_tl' => 236,
        ])->assertRedirect(route('sales-invoices.index'));

        $invoice->refresh();
        $this->assertEquals(200.0, (float) $invoice->invoice_total_net_tl);
        $this->assertEquals(36.0, (float) $invoice->invoice_total_vat_tl);
        $this->assertEquals(236.0, (float) $invoice->invoice_total_gross_tl);

        $replacements = app(InvoiceNotificationDispatcher::class)->replacements($invoice->fresh());
        $this->assertSame('236,00 ₺', $replacements['{tutar}']);
    }

    public function test_invoice_details_require_both_net_and_gross_when_either_is_entered(): void
    {
        $admin = $this->makeAdmin();
        [$customerCari, , $pendingBilling] = $this->makePendingOrder(10);

        $invoice = SalesInvoice::create([
            'customer_cari_id' => $customerCari->id,
            'total_amount_tl' => 200,
            'order_number' => 'FTN000199',
        ]);
        SalesInvoiceLine::create([
            'sales_invoice_id' => $invoice->id,
            'pending_billing_id' => $pendingBilling->id,
            'line_amount_tl' => 200,
        ]);

        $this->actingAs($admin)
            ->from(route('sales-invoices.invoice-details', $invoice))
            ->patch(route('sales-invoices.update-invoice-details', $invoice), [
                'our_invoice_number' => 'XYZ-PARTIAL',
                'our_invoice_date' => '2026-09-01',
                'invoice_total_net_tl' => 200,
            ])
            ->assertRedirect(route('sales-invoices.invoice-details', $invoice))
            ->assertSessionHasErrors('invoice_total_gross_tl');

        $this->actingAs($admin)
            ->from(route('sales-invoices.invoice-details', $invoice))
            ->patch(route('sales-invoices.update-invoice-details', $invoice), [
                'our_invoice_number' => 'XYZ-PARTIAL',
                'our_invoice_date' => '2026-09-01',
                'invoice_total_net_tl' => 200,
                'invoice_total_gross_tl' => 180,
            ])
            ->assertRedirect(route('sales-invoices.invoice-details', $invoice))
            ->assertSessionHasErrors('invoice_total_gross_tl');
    }

    public function test_due_date_can_be_set_manually_and_overrides_cari(): void
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
            ->assertSee('Vade tarihi', false)
            ->assertSee('KDV dahil toplam (TL)', false);

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

    public function test_due_date_can_be_entered_when_cari_has_no_term(): void
    {
        $admin = $this->makeAdmin();
        [$customerCari, , $pendingBilling] = $this->makePendingOrder(10);
        $customerCari->update(['odeme_vadesi_gun' => null]);

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

    public function test_clearing_due_date_falls_back_to_cari_term(): void
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

    public function test_due_date_uses_customer_cari_term_even_with_mixed_lines(): void
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
        $this->assertSame('2026-09-16', $invoice->fresh()->due_date?->format('Y-m-d'));
    }

    public function test_reminder_is_eligible_until_due_date_not_after(): void
    {
        $invoice = $this->makeNumberedInvoice(7);
        $reminder = $this->invoiceRule(EventType::InvoiceDueApproaching);
        $dispatcher = app(InvoiceNotificationDispatcher::class);

        $this->assertTrue($dispatcher->isEligible($reminder, $invoice, Carbon::parse('2026-09-01')));
        $this->assertTrue($dispatcher->isEligible($reminder, $invoice, Carbon::parse('2026-09-08')));
        $this->assertFalse($dispatcher->isEligible($reminder, $invoice, Carbon::parse('2026-09-09')));
    }

    public function test_reminder_window_starts_offset_days_before_due(): void
    {
        $invoice = $this->makeNumberedInvoice(14);
        $reminder = $this->configureInvoiceRule(EventType::InvoiceDueApproaching, true, ['offset_days' => 7]);
        $dispatcher = app(InvoiceNotificationDispatcher::class);

        $this->assertSame('2026-09-15', $invoice->due_date?->format('Y-m-d'));
        $this->assertFalse($dispatcher->isEligible($reminder, $invoice, Carbon::parse('2026-09-07')));
        $this->assertTrue($dispatcher->isEligible($reminder, $invoice, Carbon::parse('2026-09-08')));
        $this->assertTrue($dispatcher->isEligible($reminder, $invoice, Carbon::parse('2026-09-15')));
        $this->assertFalse($dispatcher->isEligible($reminder, $invoice, Carbon::parse('2026-09-16')));
    }

    public function test_overdue_is_eligible_only_after_due_date(): void
    {
        $invoice = $this->makeNumberedInvoice(7);
        $overdue = $this->invoiceRule(EventType::InvoiceOverdue);
        $dispatcher = app(InvoiceNotificationDispatcher::class);

        $this->assertFalse($dispatcher->isEligible($overdue, $invoice, Carbon::parse('2026-09-08')));
        $this->assertTrue($dispatcher->isEligible($overdue, $invoice, Carbon::parse('2026-09-09')));
    }

    public function test_interest_closure_is_eligible_from_one_month_after_due_date(): void
    {
        $invoice = $this->makeNumberedInvoice(7);
        $legal = $this->configureInvoiceRule(EventType::InvoiceInterestClosure, true, ['offset_days' => 30]);
        $dispatcher = app(InvoiceNotificationDispatcher::class);

        $this->assertFalse($dispatcher->isEligible($legal, $invoice, Carbon::parse('2026-09-08')));
        $this->assertFalse($dispatcher->isEligible($legal, $invoice, Carbon::parse('2026-10-07')));
        $this->assertTrue($dispatcher->isEligible($legal, $invoice, Carbon::parse('2026-10-08')));
        $this->assertTrue($dispatcher->isEligible($legal, $invoice, Carbon::parse('2026-11-01')));
    }

    public function test_overdue_stops_when_interest_closure_window_starts(): void
    {
        $invoice = $this->makeNumberedInvoice(7);
        $overdue = $this->invoiceRule(EventType::InvoiceOverdue);
        $legal = $this->configureInvoiceRule(EventType::InvoiceInterestClosure, true, ['offset_days' => 30]);
        $dispatcher = app(InvoiceNotificationDispatcher::class);

        $this->assertTrue($dispatcher->isEligible($overdue, $invoice, Carbon::parse('2026-09-09')));
        $this->assertTrue($dispatcher->isEligible($overdue, $invoice, Carbon::parse('2026-10-07')));
        $this->assertFalse($dispatcher->isEligible($overdue, $invoice, Carbon::parse('2026-10-08')));
        $this->assertFalse($dispatcher->isEligible($legal, $invoice, Carbon::parse('2026-10-07')));
        $this->assertTrue($dispatcher->isEligible($legal, $invoice, Carbon::parse('2026-10-08')));
    }

    public function test_overdue_continues_after_one_month_when_interest_closure_is_disabled(): void
    {
        $invoice = $this->makeNumberedInvoice(7);
        $overdue = $this->invoiceRule(EventType::InvoiceOverdue);
        $this->configureInvoiceRule(EventType::InvoiceInterestClosure, false, ['offset_days' => 30]);
        $dispatcher = app(InvoiceNotificationDispatcher::class);

        $this->assertTrue($dispatcher->isEligible($overdue, $invoice, Carbon::parse('2026-10-08')));
    }

    public function test_overdue_stops_after_interest_closure_was_already_sent(): void
    {
        $invoice = $this->makeNumberedInvoice(7);
        $overdue = $this->invoiceRule(EventType::InvoiceOverdue);
        $legal = $this->configureInvoiceRule(EventType::InvoiceInterestClosure, false, ['offset_days' => 30]);
        $action = $legal->actions()->firstOrFail();
        AutomationJob::query()->create([
            'automation_rule_id' => $legal->id,
            'automation_rule_action_id' => $action->id,
            'event_type' => EventType::InvoiceInterestClosure,
            'cari_id' => $invoice->customer_cari_id,
            'subject_type' => $invoice->getMorphClass(),
            'subject_id' => $invoice->id,
            'occurrence_key' => EventType::InvoiceInterestClosure->value.':'.$invoice->getMorphClass().':'.$invoice->id.':once',
            'status' => JobStatus::Succeeded,
            'scheduled_at' => '2026-09-15 16:00:00',
            'executed_at' => '2026-09-15 16:00:00',
            'to_email' => 'musteri@example.com',
            'attempt_count' => 1,
        ]);
        $dispatcher = app(InvoiceNotificationDispatcher::class);

        $this->assertFalse($dispatcher->isEligible($overdue, $invoice, Carbon::parse('2026-09-20')));
    }

    public function test_paid_and_missing_email_and_missing_number_are_skipped(): void
    {
        $dispatcher = app(InvoiceNotificationDispatcher::class);
        $reminder = $this->invoiceRule(EventType::InvoiceDueApproaching);

        $paid = $this->makeNumberedInvoice(7);
        $paid->update(['is_paid' => true, 'paid_at' => now()]);
        $this->assertFalse($dispatcher->isEligible($reminder, $paid->fresh(['customerCari']), Carbon::parse('2026-09-02')));

        $noMail = $this->makeNumberedInvoice(7, null);
        $this->assertFalse($dispatcher->isEligible($reminder, $noMail, Carbon::parse('2026-09-02')));

        $disabled = $this->makeNumberedInvoice(7, 'kapali@example.com');
        $disabled->customerCari->update(['notifications_enabled' => false]);
        $this->assertFalse($dispatcher->isEligible($reminder, $disabled->fresh(['customerCari']), Carbon::parse('2026-09-02')));

        $noNumber = $this->makeNumberedInvoice(7);
        $noNumber->update(['our_invoice_number' => null]);
        $this->assertFalse($dispatcher->isEligible($reminder, $noNumber->fresh(['customerCari']), Carbon::parse('2026-09-02')));
    }

    public function test_dispatch_sends_reminder_and_respects_interval(): void
    {
        Mail::fake();
        $invoice = $this->makeNumberedInvoice(7);
        $this->configureInvoiceRule(EventType::InvoiceDueApproaching, true, ['interval_days' => 7, 'send_at' => '10:00']);
        $this->configureInvoiceRule(EventType::InvoiceOverdue, false);
        $this->configureInvoiceRule(EventType::InvoiceInterestClosure, false);

        $dispatcher = app(InvoiceNotificationDispatcher::class);
        $this->assertSame(1, $dispatcher->dispatch(Carbon::parse('2026-09-01 10:00:00', 'Europe/Istanbul')));
        $this->assertSame(1, AutomationJob::query()->where('status', JobStatus::Succeeded)->count());

        $this->assertSame(0, $dispatcher->dispatch(Carbon::parse('2026-09-02 10:00:00', 'Europe/Istanbul')));
        $this->assertSame(1, AutomationJob::query()->where('status', JobStatus::Succeeded)->count());

        $this->assertSame(1, $dispatcher->dispatch(Carbon::parse('2026-09-08 10:00:00', 'Europe/Istanbul')));
        $this->assertSame(2, AutomationJob::query()->where('status', JobStatus::Succeeded)->count());
        $this->assertTrue($invoice->exists);
    }

    public function test_dispatch_sends_interest_closure_once_automatically(): void
    {
        Mail::fake();
        $invoice = $this->makeNumberedInvoice(7);
        $this->configureInvoiceRule(EventType::InvoiceDueApproaching, false);
        $this->configureInvoiceRule(EventType::InvoiceOverdue, false);
        $this->configureInvoiceRule(EventType::InvoiceInterestClosure, true, [
            'offset_days' => 30,
            'interval_days' => 30,
            'send_at' => '16:00',
        ]);

        $dispatcher = app(InvoiceNotificationDispatcher::class);
        $this->assertSame(0, $dispatcher->dispatch(Carbon::parse('2026-10-07 16:00:00', 'Europe/Istanbul')));
        $this->assertSame(0, AutomationJob::query()->where('event_type', EventType::InvoiceInterestClosure)->count());

        $this->assertSame(1, $dispatcher->dispatch(Carbon::parse('2026-10-08 16:00:00', 'Europe/Istanbul')));
        $this->assertSame(1, AutomationJob::query()->where('event_type', EventType::InvoiceInterestClosure)->where('status', JobStatus::Succeeded)->count());

        $this->assertSame(0, $dispatcher->dispatch(Carbon::parse('2026-10-09 16:00:00', 'Europe/Istanbul')));
        $this->assertSame(0, $dispatcher->dispatch(Carbon::parse('2026-11-07 16:00:00', 'Europe/Istanbul')));
        $this->assertSame(1, AutomationJob::query()->where('event_type', EventType::InvoiceInterestClosure)->count());
        $this->assertTrue($invoice->exists);
    }

    public function test_dispatch_does_not_send_overdue_and_interest_closure_together(): void
    {
        Mail::fake();
        $invoice = $this->makeNumberedInvoice(7);
        $this->configureInvoiceRule(EventType::InvoiceDueApproaching, false);
        $this->configureInvoiceRule(EventType::InvoiceOverdue, true, [
            'offset_days' => 0,
            'interval_days' => 3,
            'send_at' => '14:30',
        ]);
        $this->configureInvoiceRule(EventType::InvoiceInterestClosure, true, [
            'offset_days' => 30,
            'interval_days' => 30,
            'send_at' => '16:00',
        ]);

        $dispatcher = app(InvoiceNotificationDispatcher::class);

        $this->assertSame(1, $dispatcher->dispatch(Carbon::parse('2026-09-09 16:00:00', 'Europe/Istanbul')));
        $this->assertTrue(AutomationJob::query()
            ->where('subject_id', $invoice->id)
            ->where('event_type', EventType::InvoiceOverdue)
            ->where('status', JobStatus::Succeeded)
            ->exists());
        $this->assertFalse(AutomationJob::query()
            ->where('subject_id', $invoice->id)
            ->where('event_type', EventType::InvoiceInterestClosure)
            ->exists());

        $this->assertSame(1, $dispatcher->dispatch(Carbon::parse('2026-10-08 16:00:00', 'Europe/Istanbul')));
        $this->assertSame(1, AutomationJob::query()->where('event_type', EventType::InvoiceInterestClosure)->where('status', JobStatus::Succeeded)->count());
        $this->assertSame(1, AutomationJob::query()->where('event_type', EventType::InvoiceOverdue)->where('status', JobStatus::Succeeded)->count());
    }

    public function test_dispatch_waits_until_configured_send_at(): void
    {
        Mail::fake();
        $this->makeNumberedInvoice(7);
        $this->configureInvoiceRule(EventType::InvoiceDueApproaching, true, ['interval_days' => 7, 'send_at' => '10:00']);
        $this->configureInvoiceRule(EventType::InvoiceOverdue, false);
        $this->configureInvoiceRule(EventType::InvoiceInterestClosure, false);

        $dispatcher = app(InvoiceNotificationDispatcher::class);
        $this->assertSame(0, $dispatcher->dispatch(Carbon::parse('2026-09-01 09:59:00', 'Europe/Istanbul')));
        $this->assertSame(0, AutomationJob::query()->where('status', JobStatus::Succeeded)->count());

        $this->assertSame(1, $dispatcher->dispatch(Carbon::parse('2026-09-01 10:00:00', 'Europe/Istanbul')));
        $this->assertSame(1, AutomationJob::query()->where('status', JobStatus::Succeeded)->count());
    }

    public function test_dispatch_skips_cari_with_email_when_notifications_disabled(): void
    {
        Mail::fake();
        $invoice = $this->makeNumberedInvoice(7);
        $invoice->customerCari->update(['notifications_enabled' => false]);
        $this->configureInvoiceRule(EventType::InvoiceDueApproaching, true, ['interval_days' => 7, 'send_at' => '10:00']);
        $this->configureInvoiceRule(EventType::InvoiceOverdue, false);
        $this->configureInvoiceRule(EventType::InvoiceInterestClosure, false);

        $this->assertSame(0, app(InvoiceNotificationDispatcher::class)->dispatch(Carbon::parse('2026-09-01 10:00:00', 'Europe/Istanbul')));
        $this->assertSame(0, AutomationJob::query()->where('status', JobStatus::Succeeded)->count());
        Mail::assertNothingSent();
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
        $reminder = $this->invoiceRule(EventType::InvoiceDueApproaching);
        $overdue = $this->invoiceRule(EventType::InvoiceOverdue);
        $templateId = $reminder->actions()->value('notification_template_id');

        $response = $this->actingAs($admin)->get(route('admin.notifications.edit'));
        $response->assertOk();
        $response->assertSee('Bildirim Yönetimi', false);
        $response->assertSee('Vade öncesi hatırlatma', false);
        $response->assertSee('Faiz Uygulaması ve Kapatma', false);
        $response->assertSee('Abonelik oluşturuldu', false);
        $response->assertSee('Aşamalar', false);
        $response->assertSee('aynı anda yalnızca biri gider', false);

        $this->actingAs($admin)->get(route('admin.notifications.templates.index'))
            ->assertOk()
            ->assertSee('{tutar}', false)
            ->assertSee('KDV dahil', false);

        $this->actingAs($admin)->patch(route('admin.notifications.rules.update', $reminder), [
            'name' => $reminder->name,
            'event_type' => EventType::InvoiceDueApproaching->value,
            'is_enabled' => '1',
            'timing_mode' => TimingMode::AtSendAt->value,
            'offset_days' => 2,
            'interval_days' => 5,
            'send_at' => '10:00',
            'dedupe_policy' => DedupePolicy::PerOccurrenceKey->value,
            'notification_template_id' => $templateId,
        ])->assertRedirect(route('admin.notifications.edit'));

        $this->actingAs($admin)->patch(route('admin.notifications.rules.update', $overdue), [
            'name' => $overdue->name,
            'event_type' => EventType::InvoiceOverdue->value,
            'is_enabled' => '0',
            'timing_mode' => TimingMode::AtSendAt->value,
            'offset_days' => 1,
            'interval_days' => 3,
            'send_at' => '14:30',
            'dedupe_policy' => DedupePolicy::PerOccurrenceKey->value,
            'notification_template_id' => $overdue->actions()->value('notification_template_id'),
        ])->assertRedirect(route('admin.notifications.edit'));

        $reminder->refresh();
        $this->assertTrue($reminder->is_enabled);
        $this->assertSame(2, $reminder->offsetDays());
        $this->assertSame(5, $reminder->intervalDays());
        $this->assertSame('10:00', $reminder->sendAtForInput());

        $overdue->refresh();
        $this->assertFalse($overdue->is_enabled);
        $this->assertSame('14:30', $overdue->sendAtForInput());
    }

    public function test_admin_sees_due_dated_invoice_in_test_mail_form(): void
    {
        $admin = $this->makeAdmin();
        $invoice = $this->makeNumberedInvoice(7);
        $template = $this->invoiceTemplate();

        $this->actingAs($admin)
            ->get(route('admin.notifications.templates.edit', $template))
            ->assertOk()
            ->assertSee('Test maili gönder', false)
            ->assertSee('Mail önizlemesi', false)
            ->assertSee('Yeni sekmede aç', false)
            ->assertSee($invoice->our_invoice_number, false)
            ->assertSee('vade '.$invoice->due_date->format('d.m.Y'), false)
            ->assertSee('{abonelikler}', false)
            ->assertSee('{abonelik_no}', false)
            ->assertSee('{tutar}', false)
            ->assertSee('Tutar (KDV dahil): {tutar}', false);
    }

    public function test_admin_can_send_test_mail_with_invoice_placeholders(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin();
        $invoice = $this->makeNumberedInvoice(7);
        $template = $this->invoiceTemplate();
        $template->update([
            'subject' => 'Hatirlatma {fatura_no}',
            'body' => 'Sayin {musteri} vade {vade_tarihi} tutar {tutar} ftn {ftn}',
        ]);

        $this->actingAs($admin)->post(route('admin.notifications.templates.test', $template), [
            'test_email' => 'gozlem@example.com',
            'sales_invoice_id' => $invoice->id,
        ])->assertRedirect(route('admin.notifications.templates.edit', $template))
            ->assertSessionHas('success');

        $replacements = app(InvoiceNotificationDispatcher::class)->replacements($invoice->fresh('customerCari'));
        $this->assertSame('Musteri Bildiri', $replacements['{musteri}']);
        $this->assertSame('ABC2026001', $replacements['{fatura_no}']);
        $this->assertSame('01.09.2026', $replacements['{fatura_tarihi}']);
        $this->assertSame($invoice->due_date->format('d.m.Y'), $replacements['{vade_tarihi}']);
        $this->assertSame('240,00 ₺', $replacements['{tutar}']);
        $this->assertSame('FTN000101', $replacements['{ftn}']);
        $this->assertSame('SOZ-NTF-001', $replacements['{abonelik_no}']);
        $this->assertSame('1', $replacements['{adet}']);
        $this->assertStringContainsString('SOZ-NTF-001', $replacements['{abonelikler}']);
        $this->assertStringContainsString('1 adet', $replacements['{abonelikler}']);
        $this->assertSame(
            'Hatirlatma ABC2026001',
            $template->fresh()->renderSubject($replacements)
        );
        $this->assertSame(
            'Sayin Musteri Bildiri vade '.$invoice->due_date->format('d.m.Y').' tutar 240,00 ₺ ftn FTN000101',
            $template->fresh()->renderBody($replacements)
        );
    }

    public function test_tutar_uses_recorded_invoice_gross_when_present(): void
    {
        $invoice = $this->makeNumberedInvoice(7);
        $invoice->update(['invoice_total_gross_tl' => 288.88]);

        $replacements = app(InvoiceNotificationDispatcher::class)->replacements($invoice->fresh());
        $this->assertSame('288,88 ₺', $replacements['{tutar}']);
    }

    public function test_invoice_placeholders_summarize_subscriptions_and_quantities(): void
    {
        $invoice = $this->makeNumberedInvoice(7);
        $replacements = app(InvoiceNotificationDispatcher::class)->replacements($invoice->fresh());

        $this->assertSame('SOZ-NTF-001', $replacements['{abonelik_no}']);
        $this->assertSame('1', $replacements['{adet}']);
        $this->assertNotSame('', $replacements['{abonelikler}']);
        $this->assertStringContainsString('• ', $replacements['{abonelikler}']);
        $this->assertStringContainsString('SOZ-NTF-001', $replacements['{abonelikler}']);
        $this->assertStringContainsString('1 adet', $replacements['{abonelikler}']);
        $this->assertStringNotContainsString('200,00 ₺', $replacements['{abonelikler}']);
    }

    public function test_non_admin_cannot_send_notification_test_mail(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $invoice = $this->makeNumberedInvoice(7);
        $template = $this->invoiceTemplate();

        $this->actingAs($user)->post(route('admin.notifications.templates.test', $template), [
            'test_email' => 'gozlem@example.com',
            'sales_invoice_id' => $invoice->id,
        ])->assertForbidden();
    }

    public function test_admin_can_preview_saved_mail_template_with_invoice_placeholders(): void
    {
        $admin = $this->makeAdmin();
        $invoice = $this->makeNumberedInvoice(7);
        $template = $this->invoiceTemplate();
        $template->update([
            'subject' => 'Hatirlatma {fatura_no}',
            'body' => "Sayin {musteri}\nVade {vade_tarihi}\nTutar {tutar}\nFTN {ftn}",
        ]);

        $response = $this->actingAs($admin)->get(route('admin.notifications.templates.preview', [
            'template' => $template,
            'sales_invoice_id' => $invoice->id,
        ]));

        $response->assertOk()
            ->assertSee('Mail önizlemesi', false)
            ->assertSee('Hatirlatma ABC2026001', false)
            ->assertSee('Sayin Musteri Bildiri', false)
            ->assertSee('Vade '.$invoice->due_date->format('d.m.Y'), false)
            ->assertSee('Tutar 240,00 ₺', false)
            ->assertSee('FTN FTN000101', false)
            ->assertSee('musteri@example.com', false)
            ->assertSee('ABC2026001', false);
    }

    public function test_admin_can_preview_unsaved_draft_template_via_post(): void
    {
        $admin = $this->makeAdmin();
        $invoice = $this->makeNumberedInvoice(7);
        $template = $this->invoiceTemplate();

        $this->actingAs($admin)->post(route('admin.notifications.templates.preview', $template), [
            'sales_invoice_id' => $invoice->id,
            'subject' => 'Taslak {fatura_no}',
            'body' => 'Merhaba {musteri}, tutar {tutar}',
        ])->assertOk()
            ->assertSee('Taslak ABC2026001', false)
            ->assertSee('Merhaba Musteri Bildiri, tutar 240,00 ₺', false)
            ->assertDontSee('Taslak {fatura_no}', false);

        $this->assertSame('Fatura hatırlatması: {fatura_no}', $template->fresh()->subject);
    }

    public function test_admin_can_fetch_mail_preview_as_json(): void
    {
        $admin = $this->makeAdmin();
        $invoice = $this->makeNumberedInvoice(7);
        $template = $this->invoiceTemplate();
        $template->update([
            'subject' => 'Hatirlatma {fatura_no}',
            'body' => 'Sayin {musteri}',
        ]);

        $this->actingAs($admin)->getJson(route('admin.notifications.templates.preview', [
            'template' => $template,
            'sales_invoice_id' => $invoice->id,
        ]))->assertOk()
            ->assertJsonPath('subject', 'Hatirlatma ABC2026001')
            ->assertJsonPath('body', 'Sayin Musteri Bildiri')
            ->assertJsonPath('to', 'musteri@example.com')
            ->assertJsonPath('invoice_number', 'ABC2026001');
    }

    public function test_non_admin_cannot_preview_notification_mail(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $invoice = $this->makeNumberedInvoice(7);
        $template = $this->invoiceTemplate();

        $this->actingAs($user)->get(route('admin.notifications.templates.preview', [
            'template' => $template,
            'sales_invoice_id' => $invoice->id,
        ]))->assertForbidden();
    }

    public function test_admin_can_open_queue_templates_and_cari_tabs(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.notifications.edit'))
            ->assertOk()
            ->assertSee('Kurallar', false)
            ->assertSee('Kuyruk', false)
            ->assertSee('Sürece göre sırala', false)
            ->assertSeeInOrder([
                'Abonelik süreci',
                'Abonelik oluşturuldu',
                'Sipariş süreci',
                'Sipariş oluştu',
                'Fatura süreci',
                'Fatura oluştu',
                'Vade öncesi hatırlatma',
                'Ödeme alındı',
            ]);

        $this->actingAs($admin)->get(route('admin.notifications.jobs.index'))
            ->assertOk()
            ->assertSee('Kuyrukta iş yok', false);

        $this->actingAs($admin)->get(route('admin.notifications.templates.index'))
            ->assertOk();

        $this->actingAs($admin)->get(route('admin.notifications.caris.index'))
            ->assertOk();
    }

    public function test_admin_can_move_rule_up_in_process_list(): void
    {
        $admin = $this->makeAdmin();
        $order = AutomationRule::query()->where('event_type', EventType::OrderCreated)->firstOrFail();
        $before = AutomationRule::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('event_type')
            ->map(fn ($type) => $type instanceof EventType ? $type->value : (string) $type)
            ->all();
        $index = array_search(EventType::OrderCreated->value, $before, true);
        $this->assertNotFalse($index);
        $this->assertGreaterThan(0, $index);

        $this->actingAs($admin)->post(route('admin.notifications.rules.move', $order), [
            'direction' => 'up',
        ])->assertRedirect(route('admin.notifications.edit'));

        $after = AutomationRule::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('event_type')
            ->map(fn ($type) => $type instanceof EventType ? $type->value : (string) $type)
            ->all();
        $this->assertSame($before[$index - 1], $after[$index]);
        $this->assertSame(EventType::OrderCreated->value, $after[$index - 1]);
    }

    public function test_admin_can_restore_process_order(): void
    {
        $admin = $this->makeAdmin();
        $created = AutomationRule::query()->where('event_type', EventType::SubscriptionCreated)->firstOrFail();
        $created->update(['sort_order' => 999]);

        $this->actingAs($admin)->post(route('admin.notifications.rules.reorder-process'))
            ->assertRedirect(route('admin.notifications.edit'))
            ->assertSessionHas('success');

        $first = AutomationRule::query()->orderBy('sort_order')->orderBy('id')->first();
        $this->assertSame(EventType::SubscriptionCreated, $first?->event_type);
    }

    public function test_entering_invoice_number_enqueues_invoice_issued_when_rule_enabled(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin();
        [$customerCari, , $pendingBilling] = $this->makePendingOrder(10);
        AutomationRule::query()
            ->where('event_type', EventType::InvoiceIssued)
            ->update(['is_enabled' => true]);

        $invoice = SalesInvoice::create([
            'customer_cari_id' => $customerCari->id,
            'total_amount_tl' => 200,
            'order_number' => 'FTN000201',
        ]);
        SalesInvoiceLine::create([
            'sales_invoice_id' => $invoice->id,
            'pending_billing_id' => $pendingBilling->id,
            'line_amount_tl' => 200,
        ]);

        $this->actingAs($admin)->patch(route('sales-invoices.update-invoice-details', $invoice), [
            'our_invoice_number' => 'ISS-1',
            'our_invoice_date' => '2026-09-01',
        ])->assertRedirect(route('sales-invoices.index'));

        $this->assertTrue(AutomationJob::query()
            ->where('event_type', EventType::InvoiceIssued)
            ->where('subject_id', $invoice->id)
            ->where('status', JobStatus::Succeeded)
            ->exists());
    }

    public function test_admin_can_save_immediate_timing_on_lifecycle_rule(): void
    {
        $admin = $this->makeAdmin();
        $rule = AutomationRule::query()->where('event_type', EventType::SubscriptionCreated)->firstOrFail();
        $templateId = $rule->actions()->value('notification_template_id');

        $this->actingAs($admin)
            ->get(route('admin.notifications.rules.edit', $rule))
            ->assertOk()
            ->assertSee('Gönderim zamanı', false)
            ->assertSee('Hemen', false);

        $this->actingAs($admin)->patch(route('admin.notifications.rules.update', $rule), [
            'name' => 'Abonelik oluşturuldu',
            'event_type' => EventType::SubscriptionCreated->value,
            'is_enabled' => '1',
            'timing_mode' => TimingMode::Immediate->value,
            'offset_days' => 0,
            'interval_days' => 1,
            'dedupe_policy' => DedupePolicy::Once->value,
            'notification_template_id' => $templateId,
        ])->assertRedirect(route('admin.notifications.edit'));

        $rule->refresh();
        $this->assertTrue($rule->is_enabled);
        $this->assertSame(TimingMode::Immediate, $rule->timingMode());
    }

    public function test_instant_rule_form_hides_offset_days_timing(): void
    {
        $admin = $this->makeAdmin();
        $rule = AutomationRule::query()->where('event_type', EventType::SubscriptionCreated)->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.notifications.rules.edit', $rule))
            ->assertOk()
            ->assertSee('Hemen', false)
            ->assertSee('Bugün şu saatte', false)
            ->assertDontSee('X gün sonra şu saatte', false);
    }

    public function test_window_rule_form_hides_immediate_timing(): void
    {
        $admin = $this->makeAdmin();
        $rule = AutomationRule::query()->where('event_type', EventType::InvoiceInterestClosure)->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.notifications.rules.edit', $rule))
            ->assertOk()
            ->assertDontSee('X gün sonra şu saatte', false)
            ->assertSee('Gün ofseti', false);
    }

    public function test_instant_rule_rejects_offset_days_timing(): void
    {
        $admin = $this->makeAdmin();
        $rule = AutomationRule::query()->where('event_type', EventType::SubscriptionCreated)->firstOrFail();
        $templateId = $rule->actions()->value('notification_template_id');

        $this->actingAs($admin)->from(route('admin.notifications.rules.edit', $rule))
            ->patch(route('admin.notifications.rules.update', $rule), [
                'name' => 'Abonelik oluşturuldu',
                'event_type' => EventType::SubscriptionCreated->value,
                'is_enabled' => '1',
                'timing_mode' => TimingMode::OffsetDays->value,
                'offset_days' => 3,
                'interval_days' => 1,
                'send_at' => '10:00',
                'dedupe_policy' => DedupePolicy::Once->value,
                'notification_template_id' => $templateId,
            ])->assertRedirect(route('admin.notifications.rules.edit', $rule))
            ->assertSessionHasErrors('timing_mode');
    }

    public function test_window_rule_rejects_immediate_timing(): void
    {
        $admin = $this->makeAdmin();
        $rule = AutomationRule::query()->where('event_type', EventType::InvoiceInterestClosure)->firstOrFail();
        $templateId = $rule->actions()->value('notification_template_id');

        $this->actingAs($admin)->from(route('admin.notifications.rules.edit', $rule))
            ->patch(route('admin.notifications.rules.update', $rule), [
                'name' => $rule->name,
                'event_type' => EventType::InvoiceInterestClosure->value,
                'is_enabled' => '0',
                'timing_mode' => TimingMode::Immediate->value,
                'offset_days' => 30,
                'interval_days' => 30,
                'dedupe_policy' => DedupePolicy::Once->value,
                'notification_template_id' => $templateId,
            ])->assertRedirect(route('admin.notifications.rules.edit', $rule))
            ->assertSessionHasErrors('timing_mode');
    }

    public function test_once_policy_does_not_require_or_reset_interval(): void
    {
        $admin = $this->makeAdmin();
        $rule = $this->configureInvoiceRule(EventType::InvoiceInterestClosure, false, [
            'offset_days' => 30,
            'interval_days' => 4,
            'send_at' => '16:00',
        ]);
        $templateId = $rule->actions()->value('notification_template_id');

        $this->actingAs($admin)->patch(route('admin.notifications.rules.update', $rule), [
            'name' => $rule->name,
            'event_type' => EventType::InvoiceInterestClosure->value,
            'is_enabled' => '0',
            'timing_mode' => TimingMode::AtSendAt->value,
            'offset_days' => 30,
            'send_at' => '16:00',
            'dedupe_policy' => DedupePolicy::Once->value,
            'notification_template_id' => $templateId,
        ])->assertRedirect(route('admin.notifications.edit'));

        $rule->refresh();
        $this->assertSame(DedupePolicy::Once, $rule->dedupe_policy);
        $this->assertSame(4, $rule->intervalDays());
    }

    public function test_change_events_reject_once_dedupe(): void
    {
        $admin = $this->makeAdmin();

        foreach ([
            EventType::SubscriptionPriceChanged,
            EventType::SubscriptionQuantityChanged,
            EventType::InvoicePaid,
        ] as $type) {
            $rule = AutomationRule::query()->where('event_type', $type)->firstOrFail();
            $templateId = $rule->actions()->value('notification_template_id');

            $this->actingAs($admin)->from(route('admin.notifications.rules.edit', $rule))
                ->patch(route('admin.notifications.rules.update', $rule), [
                    'name' => $rule->name,
                    'event_type' => $type->value,
                    'is_enabled' => '0',
                    'timing_mode' => TimingMode::Immediate->value,
                    'offset_days' => 0,
                    'interval_days' => 1,
                    'dedupe_policy' => DedupePolicy::Once->value,
                    'notification_template_id' => $templateId,
                ])->assertRedirect(route('admin.notifications.rules.edit', $rule))
                ->assertSessionHasErrors('dedupe_policy');
        }
    }

    public function test_one_shot_events_reject_per_occurrence_dedupe(): void
    {
        $admin = $this->makeAdmin();

        foreach ([
            EventType::SubscriptionCreated,
            EventType::SubscriptionAutoRenewDisabled,
            EventType::OrderCreated,
            EventType::InvoiceIssued,
        ] as $type) {
            $rule = AutomationRule::query()->where('event_type', $type)->firstOrFail();
            $templateId = $rule->actions()->value('notification_template_id');

            $this->actingAs($admin)->from(route('admin.notifications.rules.edit', $rule))
                ->patch(route('admin.notifications.rules.update', $rule), [
                    'name' => $rule->name,
                    'event_type' => $type->value,
                    'is_enabled' => '0',
                    'timing_mode' => TimingMode::Immediate->value,
                    'offset_days' => 0,
                    'interval_days' => 1,
                    'dedupe_policy' => DedupePolicy::PerOccurrenceKey->value,
                    'notification_template_id' => $templateId,
                ])->assertRedirect(route('admin.notifications.rules.edit', $rule))
                ->assertSessionHasErrors('dedupe_policy');
        }
    }

    public function test_window_rule_still_allows_once_or_per_occurrence(): void
    {
        $admin = $this->makeAdmin();
        $rule = AutomationRule::query()->where('event_type', EventType::InvoiceDueApproaching)->firstOrFail();
        $templateId = $rule->actions()->value('notification_template_id');

        $this->actingAs($admin)->patch(route('admin.notifications.rules.update', $rule), [
            'name' => $rule->name,
            'event_type' => EventType::InvoiceDueApproaching->value,
            'is_enabled' => '0',
            'timing_mode' => TimingMode::AtSendAt->value,
            'offset_days' => 7,
            'interval_days' => 7,
            'send_at' => '10:00',
            'dedupe_policy' => DedupePolicy::Once->value,
            'notification_template_id' => $templateId,
        ])->assertRedirect(route('admin.notifications.edit'));

        $this->assertSame(DedupePolicy::Once, $rule->fresh()->dedupe_policy);

        $this->actingAs($admin)->patch(route('admin.notifications.rules.update', $rule), [
            'name' => $rule->name,
            'event_type' => EventType::InvoiceDueApproaching->value,
            'is_enabled' => '0',
            'timing_mode' => TimingMode::AtSendAt->value,
            'offset_days' => 7,
            'interval_days' => 7,
            'send_at' => '10:00',
            'dedupe_policy' => DedupePolicy::PerOccurrenceKey->value,
            'notification_template_id' => $templateId,
        ])->assertRedirect(route('admin.notifications.edit'));

        $this->assertSame(DedupePolicy::PerOccurrenceKey, $rule->fresh()->dedupe_policy);
    }

    public function test_admin_can_cancel_pending_job(): void
    {
        $admin = $this->makeAdmin();
        Mail::fake();
        $invoice = $this->makeNumberedInvoice(7);
        $this->configureInvoiceRule(EventType::InvoiceDueApproaching, true, ['interval_days' => 7, 'send_at' => '10:00']);
        $this->configureInvoiceRule(EventType::InvoiceOverdue, false);
        $this->configureInvoiceRule(EventType::InvoiceInterestClosure, false);

        $this->assertSame(0, app(InvoiceNotificationDispatcher::class)->dispatch(Carbon::parse('2026-09-01 09:59:00', 'Europe/Istanbul')));
        $job = AutomationJob::query()->where('status', JobStatus::Pending)->first();
        $this->assertNotNull($job);

        $this->actingAs($admin)
            ->post(route('admin.notifications.jobs.cancel', $job))
            ->assertRedirect(route('admin.notifications.jobs.index'));

        $this->assertSame(JobStatus::Cancelled, $job->fresh()->status);
        $this->assertTrue($invoice->exists);
    }
}
