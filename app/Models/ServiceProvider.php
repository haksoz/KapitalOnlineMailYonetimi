<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceProvider extends Model
{
    protected $table = 'service_providers';

    protected $fillable = [
        'name',
        'code',
        'service_types',
    ];

    protected $casts = [
        'service_types' => 'array',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'service_provider_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'service_provider_id');
    }
}
