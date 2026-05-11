<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookSetting extends Model
{
    protected $fillable = [
        'api_integration_id',
        'name',
        'callback_url',
        'events',
        'is_active',
        'secret_hash',
        'retry_count',
        'last_triggered_at',
        'last_success_at',
    ];

    protected function casts(): array
    {
        return [
            'events'            => 'array',
            'is_active'         => 'boolean',
            'last_triggered_at' => 'datetime',
            'last_success_at'   => 'datetime',
        ];
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(ApiIntegration::class, 'api_integration_id');
    }
}
