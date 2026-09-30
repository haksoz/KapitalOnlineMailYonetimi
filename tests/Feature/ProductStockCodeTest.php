<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStockCodeTest extends TestCase
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

    public function test_store_rejects_duplicate_stock_code(): void
    {
        $user = $this->makeUser();
        Product::create([
            'name' => 'Mevcut ürün',
            'stock_code' => 'CFQ-TEST-1',
            'currency' => Product::CURRENCY_USD,
        ]);

        $response = $this->actingAs($user)->from(route('products.create'))->post(route('products.store'), [
            'name' => 'Yeni ürün',
            'stock_code' => 'cfq-test-1',
            'currency' => 'USD',
        ]);

        $response->assertRedirect(route('products.create'));
        $response->assertSessionHasErrors('stock_code');
        $this->assertSame(1, Product::query()->count());
    }

    public function test_store_allows_blank_stock_code_on_multiple_products(): void
    {
        $user = $this->makeUser();
        Product::create([
            'name' => 'Kodsuz ürün',
            'stock_code' => null,
            'currency' => Product::CURRENCY_USD,
        ]);

        $response = $this->actingAs($user)->post(route('products.store'), [
            'name' => 'İkinci kodsuz ürün',
            'stock_code' => '   ',
            'currency' => 'TRY',
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertSame(2, Product::query()->whereNull('stock_code')->count());
    }

    public function test_update_can_keep_own_stock_code_but_not_take_another(): void
    {
        $user = $this->makeUser();
        $first = Product::create([
            'name' => 'Birinci',
            'stock_code' => 'KOD-1',
            'currency' => Product::CURRENCY_USD,
        ]);
        $second = Product::create([
            'name' => 'İkinci',
            'stock_code' => 'KOD-2',
            'currency' => Product::CURRENCY_USD,
        ]);

        $keep = $this->actingAs($user)->patch(route('products.update', $first), [
            'name' => 'Birinci güncel',
            'stock_code' => 'kod-1',
            'currency' => 'USD',
        ]);
        $keep->assertRedirect(route('products.index'));
        $this->assertSame('kod-1', $first->fresh()->stock_code);

        $take = $this->actingAs($user)->from(route('products.edit', $second))->patch(route('products.update', $second), [
            'name' => 'İkinci',
            'stock_code' => 'KOD-1',
            'currency' => 'USD',
        ]);
        $take->assertRedirect(route('products.edit', $second));
        $take->assertSessionHasErrors('stock_code');
        $this->assertSame('KOD-2', $second->fresh()->stock_code);
    }
};
