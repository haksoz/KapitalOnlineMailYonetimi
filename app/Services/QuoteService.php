<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\QuoteItemOption;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuoteService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, int $userId): Quote
    {
        $lastException = null;

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($data, $userId): Quote {
                    $normalized = $this->normalizeItems($data['type'], $data['items'], collect(), true);

                    $quote = new Quote([
                        'quote_number' => $this->nextNumber(),
                        'customer_cari_id' => $data['customer_cari_id'],
                        'type' => $data['type'],
                        'status' => Quote::STATUS_DRAFT,
                        'currency' => $normalized['currency'],
                        'vat_rate' => $data['type'] === Quote::TYPE_FIRM ? $data['vat_rate'] : null,
                        'valid_until' => $data['valid_until'] ?? null,
                        'notes' => $data['notes'] ?? null,
                        'internal_notes' => $data['internal_notes'] ?? null,
                        'created_by' => $userId,
                    ]);
                    $quote->save();
                    $this->replaceItems($quote, $normalized['items']);

                    return $quote->fresh(['items.options', 'customerCari']);
                });
            } catch (UniqueConstraintViolationException $exception) {
                $lastException = $exception;
            }
        }

        throw $lastException;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Quote $quote, array $data): Quote
    {
        if (! $quote->canRevise()) {
            throw ValidationException::withMessages([
                'quote' => 'Yalnızca taslak veya reddedilen teklif düzenlenebilir.',
            ]);
        }

        return DB::transaction(function () use ($quote, $data): Quote {
            $existing = $quote->items()->with('options')->get();
            $normalized = $this->normalizeItems(
                $quote->type,
                $data['items'],
                $existing,
                (bool) ($data['refresh_catalog'] ?? false),
            );

            $quote->fill([
                'customer_cari_id' => $data['customer_cari_id'],
                'currency' => $normalized['currency'],
                'vat_rate' => $quote->isFirm() ? $data['vat_rate'] : null,
                'valid_until' => $data['valid_until'] ?? null,
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
            ]);
            $quote->save();
            $this->replaceItems($quote, $normalized['items']);

            return $quote->fresh(['items.options', 'customerCari']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function convert(Quote $source, array $data, int $userId): Quote
    {
        return DB::transaction(function () use ($source, $data, $userId): Quote {
            $source = Quote::query()->whereKey($source->id)->lockForUpdate()->firstOrFail();
            $source->load('items.options');

            if (! $source->canConvert()) {
                throw ValidationException::withMessages([
                    'quote' => 'Bu teklif kesin teklife dönüştürülemez.',
                ]);
            }

            $items = $this->firmItemsFromOptional($source, $data['lines']);
            $firm = $this->saveFirmQuote($source, $data, $userId);

            $this->replaceItems($firm, $items);
            $source->status = Quote::STATUS_CONVERTED;
            $source->save();

            return $firm->fresh(['items', 'customerCari', 'sourceQuote']);
        });
    }

    public function transition(Quote $quote, string $status, ?int $userId = null): Quote
    {
        $allowed = match ($status) {
            Quote::STATUS_SENT => in_array($quote->status, [Quote::STATUS_DRAFT, Quote::STATUS_REJECTED], true),
            Quote::STATUS_APPROVED => $quote->isFirm() && $quote->status === Quote::STATUS_SENT,
            Quote::STATUS_REJECTED, Quote::STATUS_EXPIRED => $quote->status === Quote::STATUS_SENT,
            Quote::STATUS_CANCELLED => in_array($quote->status, [
                Quote::STATUS_DRAFT,
                Quote::STATUS_SENT,
                Quote::STATUS_APPROVED,
            ], true),
            default => false,
        };

        if (! $allowed) {
            throw ValidationException::withMessages([
                'status' => 'Bu durum değişikliği yapılamaz.',
            ]);
        }

        $quote->status = $status;
        if ($status === Quote::STATUS_SENT) {
            $quote->sent_at = now();
        }
        if ($status === Quote::STATUS_APPROVED) {
            $quote->approved_at = now();
            $quote->approved_by = $userId;
        }
        $quote->save();

        return $quote;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  Collection<int, QuoteItem>  $existingItems
     * @return array{currency: string, items: array<int, array<string, mixed>>}
     */
    private function normalizeItems(string $type, array $rows, Collection $existingItems, bool $refreshCatalog): array
    {
        $productIds = collect($rows)->pluck('product_id')->filter()->all();
        $products = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');
        $currency = null;
        $items = [];

        foreach (array_values($rows) as $index => $row) {
            $product = $products->get($row['product_id']);
            if (! $product) {
                throw ValidationException::withMessages([
                    "items.$index.product_id" => 'Ürün bulunamadı.',
                ]);
            }

            $productCurrency = $product->currency ?? Product::CURRENCY_USD;
            if ($currency === null) {
                $currency = $productCurrency;
            } elseif ($currency !== $productCurrency) {
                throw ValidationException::withMessages([
                    "items.$index.product_id" => 'Teklifteki tüm ürünler aynı para biriminde olmalıdır.',
                ]);
            }

            $quantity = null;
            if ($type === Quote::TYPE_FIRM) {
                $quantity = (int) ($row['quantity'] ?? 0);
                if ($quantity < 1) {
                    throw ValidationException::withMessages([
                        "items.$index.quantity" => 'Adet en az 1 olmalıdır.',
                    ]);
                }
            }

            $existing = $this->existingItem($existingItems, $row['id'] ?? null);
            $sameProduct = $existing && (int) $existing->product_id === (int) $product->id;
            $keepSnapshot = $sameProduct && ! $refreshCatalog;

            $item = [
                'product_id' => $product->id,
                'product_name' => $keepSnapshot ? $existing->product_name : $product->name,
                'stock_code' => $keepSnapshot ? $existing->stock_code : $product->stock_code,
                'quantity' => $quantity,
                'sort_order' => $index,
                'source_quote_item_id' => null,
                'taahhut_tipi' => null,
                'birim_alis' => null,
                'birim_satis' => null,
                'options' => [],
            ];

            if ($type === Quote::TYPE_FIRM) {
                $tip = (string) $row['taahhut_tipi'];
                $item['taahhut_tipi'] = $tip;
                $item['birim_alis'] = $this->resolveCost($product, $tip, $existing, $keepSnapshot, true);
                $item['birim_satis'] = $this->resolveSale($product, $tip, $row['birim_satis'] ?? null, $index, 'birim_satis');
            } else {
                $item['options'] = $this->optionalOptions($product, $row['options'] ?? [], $existing, $keepSnapshot, $index);
            }

            $items[] = $item;
        }

        if ($currency === null) {
            throw ValidationException::withMessages([
                'items' => 'En az bir teklif kalemi girilmelidir.',
            ]);
        }

        return [
            'currency' => $currency,
            'items' => $items,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array<string, mixed>>
     */
    private function firmItemsFromOptional(Quote $source, array $lines): array
    {
        $sourceItems = $source->items->keyBy('id');
        $items = [];
        $included = 0;

        foreach (array_values($lines) as $index => $line) {
            if (! $this->isEnabled($line['include'] ?? false)) {
                continue;
            }

            $included++;
            $sourceItem = $sourceItems->get((int) ($line['quote_item_id'] ?? 0));
            if (! $sourceItem) {
                throw ValidationException::withMessages([
                    "lines.$index.quote_item_id" => 'Kalem bu teklife ait değil.',
                ]);
            }

            $tip = (string) ($line['taahhut_tipi'] ?? '');
            $option = $sourceItem->options->firstWhere('taahhut_tipi', $tip);
            if (! $option) {
                throw ValidationException::withMessages([
                    "lines.$index.taahhut_tipi" => 'Seçilen fiyat modeli bu kalemde yok.',
                ]);
            }

            $quantity = (int) ($line['quantity'] ?? 0);
            if ($quantity < 1) {
                throw ValidationException::withMessages([
                    "lines.$index.quantity" => 'Adet en az 1 olmalıdır.',
                ]);
            }

            $postedSale = $line['birim_satis'] ?? null;
            $sale = ($postedSale === null || $postedSale === '') ? $option->birim_satis : $postedSale;

            $items[] = [
                'product_id' => $sourceItem->product_id,
                'product_name' => $sourceItem->product_name,
                'stock_code' => $sourceItem->stock_code,
                'quantity' => $quantity,
                'sort_order' => count($items),
                'source_quote_item_id' => $sourceItem->id,
                'taahhut_tipi' => $tip,
                'birim_alis' => QuoteMath::unit($option->birim_alis),
                'birim_satis' => $this->resolveSaleValue($sale, $index, 'birim_satis', 'lines'),
                'options' => [],
            ];
        }

        if ($included === 0) {
            throw ValidationException::withMessages([
                'lines' => 'Kesin teklife en az bir kalem alınmalıdır.',
            ]);
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $postedOptions
     * @return array<int, array<string, mixed>>
     */
    private function optionalOptions(Product $product, array $postedOptions, ?QuoteItem $existing, bool $keepSnapshot, int $index): array
    {
        $options = [];

        foreach (Quote::COMMITMENTS as $tip) {
            $posted = $postedOptions[$tip] ?? null;
            if (! is_array($posted) || ! $this->isEnabled($posted['enabled'] ?? false)) {
                continue;
            }

            $options[] = [
                'taahhut_tipi' => $tip,
                'birim_alis' => $this->resolveCost($product, $tip, $existing, $keepSnapshot, false),
                'birim_satis' => $this->resolveSale($product, $tip, $posted['birim_satis'] ?? null, $index, "options.$tip.birim_satis"),
            ];
        }

        if ($options === []) {
            throw ValidationException::withMessages([
                "items.$index.options" => 'Her kalem için en az bir fiyat seçeneği seçilmelidir.',
            ]);
        }

        return $options;
    }

    private function resolveCost(Product $product, string $tip, ?QuoteItem $existing, bool $keepSnapshot, bool $firm): ?string
    {
        if ($keepSnapshot && $existing) {
            if ($firm && $existing->taahhut_tipi === $tip) {
                return QuoteMath::unit($existing->birim_alis);
            }

            if (! $firm) {
                $option = $existing->options->firstWhere('taahhut_tipi', $tip);
                if ($option) {
                    return QuoteMath::unit($option->birim_alis);
                }
            }
        }

        return QuoteMath::unit($product->pricesForCommitment($tip)['alis']);
    }

    private function resolveSale(Product $product, string $tip, mixed $posted, int $index, string $field): string
    {
        $value = ($posted === null || $posted === '')
            ? $product->pricesForCommitment($tip)['satis']
            : $posted;

        return $this->resolveSaleValue($value, $index, $field, 'items');
    }

    private function resolveSaleValue(mixed $value, int $index, string $field, string $prefix): string
    {
        if ($value === null || $value === '') {
            throw ValidationException::withMessages([
                "$prefix.$index.$field" => 'Satış fiyatı girilmelidir.',
            ]);
        }

        try {
            $unit = QuoteMath::unit($value);
        } catch (\Throwable) {
            $unit = null;
        }

        if ($unit === null || str_starts_with($unit, '-')) {
            throw ValidationException::withMessages([
                "$prefix.$index.$field" => 'Satış fiyatı geçersiz.',
            ]);
        }

        return $unit;
    }

    /**
     * @param  Collection<int, QuoteItem>  $existingItems
     */
    private function existingItem(Collection $existingItems, mixed $id): ?QuoteItem
    {
        if ($id === null || $id === '') {
            return null;
        }

        return $existingItems->firstWhere('id', (int) $id);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function replaceItems(Quote $quote, array $items): void
    {
        $itemIds = $quote->items()->pluck('id');
        if ($itemIds->isNotEmpty()) {
            QuoteItemOption::query()->whereIn('quote_item_id', $itemIds)->delete();
            QuoteItem::query()->whereIn('id', $itemIds)->delete();
        }

        foreach ($items as $row) {
            $options = $row['options'] ?? [];
            unset($row['options']);
            $item = $quote->items()->create($row);
            if ($options !== []) {
                $item->options()->createMany($options);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveFirmQuote(Quote $source, array $data, int $userId): Quote
    {
        $lastException = null;

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $firm = new Quote([
                'quote_number' => $this->nextNumber(),
                'customer_cari_id' => $source->customer_cari_id,
                'type' => Quote::TYPE_FIRM,
                'status' => Quote::STATUS_DRAFT,
                'currency' => $source->currency,
                'vat_rate' => $data['vat_rate'],
                'valid_until' => $data['valid_until'] ?? $source->valid_until,
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'source_quote_id' => $source->id,
                'created_by' => $userId,
            ]);

            try {
                $firm->save();

                return $firm;
            } catch (UniqueConstraintViolationException $exception) {
                if (str_contains($exception->getMessage(), 'source_quote_id')) {
                    throw ValidationException::withMessages([
                        'quote' => 'Bu birim fiyat teklifi zaten kesin teklife dönüştürülmüş.',
                    ]);
                }

                $lastException = $exception;
            }
        }

        throw $lastException;
    }

    private function nextNumber(): string
    {
        $max = 0;
        $numbers = Quote::query()->where('quote_number', 'like', 'TKL%')->pluck('quote_number');
        foreach ($numbers as $number) {
            if (preg_match('/^TKL(\d{6})$/', (string) $number, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return 'TKL'.str_pad((string) ($max + 1), 6, '0', STR_PAD_LEFT);
    }

    private function isEnabled(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 'on' || $value === 'true';
    }
}
