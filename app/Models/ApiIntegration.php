<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiIntegration extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'base_url',
        'api_version',
        'description',
        'last_accessed_at',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active'        => 'boolean',
            'last_accessed_at' => 'datetime',
            'last_synced_at'   => 'datetime',
        ];
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function webhookSettings(): HasMany
    {
        return $this->hasMany(WebhookSetting::class);
    }

    public function requestLogs(): HasMany
    {
        return $this->hasMany(ApiRequestLog::class);
    }
}
