<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\QuoteSubscriptionRecord;
use App\Models\Subscription;
use App\Models\WorkTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuoteSubscriptionService
{
    public function item(int $id): QuoteItem
    {
        $item = QuoteItem::with(['quote', 'workTask', 'subscriptionRecord'])->findOrFail($id);
        $this->check($item->quote->isFirm() && in_array($item->quote->status, ['approved', 'partially_approved'], true) && $item->isAccepted(), 'Yalnızca onaylı kesin teklif kaleminden abonelik işlemi yapılabilir.');
        $this->check(! $item->subscriptionRecord, 'Bu teklif kaleminin abonelik işlemi zaten kaydedilmiş.');
        $this->check(! $item->workTask || $item->workTask->isOpen(), 'Bağlı iş sonuçlandırılmış.');

        return $item;
    }

    public function source(QuoteItem $item): ?Subscription
    {
        $context = $item->renewalContext();
        if (! $context || (int) $context['product_id'] !== (int) $item->product_id) {
            return null;
        }

        return Subscription::find($context['subscription_id']);
    }

    public function defaults(QuoteItem $item): array
    {
        $source = $this->source($item);

        return [
            'customer_cari_id' => $item->quote->customer_cari_id,
            'provider_cari_id' => $source?->provider_cari_id,
            'service_provider_id' => $source?->service_provider_id,
            'product_id' => $item->product_id, 'quantity' => $item->quantity,
            'taahhut_tipi' => $item->taahhut_tipi,
            'faturalama_periyodu' => $item->taahhut_tipi === Subscription::TAAHHUT_ANNUAL_COMMITMENT ? 'yearly' : 'monthly',
            'usd_birim_alis' => $item->birim_alis, 'usd_birim_satis' => $item->birim_satis,
            'currency' => $item->quote->currency, 'vat_rate' => $item->quote->vat_rate, 'durum' => 'active',
        ];
    }

    public function save(int $itemId, array $data, ?Subscription $existing, callable $persist): Subscription
    {
        return DB::transaction(function () use ($itemId, $data, $existing, $persist) {
            $item = QuoteItem::findOrFail($itemId);
            Quote::whereKey($item->quote_id)->lockForUpdate()->firstOrFail();
            $item = $this->item($itemId);
            $task = $item->work_task_id ? WorkTask::whereKey($item->work_task_id)->lockForUpdate()->firstOrFail() : null;
            $this->check(! $task || $task->isOpen(), 'Bağlı iş sonuçlandırılmış.');
            $source = $this->source($item);
            $defaults = $this->defaults($item);
            foreach (['customer_cari_id', 'product_id', 'quantity', 'taahhut_tipi', 'faturalama_periyodu', 'currency'] as $field) {
                $this->check((string) ($data[$field] ?? $existing?->$field) === (string) $defaults[$field], 'Abonelik bilgileri onaylı teklif kalemiyle aynı olmalıdır.');
            }
            foreach (['usd_birim_alis', 'usd_birim_satis'] as $field) {
                $this->check(QuoteMath::unit($data[$field] ?? null) === QuoteMath::unit($defaults[$field]), 'Alış ve satış fiyatları onaylı teklifle aynı olmalıdır.');
            }
            $this->check(($data['durum'] ?? '') === 'active', 'Sonuç aboneliği aktif olmalıdır.');
            if ($existing) {
                $this->check($source && (int) $source->id === (int) $existing->id, 'Bu teklif farklı bir aboneliğe ait.');
                $existing = Subscription::whereKey($existing->id)->lockForUpdate()->firstOrFail();
                $this->check((int) $existing->customer_cari_id === (int) $item->quote->customer_cari_id && (int) $existing->product_id === (int) $item->product_id, 'Mevcut aboneliğin müşteri ve ürünü teklifle aynı olmalıdır.');
                $this->check(! empty($data['auto_renew']), 'Mevcut aboneliğin otomatik yenilemesini açın.');
                $this->check($data['sozlesme_no'] === $existing->sozlesme_no, 'Mevcut abonelikte sözleşme numarası korunmalıdır.');
                $data['bitis_tarihi'] = $existing->bitis_tarihi?->toDateString();
                $data['baslangic_tarihi'] = $existing->baslangic_tarihi->toDateString();
                $data['planned_cancel_date'] = null;
            } else {
                $this->check(! $source || $data['sozlesme_no'] !== $source->sozlesme_no, 'Yeni abonelik için yeni sözleşme numarası girin.');
                $this->check(! Subscription::where('sozlesme_no', $data['sozlesme_no'])->where('provider_cari_id', $data['provider_cari_id'] ?? null)->exists(), 'Bu sözleşme numarası zaten kayıtlı.');
            }
            $subscription = $persist($data, $existing);
            QuoteSubscriptionRecord::create([
                'quote_id' => $item->quote_id, 'quote_item_id' => $item->id,
                'subscription_id' => $subscription->id, 'previous_subscription_id' => $source?->id,
                'mode' => $existing ? 'existing' : 'new', 'contract_number' => $subscription->sozlesme_no,
                'previous_contract_number' => $source?->sozlesme_no,
                'created_by' => auth()->id(),
            ]);
            if ($task) {
                $task->record($item->quote->quote_number.' · '.($existing ? 'Mevcut abonelik yenilendi: ' : 'Yeni abonelik oluşturuldu: ').$subscription->sozlesme_no, auth()->id(), ['subscription_id' => $subscription->id, 'previous_subscription_id' => $source?->id]);
                app(QuoteWorkTaskService::class)->refresh($item->quote);
            }

            app(QuoteFollowUpService::class)->sync($item->quote);

            return $subscription;
        });
    }

    private function check(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['quote_item_id' => $message]);
        }
    }
}
