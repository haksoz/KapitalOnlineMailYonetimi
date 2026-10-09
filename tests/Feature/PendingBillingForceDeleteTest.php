<?php

namespace Tests\Feature;

use App\Models\Cari;
use App\Models\PendingBilling;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendingBillingForceDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleted_order_can_be_permanently_removed(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $cari = Cari::create([
            'name' => 'Kalici Sil Cari',
            'short_name' => 'Kalici Sil',
            'cari_type' => 'customer',
            'tax_number' => '9990000001',
        ]);

        $subscription = Subscription::create([
            'customer_cari_id' => $cari->id,
            'provider_cari_id' => $cari->id,
            'sozlesme_no' => 'SOZ-FORCE-001',
            'baslangic_tarihi' => '2026-09-01',
            'bitis_tarihi' => '2027-09-01',
            'taahhut_tipi' => Subscription::TAAHHUT_MONTHLY_COMMITMENT,
            'faturalama_periyodu' => Subscription::FATURALAMA_MONTHLY,
            'durum' => Subscription::DURUM_ACTIVE,
            'quantity' => 1,
            'usd_birim_alis' => 10,
            'usd_birim_satis' => 12,
            'vat_rate' => 20,
        ]);

        $pending = PendingBilling::create([
            'subscription_id' => $subscription->id,
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
            'status' => PendingBilling::STATUS_PENDING,
            'is_deleted' => true,
        ]);

        $response = $this->actingAs($user)->delete(route('pending-billings.force-destroy', $pending->id));

        $response->assertRedirect(route('pending-billings.index', ['status' => 'deleted']));
        $this->assertDatabaseMissing('pending_billings', ['id' => $pending->id]);
    }

    public function test_active_order_cannot_be_permanently_removed_from_recycle_bin(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $cari = Cari::create([
            'name' => 'Aktif Siparis Cari',
            'short_name' => 'Aktif Siparis',
            'cari_type' => 'customer',
            'tax_number' => '9990000002',
        ]);

        $subscription = Subscription::create([
            'customer_cari_id' => $cari->id,
            'provider_cari_id' => $cari->id,
            'sozlesme_no' => 'SOZ-FORCE-002',
            'baslangic_tarihi' => '2026-09-01',
            'bitis_tarihi' => '2027-09-01',
            'taahhut_tipi' => Subscription::TAAHHUT_MONTHLY_COMMITMENT,
            'faturalama_periyodu' => Subscription::FATURALAMA_MONTHLY,
            'durum' => Subscription::DURUM_ACTIVE,
            'quantity' => 1,
            'usd_birim_alis' => 10,
            'usd_birim_satis' => 12,
            'vat_rate' => 20,
        ]);

        $pending = PendingBilling::create([
            'subscription_id' => $subscription->id,
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
            'status' => PendingBilling::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->from(route('pending-billings.index', ['status' => 'pending']))
            ->delete(route('pending-billings.force-destroy', $pending->id));

        $response->assertRedirect(route('pending-billings.index', ['status' => 'deleted']));
        $this->assertDatabaseHas('pending_billings', [
            'id' => $pending->id,
            'is_deleted' => false,
        ]);
    }
}
