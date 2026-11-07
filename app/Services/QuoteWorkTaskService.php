<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\WorkTask;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class QuoteWorkTaskService
{
    public function selected(array $ids, ?int $quoteId = null): Collection
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $tasks = WorkTask::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
        $this->check($tasks->count() === count($ids), 'Seçilen işlerden biri bulunamadı.');
        foreach ($tasks as $task) {
            $this->check($task->type === WorkTask::TYPE_RENEWAL && $task->isOpen() && $task->cost_checked_at, 'Seçilen tüm yenileme işleri açık ve fiyat kontrolü tamamlanmış olmalıdır.');
            $this->check(! $task->hasBlockingQuote($quoteId), 'Bu işe bağlı devam eden bir teklif var. Mevcut teklifi kullanın.');
        }
        $this->check($tasks->pluck('customer_cari_id')->unique()->count() <= 1, 'Aynı müşteriye ait işleri seçin.');
        $this->check($tasks->map(fn ($task) => $task->snapshot['currency'])->unique()->count() <= 1, 'Seçilen işlerin para birimi aynı olmalıdır.');
        $this->check($tasks->map(fn ($task) => $this->vat($task))->unique()->count() <= 1, 'Seçilen işlerin KDV oranı aynı olmalıdır.');

        return $tasks;
    }

    public function vat(WorkTask $task): string
    {
        return QuoteMath::unit($task->snapshot['vat_rate'] ?? $task->subject?->vat_rate ?? '20');
    }

    public function prepare(array $data, array &$normalized, ?Quote $quote = null, ?int $ignoreQuoteId = null): Collection
    {
        $ids = $quote ? $quote->workTasks()->pluck('work_tasks.id')->all() : ($data['work_task_ids'] ?? (! empty($data['work_task_id']) ? [$data['work_task_id']] : []));
        $tasks = $this->selected($ids, $quote?->id ?? $ignoreQuoteId)->keyBy('id');
        foreach ($tasks as $task) {
            $this->check((int) $data['customer_cari_id'] === (int) $task->customer_cari_id, 'Müşteri seçilen işlerle aynı olmalıdır.');
            $this->check($normalized['currency'] === $task->snapshot['currency'], 'Teklifin para birimi abonelikle aynı olmalıdır.', 'items');
            if (($data['type'] ?? $quote?->type) === 'firm') {
                $this->check(QuoteMath::unit($data['vat_rate']) === $this->vat($task), 'Teklifin KDV oranı aboneliklerle aynı olmalıdır.');
            }
        }
        $found = [];
        foreach ($normalized['items'] as &$item) {
            $taskId = $item['work_task_id'] ?? null;
            if (! $taskId && $tasks->count() === 1 && (int) $tasks->first()->snapshot['product_id'] === (int) $item['product_id']) {
                $taskId = $tasks->first()->id;
            }
            if (! $taskId) {
                continue;
            }
            $task = $tasks->get((int) $taskId);
            $this->check($task && (int) $task->snapshot['product_id'] === (int) $item['product_id'], 'Teklif kaleminin ürün ve iş bağlantısı geçersiz.', 'items');
            $found[] = $task->id;
            $item['work_task_id'] = $task->id;
            $item['renewal_context'] = $task->snapshot;
            if ($item['taahhut_tipi'] === $task->snapshot['commitment']) {
                $item['birim_alis'] = $task->verified_cost;
            }
            foreach ($item['options'] as &$option) {
                if ($option['taahhut_tipi'] === $task->snapshot['commitment']) {
                    $option['birim_alis'] = $task->verified_cost;
                }
            }
            unset($option);
        }
        unset($item);
        $this->check(! array_diff($tasks->keys()->all(), $found), 'Seçilen her iş için ayrı teklif kalemi bulunmalıdır.', 'items');

        return $tasks->values();
    }

    public function attach(Quote $quote, Collection $tasks): void
    {
        $quote->workTasks()->sync($tasks->pluck('id')->all());
        $quote->update([
            'work_task_id' => $tasks->count() === 1 ? $tasks->first()->id : null,
            'renewal_context' => $tasks->isEmpty() ? null : ($tasks->count() === 1 ? $tasks->first()->snapshot : ['subscriptions' => $tasks->pluck('snapshot')->all()]),
        ]);
        app(QuoteFollowUpService::class)->sync($quote);
    }

    public function refresh(Quote $quote, ?int $userId = null): void
    {
        app(QuoteFollowUpService::class)->sync($quote);
        foreach ($quote->workTasks()->get() as $task) {
            if (! $task->isOpen() || $task->hasBlockingQuote($quote->id)) {
                continue;
            }
            $items = $quote->items()->where('work_task_id', $task->id)->with('subscriptionRecord')->get();
            $accepted = $items->filter(fn ($item) => $item->isAccepted());
            $pending = $items->where('response_status', 'pending')->filter(fn ($item) => ! $item->isAccepted());
            if (in_array($quote->status, ['cancelled', 'expired', 'converted'], true)) {
                $stage = 'pricing';
            } elseif ($accepted->isNotEmpty()) {
                $complete = $pending->isEmpty() && $accepted->every(fn ($item) => $item->subscriptionRecord !== null);
                $stage = $complete ? 'completed' : 'renewal_pending';
            } elseif ($pending->isNotEmpty() && in_array($quote->status, ['sent', 'partially_approved'], true)) {
                $stage = ($task->snapshot['auto_renew'] ?? false) ? 'renewal_pending' : 'response_pending';
            } elseif ($quote->status === 'draft') {
                $stage = 'notification_pending';
            } else {
                $stage = 'pricing';
            }
            $values = ['status' => $stage];
            if ($stage === 'completed') {
                $values += ['completed_at' => now(), 'result' => 'Onaylı teklif kalemlerinin abonelik işlemleri kaydedildi.'];
            }
            $task->update($values);
            $task->record($quote->quote_number.' · '.Quote::statusLabel($quote->status).' · '.$task->statusLabel(), $userId ?? auth()->id());
        }
    }

    private function check(bool $condition, string $message, string $field = 'work_task_id'): void
    {
        if (! $condition) {
            throw ValidationException::withMessages([$field => $message]);
        }
    }
}
