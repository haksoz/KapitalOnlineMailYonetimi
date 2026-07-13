<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionQuantityHistory extends Model
{
    protected $table = 'subscription_quantity_history';

    protected $fillable = [
        'subscription_id',
        'previous_quantity',
        'new_quantity',
        'effective_date',
        'changed_by',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'previous_quantity' => 'integer',
            'new_quantity' => 'integer',
            'effective_date' => 'date',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
