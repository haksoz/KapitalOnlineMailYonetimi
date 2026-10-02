<?php

namespace Tests\Feature;

use App\Models\Cari;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionIndexSortTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_index_sorts_by_column_headers(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->subscription('Zebra', 'SOZ-2', '2026-02-01', '2026-03-01', 5, 10, 15);
        $this->subscription('Alfa', 'SOZ-1', '2026-01-01', '2026-06-01', 2, 10, 12);

        $this->actingAs($user)->get(route('subscriptions.index'))
            ->assertOk()
            ->assertSee('sort=sozlesme', false)
            ->assertSeeInOrder(['SOZ-2', 'SOZ-1']);

        $this->actingAs($user)->get(route('subscriptions.index', ['sort' => 'sozlesme', 'direction' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder(['SOZ-1', 'SOZ-2']);

        $this->actingAs($user)->get(route('subscriptions.index', ['sort' => 'musteri', 'direction' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder(['SOZ-1', 'SOZ-2']);

        $this->actingAs($user)->get(route('subscriptions.index', ['sort' => 'musteri', 'direction' => 'desc']))
            ->assertOk()
            ->assertSeeInOrder(['SOZ-2', 'SOZ-1']);

        $this->actingAs($user)->get(route('subscriptions.index', ['sort' => 'adet', 'direction' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder(['SOZ-1', 'SOZ-2']);

        $this->actingAs($user)->get(route('subscriptions.index', ['sort' => 'kar', 'direction' => 'desc']))
            ->assertOk()
            ->assertSeeInOrder(['SOZ-2', 'SOZ-1']);

        $this->actingAs($user)->get(route('subscriptions.index', [
            'sort' => 'sozlesme',
            'direction' => 'asc',
            'durum' => 'active',
        ]))->assertOk()->assertSee('name="sort"', false)->assertSee('value="sozlesme"', false);
    }

    private function subscription(
        string $customer,
        string $number,
        string $start,
        string $end,
        int $quantity,
        float $buy,
        float $sell,
    ): void {
        $cari = Cari::create([
            'name' => $customer.' Ltd',
            'short_name' => $customer,
            'cari_type' => 'customer',
            'tax_number' => 'T-'.$number,
        ]);

        Subscription::create([
            'customer_cari_id' => $cari->id,
            'sozlesme_no' => $number,
            'baslangic_tarihi' => $start,
            'bitis_tarihi' => $end,
            'taahhut_tipi' => Subscription::TAAHHUT_MONTHLY_NO_COMMITMENT,
            'faturalama_periyodu' => Subscription::FATURALAMA_MONTHLY,
            'durum' => Subscription::DURUM_ACTIVE,
            'auto_renew' => true,
            'quantity' => $quantity,
            'usd_birim_alis' => $buy,
            'usd_birim_satis' => $sell,
            'vat_rate' => 20,
        ]);
    }
}
