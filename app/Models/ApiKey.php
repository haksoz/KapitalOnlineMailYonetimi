<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    public const PERMISSION_READ  = 'read';
    public const PERMISSION_WRITE = 'write';
    public const PERMISSION_ADMIN = 'admin';

    protected $fillable = [
        'api_integration_id',
        'name',
        'token_hash',
        'token_prefix',
        'permission_level',
        'description',
        'allowed_ips',
        'rate_limit_per_minute',
        'is_active',
        'expires_at',
        'last_used_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(ApiIntegration::class, 'api_integration_id');
    }

    public static function generate(int $integrationId, string $name, string $permissionLevel = self::PERMISSION_READ, array $options = []): array
    {
        $plainToken = 'kms_' . Str::random(40);
        $prefix     = substr($plainToken, 0, 8);

        $key = static::create([
            'api_integration_id'  => $integrationId,
            'name'                => $name,
            'token_hash'          => hash('sha256', $plainToken),
            'token_prefix'        => $prefix,
            'permission_level'    => $permissionLevel,
            'description'         => $options['description'] ?? null,
            'allowed_ips'         => $options['allowed_ips'] ?? null,
            'rate_limit_per_minute' => $options['rate_limit_per_minute'] ?? 60,
            'is_active'           => true,
            'expires_at'          => $options['expires_at'] ?? null,
        ]);

        return ['key' => $key, 'plain_token' => $plainToken];
    }

    public function revoke(): void
    {
        $this->update(['is_active' => false, 'revoked_at' => now()]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
