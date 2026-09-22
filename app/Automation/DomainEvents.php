<?php

namespace App\Automation;

use App\Models\PendingBilling;
use App\Models\SalesInvoice;
use App\Models\Subscription;
use App\Models\SubscriptionPriceHistory;
use App\Models\SubscriptionQuantityHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

final class DomainEvents
{
    public function __construct(private readonly AutomationBus $bus)
    {
    }

    public function subscriptionCreated(Subscription $subscription): void
    {
        $this->emitSafely(new DomainEvent(
            type: EventType::SubscriptionCreated,
            subject: $subscription,
            cariId: $subscription->customer_cari_id,
            context: DomainPlaceholders::subscriptionContext($subscription),
            fingerprint: (string) $subscription->getKey(),
            occurredAt: now()->timezone(Automation::TIMEZONE),
        ));
    }

    public function subscriptionAutoRenewDisabled(Subscription $subscription): void
    {
        $this->emitSafely(new DomainEvent(
            type: EventType::SubscriptionAutoRenewDisabled,
            subject: $subscription,
            cariId: $subscription->customer_cari_id,
            context: DomainPlaceholders::subscriptionContext($subscription, [
                'auto_renew' => false,
            ]),
            fingerprint: 'once',
            occurredAt: now()->timezone(Automation::TIMEZONE),
        ));
    }

    public function subscriptionPriceChanged(Subscription $subscription, SubscriptionPriceHistory $history): void
    {
        $this->emitSafely(new DomainEvent(
            type: EventType::SubscriptionPriceChanged,
            subject: $subscription,
            cariId: $subscription->customer_cari_id,
            context: DomainPlaceholders::subscriptionContext($subscription, [
                'price_history_id' => $history->id,
                'field_name' => $history->field_name,
                'old_value' => $history->old_value,
                'new_value' => $history->new_value,
            ]),
            fingerprint: 'history:'.$history->id,
            occurredAt: now()->timezone(Automation::TIMEZONE),
        ));
    }

    public function subscriptionQuantityChanged(Subscription $subscription, SubscriptionQuantityHistory $history): void
    {
        $this->emitSafely(new DomainEvent(
            type: EventType::SubscriptionQuantityChanged,
            subject: $subscription,
            cariId: $subscription->customer_cari_id,
            context: DomainPlaceholders::subscriptionContext($subscription, [
                'quantity_history_id' => $history->id,
                'previous_quantity' => $history->previous_quantity,
                'new_quantity' => $history->new_quantity,
            ]),
            fingerprint: 'history:'.$history->id,
            occurredAt: now()->timezone(Automation::TIMEZONE),
        ));
    }

    public function subscriptionExpired(Subscription $subscription): void
    {
        $end = $subscription->bitis_tarihi?->toDateString() ?? now()->timezone(Automation::TIMEZONE)->toDateString();
        $this->emitSafely(new DomainEvent(
            type: EventType::SubscriptionExpired,
            subject: $subscription,
            cariId: $subscription->customer_cari_id,
            context: DomainPlaceholders::subscriptionContext($subscription),
            fingerprint: 'end:'.$end,
            occurredAt: now()->timezone(Automation::TIMEZONE),
        ));
    }

    public function orderCreated(PendingBilling $order): void
    {
        $order->loadMissing(['subscription']);
        $this->emitSafely(new DomainEvent(
            type: EventType::OrderCreated,
            subject: $order,
            cariId: $order->subscription?->customer_cari_id,
            context: DomainPlaceholders::orderContext($order),
            fingerprint: (string) $order->getKey(),
            occurredAt: now()->timezone(Automation::TIMEZONE),
        ));
    }

    public function invoiceIssued(SalesInvoice $invoice): void
    {
        $this->emitSafely(new DomainEvent(
            type: EventType::InvoiceIssued,
            subject: $invoice,
            cariId: $invoice->customer_cari_id,
            context: InvoicePlaceholders::invoiceContext($invoice),
            fingerprint: (string) $invoice->getKey(),
            occurredAt: now()->timezone(Automation::TIMEZONE),
        ));
    }

    public function invoicePaid(SalesInvoice $invoice): void
    {
        $paidDay = $invoice->paid_at instanceof Carbon
            ? $invoice->paid_at->timezone(Automation::TIMEZONE)->toDateString()
            : now()->timezone(Automation::TIMEZONE)->toDateString();

        $this->emitSafely(new DomainEvent(
            type: EventType::InvoicePaid,
            subject: $invoice,
            cariId: $invoice->customer_cari_id,
            context: InvoicePlaceholders::invoiceContext($invoice, [
                'paid_at' => $paidDay,
            ]),
            fingerprint: 'paid:'.$paidDay,
            occurredAt: now()->timezone(Automation::TIMEZONE),
        ));
    }

    private function emitSafely(DomainEvent $event): void
    {
        try {
            $this->bus->emit($event);
        } catch (\Throwable $e) {
            Log::error('Otomasyon olayı kuyruğa yazılamadı.', [
                'event' => $event->type->value,
                'subject_type' => $event->subject->getMorphClass(),
                'subject_id' => $event->subject->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
