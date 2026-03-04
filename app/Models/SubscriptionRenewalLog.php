<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionRenewalLog extends Model
{
    protected $table = 'subscription_renewal_logs';

    protected $fillable = [
        'run_at',
        'as_of_date',
        'renewed_count',
        'renewed_ids',
    ];

    protected function casts(): array
    {
        return [
            'run_at' => 'datetime',
            'as_of_date' => 'date',
            'renewed_count' => 'integer',
            'renewed_ids' => 'array',
        ];
    }
}
