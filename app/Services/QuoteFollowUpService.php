<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\WorkTask;

class QuoteFollowUpService
{
    public function sync(Quote $quote): void
    {
        if ($quote->workTasks()->exists()) {
            return;
        }

        $items = $quote->items()->with('subscriptionRecord')->get();
        $pending = $items->where('response_status', 'pending')->reject(fn ($item) => $item->isAccepted());
        $accepted = $items->filter(fn ($item) => $item->isAccepted());
        $status = match (true) {
            in_array($quote->status, ['cancelled', 'expired', 'rejected'], true) => 'cancelled',
            $quote->status === 'converted' => 'completed',
            $quote->status === 'draft' => 'notification_pending',
            $pending->isNotEmpty() => 'response_pending',
            $accepted->contains(fn ($item) => $item->subscriptionRecord === null) => 'renewal_pending',
            default => 'completed',
        };
        $task = WorkTask::firstOrNew(['occurrence_key' => 'quote:'.$quote->id]);
        $before = $task->status;
        $due = $quote->valid_until ?? now('Europe/Istanbul')->addDays(3);
        $task->fill([
            'type' => WorkTask::TYPE_QUOTE,
            'subject_type' => $quote->getMorphClass(), 'subject_id' => $quote->id,
            'customer_cari_id' => $quote->customer_cari_id,
            'title' => $quote->quote_number.' · Teklif takibi',
            'status' => $status, 'due_date' => $due,
            'snapshot' => ['currency' => $quote->currency, 'item_count' => $items->count()],
            'completed_at' => in_array($status, ['completed', 'cancelled'], true) ? ($task->completed_at ?? now()) : null,
            'result' => in_array($status, ['completed', 'cancelled'], true) ? Quote::statusLabel($quote->status) : null,
        ]);
        if (! $task->exists) {
            $task->next_follow_up = now('Europe/Istanbul')->addDays(3)->min($due)->toDateString();
        }
        $task->save();
        if ($before !== $status) {
            $task->record($quote->quote_number.' · '.$task->statusLabel(), auth()->id());
        }
    }
}
