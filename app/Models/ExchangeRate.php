<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $table = 'exchange_rates';

    protected $fillable = [
        'currency_code',
        'name',
        'forex_buying',
        'forex_selling',
        'banknote_buying',
        'banknote_selling',
        'effective_date',
    ];

    protected $casts = [
        'forex_buying' => 'decimal:6',
        'forex_selling' => 'decimal:6',
        'banknote_buying' => 'decimal:6',
        'banknote_selling' => 'decimal:6',
        'effective_date' => 'date',
    ];
}
