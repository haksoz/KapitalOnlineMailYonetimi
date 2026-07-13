<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ProductPriceHistory;

class ProductObserver
{
    private const PRICE_FIELDS = [
        'alis_usd_monthly_commitment',
        'satis_usd_monthly_commitment',
        'alis_usd_monthly_no_commitment',
        'satis_usd_monthly_no_commitment',
        'alis_usd_yearly_commitment',
        'satis_usd_yearly_commitment',
    ];

    public function updated(Product $product): void
    {
        $changedBy = auth()->id();
        $now = now();

        foreach (self::PRICE_FIELDS as $field) {
            if (! $product->wasChanged($field)) {
                continue;
            }

            $oldValue = $product->getOriginal($field);
            $newValue = $product->getAttribute($field);

            if ($this->isEqual($oldValue, $newValue)) {
                continue;
            }

            ProductPriceHistory::create([
                'product_id' => $product->id,
                'field_name' => $field,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'changed_by' => $changedBy,
                'reason' => null,
                'created_at' => $now,
            ]);
        }
    }

    private function isEqual(mixed $old, mixed $new): bool
    {
        if ($old === null && $new === null) {
            return true;
        }

        if ($old === null || $new === null) {
            return false;
        }

        return (string) $old === (string) $new;
    }
}
