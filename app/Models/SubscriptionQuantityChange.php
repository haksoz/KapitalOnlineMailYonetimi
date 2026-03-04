<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionQuantityChange extends Model
{
    protected $table = 'subscription_quantity_changes';

    protected $fillable = [
        'subscription_id',
        'previous_quantity',
        'new_quantity',
        'effective_date',
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
}
