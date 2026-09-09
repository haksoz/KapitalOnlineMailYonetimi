<?php

namespace Tests\Feature;

use App\Models\MailSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailSettingTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function makeUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_USER,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_non_admin_cannot_view_mail_settings(): void
    {
        $response = $this->actingAs($this->makeUser())->get(route('admin.mail-settings.edit'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_mail_settings(): void
    {
        $response = $this->actingAs($this->makeAdmin())->get(route('admin.mail-settings.edit'));

        $response->assertOk();
        $response->assertSee('Mail Yönetimi', false);
        $response->assertSee('Aktif kaynak', false);
    }

    public function test_smtp_scheme_maps_encryption(): void
    {
        $this->assertSame('smtps', MailSetting::smtpScheme('ssl', 587));
        $this->assertSame('smtp', MailSetting::smtpScheme('tls', 587));
        $this->assertSame('smtps', MailSetting::smtpScheme(null, 465));
        $this->assertNull(MailSetting::smtpScheme(null, 587));
    }

    public function test_custom_smtp_without_host_is_rejected(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)
            ->from(route('admin.mail-settings.edit'))
            ->patch(route('admin.mail-settings.update'), [
                'use_custom' => '1',
                'driver' => 'smtp',
                'host' => '',
                'port' => 587,
                'from_address' => 'noreply@example.com',
                'from_name' => 'Test',
            ]);

        $response->assertRedirect(route('admin.mail-settings.edit'));
        $response->assertSessionHasErrors('host');
    }

    public function test_admin_can_save_custom_smtp_settings(): void
    {
        $admin = $this->makeAdmin();
        $setting = MailSetting::instance();

        $response = $this->actingAs($admin)->patch(route('admin.mail-settings.update'), [
            'use_custom' => '1',
            'driver' => 'smtp',
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'mailuser',
            'password' => 'secret-pass',
            'encryption' => 'tls',
            'from_address' => 'noreply@example.com',
            'from_name' => 'Kapital Mail',
        ]);

        $response->assertRedirect(route('admin.mail-settings.edit'));
        $response->assertSessionHas('success');

        $setting->refresh();
        $this->assertTrue($setting->use_custom);
        $this->assertSame('smtp', $setting->driver);
        $this->assertSame('smtp.example.com', $setting->host);
        $this->assertSame(587, (int) $setting->port);
        $this->assertSame('tls', $setting->encryption);
        $this->assertSame('noreply@example.com', $setting->from_address);
        $this->assertTrue($setting->hasPassword());
        $this->assertSame('secret-pass', $setting->password);

        $config = MailSetting::toMailConfig();
        $this->assertSame('db', $config['default']);
        $this->assertSame('smtp', $config['mailers']['db']['transport']);
        $this->assertSame('smtp', $config['mailers']['db']['scheme']);
        $this->assertSame('smtp.example.com', $config['mailers']['db']['host']);
    }

    public function test_empty_password_does_not_overwrite_existing(): void
    {
        $admin = $this->makeAdmin();
        $setting = MailSetting::instance();
        $setting->update([
            'use_custom' => true,
            'driver' => 'smtp',
            'host' => 'smtp.example.com',
            'port' => 587,
            'from_address' => 'noreply@example.com',
            'password' => 'keep-me',
        ]);

        $this->actingAs($admin)->patch(route('admin.mail-settings.update'), [
            'use_custom' => '1',
            'driver' => 'smtp',
            'host' => 'smtp.example.com',
            'port' => 587,
            'from_address' => 'noreply@example.com',
            'from_name' => 'Kapital Mail',
            'password' => '',
        ]);

        $this->assertSame('keep-me', $setting->fresh()->password);
    }

    public function test_apply_to_runtime_uses_database_mailer_when_custom(): void
    {
        MailSetting::instance()->update([
            'use_custom' => true,
            'driver' => 'log',
            'from_address' => 'custom@example.com',
            'from_name' => 'Ozel',
        ]);

        MailSetting::applyToRuntime();

        $this->assertSame('db', config('mail.default'));
        $this->assertSame('log', config('mail.mailers.db.transport'));
        $this->assertSame('custom@example.com', config('mail.from.address'));
    }

    public function test_send_test_mail_uses_runtime_settings(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin();

        MailSetting::instance()->update([
            'use_custom' => true,
            'driver' => 'log',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.mail-settings.test'), [
            'test_email' => 'check@example.com',
        ]);

        $response->assertRedirect(route('admin.mail-settings.edit'));
        $response->assertSessionHas('success');
    }
}
