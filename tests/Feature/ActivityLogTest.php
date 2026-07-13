<?php

namespace Tests\Feature;

use App\Models\Cari;
use App\Models\Product;
use App\Models\ServiceProvider;
use App\Models\Subscription;
use App\Models\SubscriptionQuantityHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_price_update_creates_history_rows(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $product = Product::create([
            'name' => 'Test Ürün',
            'alis_usd_monthly_commitment' => 10,
            'satis_usd_monthly_commitment' => 20,
        ]);

        $this->actingAs($admin)
            ->patch(route('products.update', $product), [
                'name' => 'Test Ürün Güncel',
                'alis_usd_monthly_commitment' => 12,
                'satis_usd_monthly_commitment' => 22,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('product_price_history', [
            'product_id' => $product->id,
            'field_name' => 'alis_usd_monthly_commitment',
            'old_value' => '10.0000',
            'new_value' => '12.0000',
            'changed_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('product_price_history', [
            'product_id' => $product->id,
            'field_name' => 'satis_usd_monthly_commitment',
            'old_value' => '20.0000',
            'new_value' => '22.0000',
            'changed_by' => $admin->id,
        ]);
    }

    public function test_subscription_price_update_creates_history_rows(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $subscription = $this->createSubscription([
            'usd_birim_alis' => 10,
            'usd_birim_satis' => 20,
        ]);

        $this->actingAs($admin)
            ->patch(route('subscriptions.update', $subscription), [
                'customer_cari_id' => $subscription->customer_cari_id,
                'sozlesme_no' => $subscription->sozlesme_no,
                'baslangic_tarihi' => $subscription->baslangic_tarihi->format('Y-m-d'),
                'bitis_tarihi' => $subscription->bitis_tarihi->format('Y-m-d'),
                'taahhut_tipi' => $subscription->taahhut_tipi,
                'faturalama_periyodu' => $subscription->faturalama_periyodu,
                'durum' => $subscription->durum,
                'usd_birim_alis' => 11,
                'usd_birim_satis' => 21,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('subscription_price_history', [
            'subscription_id' => $subscription->id,
            'field_name' => 'usd_birim_alis',
            'old_value' => '10.0000',
            'new_value' => '11.0000',
            'changed_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('subscription_price_history', [
            'subscription_id' => $subscription->id,
            'field_name' => 'usd_birim_satis',
            'old_value' => '20.0000',
            'new_value' => '21.0000',
            'changed_by' => $admin->id,
        ]);
    }

    public function test_subscription_quantity_update_creates_history_row(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $subscription = $this->createSubscription(['quantity' => 1]);

        $this->actingAs($admin)
            ->post(route('subscriptions.update-quantity', $subscription), [
                'new_quantity' => 5,
                'effective_date' => '2026-07-15',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('subscription_quantity_history', [
            'subscription_id' => $subscription->id,
            'previous_quantity' => 1,
            'new_quantity' => 5,
            'changed_by' => $admin->id,
        ]);

        $this->assertEquals('2026-07-15', SubscriptionQuantityHistory::first()->effective_date->toDateString());

        $this->assertDatabaseHas('subscription_quantity_changes', [
            'subscription_id' => $subscription->id,
            'new_quantity' => 5,
        ]);
    }

    public function test_admin_can_view_activity_logs_page(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $product = Product::create([
            'name' => 'Log Ürün',
            'alis_usd_monthly_commitment' => 10,
        ]);

        $product->update(['alis_usd_monthly_commitment' => 15]);

        $this->actingAs($admin)
            ->get(route('admin.activity-logs.index'))
            ->assertOk()
            ->assertSee('Ürün Fiyat Değişimleri')
            ->assertSee('Log Ürün');
    }

    private function createSubscription(array $overrides = []): Subscription
    {
        $cari = Cari::create([
            'name' => 'Müşteri',
            'short_name' => 'Müşteri',
            'cari_type' => 'customer',
            'tax_number' => '1234567890',
        ]);

        $serviceProvider = ServiceProvider::create([
            'name' => 'Sağlayıcı',
            'code' => 'SAG-1',
        ]);

        return Subscription::create(array_merge([
            'customer_cari_id' => $cari->id,
            'provider_cari_id' => $cari->id,
            'service_provider_id' => $serviceProvider->id,
            'sozlesme_no' => 'SOZ-' . random_int(1000, 9999),
            'baslangic_tarihi' => '2026-01-01',
            'bitis_tarihi' => '2027-01-01',
            'taahhut_tipi' => Subscription::TAAHHUT_MONTHLY_COMMITMENT,
            'faturalama_periyodu' => Subscription::FATURALAMA_MONTHLY,
            'durum' => Subscription::DURUM_ACTIVE,
            'auto_renew' => true,
            'quantity' => 1,
            'vat_rate' => 20,
        ], $overrides));
    }
}
