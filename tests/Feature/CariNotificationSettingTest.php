<?php

namespace Tests\Feature;

use App\Models\Cari;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CariNotificationSettingTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_USER,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_edit_page_shows_notification_toggle(): void
    {
        $user = $this->makeUser();
        $cari = Cari::create([
            'name' => 'Acme',
            'short_name' => 'Acme',
            'email' => 'acme@example.com',
            'notifications_enabled' => false,
            'cari_type' => 'customer',
            'tax_number' => '1111111111',
        ]);

        $this->actingAs($user)
            ->get(route('caris.edit', $cari))
            ->assertOk()
            ->assertSee('Bildirim açık', false)
            ->assertSee('Vadeli çalışılıyor', false);
    }

    public function test_can_enable_notifications_on_update(): void
    {
        $user = $this->makeUser();
        $cari = Cari::create([
            'name' => 'Acme',
            'short_name' => 'Acme',
            'email' => 'acme@example.com',
            'notifications_enabled' => false,
            'cari_type' => 'customer',
            'tax_number' => '1111111111',
        ]);

        $this->actingAs($user)->patch(route('caris.update', $cari), [
            'name' => 'Acme',
            'short_name' => 'Acme',
            'email' => 'acme@example.com',
            'notifications_enabled' => '1',
            'country_code' => 'TR',
            'tax_number' => '1111111111',
            'cari_type' => 'customer',
        ])->assertRedirect(route('caris.index'));

        $this->assertTrue($cari->fresh()->notifications_enabled);
        $this->assertTrue($cari->fresh()->canReceiveNotifications());
    }

    public function test_can_disable_notifications_on_update(): void
    {
        $user = $this->makeUser();
        $cari = Cari::create([
            'name' => 'Acme',
            'short_name' => 'Acme',
            'email' => 'acme@example.com',
            'notifications_enabled' => true,
            'cari_type' => 'customer',
            'tax_number' => '1111111111',
        ]);

        $this->actingAs($user)->patch(route('caris.update', $cari), [
            'name' => 'Acme',
            'short_name' => 'Acme',
            'email' => 'acme@example.com',
            'notifications_enabled' => '0',
            'country_code' => 'TR',
            'tax_number' => '1111111111',
            'cari_type' => 'customer',
        ])->assertRedirect(route('caris.index'));

        $this->assertFalse($cari->fresh()->notifications_enabled);
        $this->assertFalse($cari->fresh()->canReceiveNotifications());
    }

    public function test_cannot_enable_notifications_without_email(): void
    {
        $user = $this->makeUser();
        $cari = Cari::create([
            'name' => 'Acme',
            'short_name' => 'Acme',
            'email' => null,
            'notifications_enabled' => false,
            'cari_type' => 'customer',
            'tax_number' => '1111111111',
        ]);

        $this->actingAs($user)
            ->get(route('caris.edit', $cari))
            ->assertOk()
            ->assertSee('E-posta girilmeden bildirim açılamaz', false)
            ->assertSee('disabled', false);

        $this->actingAs($user)->patch(route('caris.update', $cari), [
            'name' => 'Acme',
            'short_name' => 'Acme',
            'email' => '',
            'notifications_enabled' => '1',
            'country_code' => 'TR',
            'tax_number' => '1111111111',
            'cari_type' => 'customer',
        ])->assertRedirect(route('caris.index'));

        $this->assertFalse($cari->fresh()->notifications_enabled);
    }

    public function test_clearing_email_disables_notifications(): void
    {
        $user = $this->makeUser();
        $cari = Cari::create([
            'name' => 'Acme',
            'short_name' => 'Acme',
            'email' => 'acme@example.com',
            'notifications_enabled' => true,
            'cari_type' => 'customer',
            'tax_number' => '1111111111',
        ]);

        $this->actingAs($user)->patch(route('caris.update', $cari), [
            'name' => 'Acme',
            'short_name' => 'Acme',
            'email' => '',
            'notifications_enabled' => '1',
            'country_code' => 'TR',
            'tax_number' => '1111111111',
            'cari_type' => 'customer',
        ])->assertRedirect(route('caris.index'));

        $this->assertNull($cari->fresh()->email);
        $this->assertFalse($cari->fresh()->notifications_enabled);
    }

    public function test_can_save_payment_term_on_cari(): void
    {
        $user = $this->makeUser();
        $cari = Cari::create([
            'name' => 'Acme',
            'short_name' => 'Acme',
            'email' => 'acme@example.com',
            'notifications_enabled' => false,
            'cari_type' => 'customer',
            'tax_number' => '1111111111',
        ]);

        $this->actingAs($user)->patch(route('caris.update', $cari), [
            'name' => 'Acme',
            'short_name' => 'Acme',
            'email' => 'acme@example.com',
            'notifications_enabled' => '0',
            'country_code' => 'TR',
            'tax_number' => '1111111111',
            'cari_type' => 'customer',
            'is_vadeli' => '1',
            'odeme_vadesi_gun' => '14',
        ])->assertRedirect(route('caris.index'));

        $cari->refresh();
        $this->assertTrue($cari->hasPaymentTerm());
        $this->assertSame(14, $cari->odeme_vadesi_gun);
        $this->assertSame('2026-09-15', $cari->dueDateFrom(\Carbon\Carbon::parse('2026-09-01'))?->format('Y-m-d'));
    }

    public function test_can_clear_payment_term_on_cari(): void
    {
        $user = $this->makeUser();
        $cari = Cari::create([
            'name' => 'Acme',
            'short_name' => 'Acme',
            'email' => 'acme@example.com',
            'notifications_enabled' => false,
            'cari_type' => 'customer',
            'tax_number' => '1111111111',
            'odeme_vadesi_gun' => 7,
        ]);

        $this->actingAs($user)->patch(route('caris.update', $cari), [
            'name' => 'Acme',
            'short_name' => 'Acme',
            'email' => 'acme@example.com',
            'notifications_enabled' => '0',
            'country_code' => 'TR',
            'tax_number' => '1111111111',
            'cari_type' => 'customer',
            'is_vadeli' => '0',
        ])->assertRedirect(route('caris.index'));

        $this->assertNull($cari->fresh()->odeme_vadesi_gun);
        $this->assertFalse($cari->fresh()->hasPaymentTerm());
    }
}
