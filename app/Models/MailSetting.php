<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

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
     * @return array{mailers: array, from: array}
     */
    public static function toMailConfig(): array
    {
        $row = static::query()->first();
        if ($row === null || ! $row->use_custom) {
            return [];
        }

        $mailerConfig = [
            'transport' => $row->driver === 'log' ? 'log' : 'smtp',
        ];

        if ($row->driver === 'smtp') {
            $mailerConfig['host'] = $row->host ?? config('mail.mailers.smtp.host');
            $mailerConfig['port'] = (int) ($row->port ?? config('mail.mailers.smtp.port'));
            $mailerConfig['username'] = $row->username;
            $mailerConfig['password'] = $row->password;
            $mailerConfig['encryption'] = $row->encryption ?: null;
            $mailerConfig['timeout'] = null;
        }

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
}
