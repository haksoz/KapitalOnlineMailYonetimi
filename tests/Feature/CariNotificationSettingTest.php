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
            ->assertSee('Vadeli çalışılıyor', false)
            ->assertSee('virgülle', false);
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
        $this->assertSame('2026-09-01', $cari->fresh()->dueDateFrom(\Carbon\Carbon::parse('2026-09-01'))?->format('Y-m-d'));
    }

    public function test_pesin_due_date_is_document_date_without_being_a_payment_term(): void
    {
        $cari = Cari::create([
            'name' => 'Pesin',
            'short_name' => 'Pesin',
            'email' => 'pesin@example.com',
            'notifications_enabled' => false,
            'cari_type' => 'customer',
            'tax_number' => '2222222222',
        ]);

        $this->assertFalse($cari->hasPaymentTerm());
        $this->assertSame('2026-09-22', $cari->dueDateFrom(\Carbon\Carbon::parse('2026-09-22'))?->format('Y-m-d'));
    }

    public function test_index_shows_inline_email_and_term_fields(): void
    {
        $user = $this->makeUser();
        Cari::create([
            'name' => 'Acme',
            'short_name' => 'Acme',
            'email' => 'acme@example.com',
            'notifications_enabled' => false,
            'cari_type' => 'customer',
            'tax_number' => '3333333333',
        ]);

        $this->actingAs($user)
            ->get(route('caris.index'))
            ->assertOk()
            ->assertSee('e-posta, e-posta', false)
            ->assertSee('Vadeli', false)
            ->assertSee('Peşin', false)
            ->assertSee('acme@example.com', false);
    }

    public function test_quick_update_saves_email_from_index(): void
    {
        $user = $this->makeUser();
        $cari = Cari::create([
            'name' => 'Acme',
            'short_name' => 'Acme',
            'cari_type' => 'customer',
            'tax_number' => '4444444444',
        ]);

        $this->actingAs($user)
            ->patchJson(route('caris.quick-update', $cari), [
                'email' => 'yeni@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('email', 'yeni@example.com')
            ->assertJsonPath('notifications_enabled', false);

        $this->assertSame('yeni@example.com', $cari->fresh()->email);
        $this->assertFalse($cari->fresh()->notifications_enabled);
    }

    public function test_quick_update_enables_notifications_when_email_present(): void
    {
        $user = $this->makeUser();
        $cari = Cari::create([
            'name' => 'Acme',
            'short_name' => 'Acme',
            'email' => 'acme@example.com',
            'notifications_enabled' => false,
            'cari_type' => 'customer',
            'tax_number' => '5555555555',
        ]);

        $this->actingAs($user)
            ->patchJson(route('caris.quick-update', $cari), [
                'notifications_enabled' => true,
            ])
            ->assertOk()
            ->assertJsonPath('notifications_enabled', true)
            ->assertJsonPath('can_receive_notifications', true);

        $this->assertTrue($cari->fresh()->canReceiveNotifications());
    }

    public function test_quick_update_clears_email_and_disables_notifications(): void
    {
        $user = $this->makeUser();
        $cari = Cari::create([
            'name' => 'Acme',
            'short_name' => 'Acme',
            'email' => 'acme@example.com',
            'notifications_enabled' => true,
            'cari_type' => 'customer',
            'tax_number' => '6666666666',
        ]);

        $this->actingAs($user)
            ->patchJson(route('caris.quick-update', $cari), [
                'email' => null,
            ])
            ->assertOk()
            ->assertJsonPath('email', null)
            ->assertJsonPath('notifications_enabled', false);

        $this->assertNull($cari->fresh()->email);
        $this->assertFalse($cari->fresh()->notifications_enabled);
    }

    public function test_quick_update_sets_payment_term_and_pesin(): void
    {
        $user = $this->makeUser();
        $cari = Cari::create([
            'name' => 'Acme',
            'short_name' => 'Acme',
            'cari_type' => 'customer',
            'tax_number' => '7777777777',
        ]);

        $this->actingAs($user)
            ->patchJson(route('caris.quick-update', $cari), [
                'is_vadeli' => true,
                'odeme_vadesi_gun' => 14,
            ])
            ->assertOk()
            ->assertJsonPath('has_payment_term', true)
            ->assertJsonPath('odeme_vadesi_gun', 14);

        $this->assertSame(14, $cari->fresh()->odeme_vadesi_gun);

        $this->actingAs($user)
            ->patchJson(route('caris.quick-update', $cari), [
                'is_vadeli' => false,
            ])
            ->assertOk()
            ->assertJsonPath('has_payment_term', false)
            ->assertJsonPath('odeme_vadesi_gun', null);

        $this->assertNull($cari->fresh()->odeme_vadesi_gun);
    }

    public function test_quick_update_rejects_invalid_email(): void
    {
        $user = $this->makeUser();
        $cari = Cari::create([
            'name' => 'Acme',
            'short_name' => 'Acme',
            'cari_type' => 'customer',
            'tax_number' => '8888888888',
        ]);

        $this->actingAs($user)
            ->patchJson(route('caris.quick-update', $cari), [
                'email' => 'not-an-email',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_can_save_multiple_emails_separated_by_comma(): void
    {
        $user = $this->makeUser();
        $cari = Cari::create([
            'name' => 'Acme',
            'short_name' => 'Acme',
            'cari_type' => 'customer',
            'tax_number' => '9999999991',
        ]);

        $this->actingAs($user)->patch(route('caris.update', $cari), [
            'name' => 'Acme',
            'short_name' => 'Acme',
            'email' => 'muhasebe@example.com,  yonetim@example.com,muhasebe@example.com',
            'notifications_enabled' => '1',
            'country_code' => 'TR',
            'tax_number' => '9999999991',
            'cari_type' => 'customer',
        ])->assertRedirect(route('caris.index'));

        $cari->refresh();
        $this->assertSame('muhasebe@example.com, yonetim@example.com', $cari->email);
        $this->assertSame(['muhasebe@example.com', 'yonetim@example.com'], $cari->notificationEmails());
        $this->assertTrue($cari->canReceiveNotifications());
    }

    public function test_quick_update_saves_multiple_emails(): void
    {
        $user = $this->makeUser();
        $cari = Cari::create([
            'name' => 'Acme',
            'short_name' => 'Acme',
            'cari_type' => 'customer',
            'tax_number' => '9999999992',
        ]);

        $this->actingAs($user)
            ->patchJson(route('caris.quick-update', $cari), [
                'email' => 'a@example.com, b@example.com',
                'notifications_enabled' => true,
            ])
            ->assertOk()
            ->assertJsonPath('email', 'a@example.com, b@example.com')
            ->assertJsonPath('notifications_enabled', true);

        $this->assertSame(['a@example.com', 'b@example.com'], $cari->fresh()->notificationEmails());
    }

    public function test_rejects_invalid_address_inside_email_list(): void
    {
        $user = $this->makeUser();
        $cari = Cari::create([
            'name' => 'Acme',
            'short_name' => 'Acme',
            'cari_type' => 'customer',
            'tax_number' => '9999999993',
        ]);

        $this->actingAs($user)
            ->from(route('caris.edit', $cari))
            ->patch(route('caris.update', $cari), [
                'name' => 'Acme',
                'short_name' => 'Acme',
                'email' => 'gecerli@example.com, degil',
                'notifications_enabled' => '1',
                'country_code' => 'TR',
                'tax_number' => '9999999993',
                'cari_type' => 'customer',
            ])
            ->assertRedirect(route('caris.edit', $cari))
            ->assertSessionHasErrors('email');
    }
}
