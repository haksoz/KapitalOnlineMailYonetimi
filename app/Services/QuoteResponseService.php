<?php

namespace App\Services;

use App\Models\Quote;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuoteResponseService
{
    public function all(Quote $quote, string $decision, ?int $userId): Quote
    {
        return $this->save($quote, null, $decision, null, $userId);
    }

    public function save(Quote $quote, ?int $itemId, string $decision, ?string $note, ?int $userId): Quote
    {
        return DB::transaction(function () use ($quote, $itemId, $decision, $note, $userId) {
            $quote = Quote::whereKey($quote->id)->lockForUpdate()->firstOrFail();
            $this->check($quote->isFirm() && in_array($quote->status, ['sent', 'partially_approved'], true), 'Müşteri yanıtı yalnızca gönderilmiş kesin tekliflerde kaydedilebilir.');
            $this->check(in_array($decision, ['approved', 'rejected'], true), 'Geçersiz müşteri yanıtı.');
            $items = $quote->items()->where('response_status', 'pending')->when($itemId, fn ($q) => $q->whereKey($itemId))->get();
            $this->check($items->isNotEmpty(), 'Bu kalem sonuçlandırılmış veya bu teklife ait değil.');
            foreach ($items->sortBy('work_task_id') as $item) {
                if ($item->work_task_id) {
                    $task = \App\Models\WorkTask::whereKey($item->work_task_id)->lockForUpdate()->firstOrFail();
                    $this->check($task->isOpen() && ! $task->hasBlockingQuote($quote->id), 'Kalemin işi sonuçlandırılmış veya başka bir teklife alınmış.');
                    $task->record($quote->quote_number.' · '.$item->product_name.' · '.($decision === 'approved' ? 'Müşteri onayladı.' : 'Müşteri reddetti.').($note ? ' '.$note : ''), $userId);
                }
                $item->update(['response_status' => $decision, 'response_note' => $note, 'responded_at' => now(), 'responded_by' => $userId]);
            }
            $statuses = $quote->items()->pluck('response_status');
            $status = $statuses->every(fn ($s) => $s === 'approved') ? 'approved'
                : ($statuses->contains('approved') ? 'partially_approved' : ($statuses->every(fn ($s) => $s === 'rejected') ? 'rejected' : 'sent'));
            $quote->update(['status' => $status, 'approved_at' => $statuses->contains('approved') ? ($quote->approved_at ?? now()) : null, 'approved_by' => $statuses->contains('approved') ? ($quote->approved_by ?? $userId) : null]);
            app(QuoteWorkTaskService::class)->refresh($quote, $userId);

            return $quote->fresh(['items', 'workTasks']);
        });
    }

    private function check(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['quote' => $message]);
        }
    }
}
