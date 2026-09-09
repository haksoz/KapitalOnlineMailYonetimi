<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class MailSetting extends Model
{
    protected $table = 'mail_settings';

    protected $fillable = [
        'use_custom',
        'driver',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_address',
        'from_name',
    ];

    protected function casts(): array
    {
        return [
            'use_custom' => 'boolean',
        ];
    }

    public function setPasswordAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['password'] = null;
            return;
        }
        $this->attributes['password'] = Crypt::encryptString($value);
    }

    public function getPasswordAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function hasPassword(): bool
    {
        $raw = $this->getRawOriginal('password');

        return is_string($raw) && $raw !== '';
    }

    /**
     * Tek satır ayar (singleton). İlk kaydı döndürür.
     */
    public static function instance(): self
    {
        $row = static::query()->first();
        if ($row !== null) {
            return $row;
        }

        return static::query()->create([
            'use_custom' => false,
            'driver' => 'log',
        ]);
    }

    /**
     * Özel (DB) ayar kullanılsın mı?
     */
    public static function useCustom(): bool
    {
        $row = static::query()->first();

        return $row?->use_custom ?? false;
    }

    /**
     * Laravel mail config'ine merge edilecek mailer + from ayarı.
     *
     * @return array{mailers?: array<string, mixed>, default?: string, from?: array{address: string, name: string}}
     */
    public static function toMailConfig(): array
    {
        $row = static::query()->first();
        if ($row === null || ! $row->use_custom) {
            return [];
        }

        $mailerConfig = $row->driver === 'log'
            ? [
                'transport' => 'log',
                'channel' => config('mail.mailers.log.channel'),
            ]
            : [
                'transport' => 'smtp',
                'host' => $row->host ?: config('mail.mailers.smtp.host'),
                'port' => (int) ($row->port ?: config('mail.mailers.smtp.port')),
                'username' => $row->username,
                'password' => $row->password,
                'scheme' => static::smtpScheme($row->encryption, $row->port !== null ? (int) $row->port : null),
                'timeout' => null,
                'local_domain' => config('mail.mailers.smtp.local_domain'),
            ];

        $from = [
            'address' => $row->from_address ?: config('mail.from.address'),
            'name' => $row->from_name ?: config('mail.from.name'),
        ];

        return [
            'mailers' => ['db' => $mailerConfig],
            'default' => 'db',
            'from' => $from,
        ];
    }

    /**
     * Veritabanı ayarını runtime mail config'ine uygular ve çözülmüş mailer önbelleğini temizler.
     */
    public static function applyToRuntime(): void
    {
        if (! app()->bound('mail.config.base')) {
            app()->instance('mail.config.base', config('mail'));
        }

        $mail = app('mail.config.base');

        try {
            if (Schema::hasTable('mail_settings')) {
                $overrides = static::toMailConfig();
                if ($overrides !== []) {
                    $mail['mailers'] = array_merge($mail['mailers'] ?? [], $overrides['mailers'] ?? []);
                    $mail['default'] = $overrides['default'] ?? $mail['default'];
                    if (! empty($overrides['from'])) {
                        $mail['from'] = $overrides['from'];
                    }
                }
            }
        } catch (\Throwable) {
            // Tablo henüz yoksa .env/config ile devam.
        }

        config(['mail' => $mail]);

        if (! app()->bound('mail.manager')) {
            return;
        }

        $manager = app('mail.manager');
        foreach (array_unique(array_filter([
            $mail['default'] ?? null,
            'db',
            'smtp',
            'log',
        ])) as $mailerName) {
            $manager->purge($mailerName);
        }
    }

    /**
     * Laravel 12 SMTP `scheme` bekler; eski tls/ssl alanı buna çevrilir.
     */
    public static function smtpScheme(?string $encryption, ?int $port): ?string
    {
        return match ($encryption) {
            'ssl' => 'smtps',
            'tls' => 'smtp',
            default => $port === 465 ? 'smtps' : null,
        };
    }
}
