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

class AdminCariLedgerReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_cari_ledger_report_with_group_totals(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

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
            'usd_birim_satis' => 20,
            'vat_rate' => 20,
        ]);

        $pendingBilling = PendingBilling::create([
            'subscription_id' => $subscription->id,
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-28',
            'status' => PendingBilling::STATUS_INVOICED,
            'expected_alis_tl' => 100,
            'actual_alis_tl' => 110,
            'expected_satis_tl' => 200,
            'actual_satis_tl' => 180,
            'supplier_invoice_number' => 'ALS-001',
            'supplier_invoice_date' => '2026-02-15',
        ]);

        $salesInvoice = SalesInvoice::create([
            'customer_cari_id' => $customerCari->id,
            'our_invoice_number' => 'SAT-001',
            'our_invoice_date' => '2026-02-20',
            'total_amount_tl' => 180,
        ]);

        SalesInvoiceLine::create([
            'sales_invoice_id' => $salesInvoice->id,
            'pending_billing_id' => $pendingBilling->id,
            'line_amount_tl' => 180,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.cari-ledger'));

        $response->assertOk();
        $response->assertSee('Cari Hesap Dökümü');
        $response->assertSee('Finansal Toplamlar');
        $response->assertDontSee('Musteri A Dip Toplam');
        $response->assertSee('ALS-001');
        $response->assertSee('SAT-001');
        $response->assertSee('10,00');
        $response->assertSee('-20,00');
    }

    public function test_admin_can_export_cari_ledger_report_as_excel(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.cari-ledger.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8');
        $response->assertSee('Cari', false);
        $response->assertSee('Genel Dip Toplam');
    }
}
