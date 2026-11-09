<?php

namespace Tests\Feature;

use App\Models\Cari;
use App\Models\Product;
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

    public function test_search_matches_partial_contract_customer_and_product_names(): void
    {
        $this->actingAs(User::factory()->create(['email_verified_at' => now()]));
        $this->subscription('Alfa', '2503087060', '2026-01-01', '2026-12-01', 1, 10, 15);
        $this->subscription('Zebra', '2210061688', '2026-01-01', '2026-12-01', 1, 10, 15);
        $subscription = Subscription::where('sozlesme_no', '2503087060')->firstOrFail();
        $subscription->update(['product_id' => Product::create(['name' => 'Exchange Online'])->id]);

        foreach (['  308706  ', 'Alfa', 'Alfa Ltd', 'Exchange'] as $search) {
            $this->get(route('subscriptions.index', ['search' => $search]))
                ->assertOk()
                ->assertViewHas('subscriptions', fn ($rows) => $rows->pluck('id')->all() === [$subscription->id]);
        }

        $this->get(route('subscriptions.index', ['search' => 'Bulunamayan']))
            ->assertOk()->assertViewHas('subscriptions', fn ($rows) => $rows->total() === 0);
        $this->get(route('subscriptions.index', ['search' => '   ']))
            ->assertOk()->assertViewHas('subscriptions', fn ($rows) => $rows->total() === 2);
    }

    public function test_search_combines_with_customer_status_and_sort_filters(): void
    {
        $this->actingAs(User::factory()->create(['email_verified_at' => now()]));
        $this->subscription('Alfa', 'SOZ-1', '2026-01-01', '2026-12-01', 1, 10, 15);
        $this->subscription('Zebra', 'SOZ-2', '2026-01-01', '2026-12-01', 1, 10, 15);
        $first = Subscription::where('sozlesme_no', 'SOZ-1')->firstOrFail();
        $second = Subscription::where('sozlesme_no', 'SOZ-2')->firstOrFail();
        $second->update(['durum' => Subscription::DURUM_CANCELLED]);

        $this->get(route('subscriptions.index', ['search' => 'Zebra', 'durum' => 'active']))
            ->assertOk()->assertViewHas('subscriptions', fn ($rows) => $rows->total() === 0);
        $this->get(route('subscriptions.index', ['search' => 'Zebra', 'customer_cari_id' => $first->customer_cari_id]))
            ->assertOk()->assertViewHas('subscriptions', fn ($rows) => $rows->total() === 0);

        $this->get(route('subscriptions.index', ['search' => 'SOZ-', 'sort' => 'sozlesme', 'direction' => 'desc']))
            ->assertOk()
            ->assertViewHas('subscriptions', fn ($rows) => $rows->pluck('id')->all() === [$second->id, $first->id])
            ->assertSee('search=SOZ-', false)->assertSee('Aramayı temizle');
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
