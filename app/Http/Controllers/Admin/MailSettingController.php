<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MailSettingController extends Controller
{
    public function edit(): View
    {
        $mailSetting = MailSetting::instance();
        MailSetting::applyToRuntime();
        $baseMail = app()->bound('mail.config.base') ? app('mail.config.base') : config('mail');

        return view('admin.mail-settings.edit', [
            'mailSetting' => $mailSetting,
            'envMailer' => $baseMail['default'] ?? 'log',
            'envFromAddress' => $baseMail['from']['address'] ?? null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $smtpRequired = $request->boolean('use_custom') && $request->input('driver') === 'smtp';

        $validated = $request->validate([
            'use_custom' => ['required', 'boolean'],
            'driver' => ['required', 'in:smtp,log'],
            'host' => [Rule::requiredIf($smtpRequired), 'nullable', 'string', 'max:255'],
            'port' => [Rule::requiredIf($smtpRequired), 'nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'encryption' => ['nullable', 'in:tls,ssl'],
            'from_address' => [Rule::requiredIf($smtpRequired), 'nullable', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
        ], [
            'host.required' => 'Özel SMTP kullanırken sunucu (host) zorunludur.',
            'port.required' => 'Özel SMTP kullanırken port zorunludur.',
            'from_address.required' => 'Özel SMTP kullanırken gönderen e-posta zorunludur.',
            'from_address.email' => 'Gönderen adres geçerli bir e-posta olmalıdır.',
        ]);

        $mailSetting = MailSetting::instance();

        $data = [
            'use_custom' => (bool) $validated['use_custom'],
            'driver' => $validated['driver'],
            'host' => $validated['host'] ?: null,
            'port' => $validated['port'] ? (int) $validated['port'] : null,
            'username' => $validated['username'] ?: null,
            'encryption' => $validated['encryption'] ?: null,
            'from_address' => $validated['from_address'] ?: null,
            'from_name' => $validated['from_name'] ?: null,
        ];

        if ($request->filled('password')) {
            $data['password'] = $validated['password'];
        }

        $mailSetting->update($data);
        MailSetting::applyToRuntime();

        return redirect()
            ->route('admin.mail-settings.edit')
            ->with('success', 'Mail ayarları kaydedildi. Yeni gönderimler bu ayarlarla gidecek.');
    }

    public function sendTest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'test_email' => ['required', 'email', 'max:255'],
        ]);

        $to = $validated['test_email'];
        MailSetting::applyToRuntime();

        $setting = MailSetting::query()->first();
        if ($setting?->use_custom && $setting->driver === 'smtp' && blank($setting->host)) {
            return redirect()
                ->route('admin.mail-settings.edit')
                ->with('error', 'Özel SMTP seçili ama sunucu (host) boş. Önce ayarları kaydedin.');
        }

        try {
            Mail::raw('Bu bir test e-postasıdır. Mail Yönetimi uygulamasından otomatik gönderim testi.', function ($message) use ($to): void {
                $message->to($to)->subject('Mail ayar testi');
            });
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.mail-settings.edit')
                ->with('error', 'Test e-postası gönderilemedi: ' . $e->getMessage());
        }

        $via = config('mail.default');

        return redirect()
            ->route('admin.mail-settings.edit')
            ->with('success', 'Test e-postası gönderildi: ' . $to . ' (mailer: ' . $via . ')');
    }
}
