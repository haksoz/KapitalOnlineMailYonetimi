<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = ['name', 'code'];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
