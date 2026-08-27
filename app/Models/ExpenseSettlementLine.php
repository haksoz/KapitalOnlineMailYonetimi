<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseSettlementLine extends Model
{
    protected $table = 'expense_settlement_lines';

    protected $fillable = [
        'expense_settlement_id',
        'pending_billing_id',
        'line_amount_tl',
    ];

    protected function casts(): array
    {
        return [
            'line_amount_tl' => 'decimal:2',
        ];
    }

    public function expenseSettlement(): BelongsTo
    {
        return $this->belongsTo(ExpenseSettlement::class);
    }

    public function pendingBilling(): BelongsTo
    {
        return $this->belongsTo(PendingBilling::class);
    }
}
