<?php

namespace App\Automation;

enum EventType: string
{
    case SubscriptionCreated = 'subscription.created';
    case SubscriptionExpiryApproaching = 'subscription.expiry_approaching';
    case SubscriptionExpired = 'subscription.expired';
    case SubscriptionAutoRenewDisabled = 'subscription.auto_renew_disabled';
    case SubscriptionPriceChanged = 'subscription.price_changed';
    case SubscriptionQuantityChanged = 'subscription.quantity_changed';
    case OrderCreated = 'order.created';
    case OrderPaymentDueApproaching = 'order.payment_due_approaching';
    case InvoiceIssued = 'invoice.issued';
    case InvoiceDueApproaching = 'invoice.due_approaching';
    case InvoiceOverdue = 'invoice.overdue';
    case InvoiceInterestClosure = 'invoice.interest_closure';
    case InvoicePaid = 'invoice.paid';

    public function label(): string
    {
        return match ($this) {
            self::SubscriptionCreated => 'Abonelik oluşturuldu',
            self::SubscriptionExpiryApproaching => 'Abonelik süresi yaklaşıyor',
            self::SubscriptionExpired => 'Abonelik süresi doldu',
            self::SubscriptionAutoRenewDisabled => 'Otomatik yenileme kapatıldı',
            self::SubscriptionPriceChanged => 'Abonelik fiyatı değişti',
            self::SubscriptionQuantityChanged => 'Abonelik miktarı değişti',
            self::OrderCreated => 'Sipariş oluştu',
            self::OrderPaymentDueApproaching => 'Sipariş ödeme zamanı yaklaşıyor',
            self::InvoiceIssued => 'Fatura oluştu',
            self::InvoiceDueApproaching => 'Fatura vadesi yaklaşıyor',
            self::InvoiceOverdue => 'Fatura vadesi geçti',
            self::InvoiceInterestClosure => 'Faiz uygulaması ve kapatma',
            self::InvoicePaid => 'Ödeme alındı',
        };
    }

    /**
     * @return list<self>
     */
    public static function invoiceReminderTypes(): array
    {
        return [
            self::InvoiceDueApproaching,
            self::InvoiceOverdue,
            self::InvoiceInterestClosure,
        ];
    }

    /**
     * @return list<self>
     */
    public static function subscriptionWindowTypes(): array
    {
        return [
            self::SubscriptionExpiryApproaching,
            self::SubscriptionExpired,
        ];
    }

    public function isInstantaneous(): bool
    {
        return match ($this) {
            self::SubscriptionCreated,
            self::SubscriptionAutoRenewDisabled,
            self::SubscriptionPriceChanged,
            self::SubscriptionQuantityChanged,
            self::OrderCreated,
            self::InvoiceIssued,
            self::InvoicePaid => true,
            default => false,
        };
    }

    public function showsAutoRenewCondition(): bool
    {
        return $this === self::SubscriptionExpiryApproaching
            || $this === self::SubscriptionExpired;
    }

    public function isSelectable(): bool
    {
        return $this !== self::OrderPaymentDueApproaching;
    }

    public function processGroup(): string
    {
        return match ($this) {
            self::SubscriptionCreated,
            self::SubscriptionPriceChanged,
            self::SubscriptionQuantityChanged,
            self::SubscriptionAutoRenewDisabled,
            self::SubscriptionExpiryApproaching,
            self::SubscriptionExpired => 'Abonelik',
            self::OrderCreated,
            self::OrderPaymentDueApproaching => 'Sipariş',
            default => 'Fatura',
        };
    }

    public function processSort(): int
    {
        return match ($this) {
            self::SubscriptionCreated => 10,
            self::SubscriptionPriceChanged => 20,
            self::SubscriptionQuantityChanged => 30,
            self::SubscriptionAutoRenewDisabled => 40,
            self::OrderCreated => 50,
            self::InvoiceIssued => 60,
            self::InvoiceDueApproaching => 70,
            self::InvoiceOverdue => 80,
            self::InvoiceInterestClosure => 90,
            self::InvoicePaid => 100,
            self::SubscriptionExpiryApproaching => 110,
            self::SubscriptionExpired => 120,
            self::OrderPaymentDueApproaching => 130,
        };
    }

    public function defaultTimingMode(): TimingMode
    {
        return $this->isInstantaneous()
            ? TimingMode::Immediate
            : TimingMode::AtSendAt;
    }

    /**
     * @return list<TimingMode>
     */
    public function allowedTimingModes(): array
    {
        return $this->isInstantaneous()
            ? [TimingMode::Immediate, TimingMode::AtSendAt]
            : [TimingMode::AtSendAt];
    }

    public function allowsTimingMode(TimingMode $mode): bool
    {
        return in_array($mode, $this->allowedTimingModes(), true);
    }

    /**
     * @return list<DedupePolicy>
     */
    public function allowedDedupePolicies(): array
    {
        return match ($this) {
            self::SubscriptionCreated,
            self::SubscriptionAutoRenewDisabled,
            self::OrderCreated,
            self::InvoiceIssued => [DedupePolicy::Once],
            self::SubscriptionPriceChanged,
            self::SubscriptionQuantityChanged,
            self::InvoicePaid => [DedupePolicy::PerOccurrenceKey],
            default => [DedupePolicy::Once, DedupePolicy::PerOccurrenceKey],
        };
    }

    public function allowsDedupePolicy(DedupePolicy $policy): bool
    {
        return in_array($policy, $this->allowedDedupePolicies(), true);
    }

    public function defaultDedupePolicy(): DedupePolicy
    {
        $allowed = $this->allowedDedupePolicies();
        if (count($allowed) === 1) {
            return $allowed[0];
        }

        return $this === self::InvoiceInterestClosure
            ? DedupePolicy::Once
            : DedupePolicy::PerOccurrenceKey;
    }

    public function dedupeOptionLabel(DedupePolicy $policy): string
    {
        if ($policy === DedupePolicy::Once) {
            return $policy->label();
        }

        return match ($this) {
            self::SubscriptionPriceChanged,
            self::SubscriptionQuantityChanged => 'Her değişimde bir kez',
            self::InvoicePaid => 'Her ödemede bir kez',
            default => $policy->label(),
        };
    }

    public function dedupeIntro(): string
    {
        return match ($this) {
            self::SubscriptionCreated,
            self::OrderCreated,
            self::InvoiceIssued => 'Bu olay kayda bir kez gider.',
            self::SubscriptionAutoRenewDisabled => 'Otomatik yenileme kapatılınca kayda bir kez gider.',
            self::SubscriptionPriceChanged => 'Alış veya satış fiyatı her değiştiğinde ayrı gider.',
            self::SubscriptionQuantityChanged => 'Adet her değiştiğinde ayrı gider.',
            self::InvoicePaid => 'Her ödeme bildirimi ayrı gider.',
            default => 'Bir kez: bu kayda bir daha gitmez. Pencere başına: pencerede kaldığı sürece aralık kadar günde bir tekrarlar.',
        };
    }

    public function timingIntro(): string
    {
        return $this->isInstantaneous()
            ? 'Bu olay kayıt anında bulunur. Hemen gidebilir veya aynı gün seçilen saati bekleyebilir.'
            : 'Bu olay periyodik taramayla bulunur. Gün ofseti pencereyi belirler; mail taramanın o gün seçilen saatinde gider.';
    }

    public function offsetHint(): string
    {
        return match ($this) {
            self::InvoiceDueApproaching,
            self::SubscriptionExpiryApproaching => 'Hedef tarihten kaç gün önce pencere başlasın (7 = bir hafta kala).',
            self::InvoiceOverdue => 'Vadeden kaç gün sonra başlasın (0 = vade ertesi).',
            self::InvoiceInterestClosure => 'Vadeden kaç gün sonra başlasın (30 = bir ay).',
            self::SubscriptionExpired => 'Bitişten kaç gün sonra başlasın (0 = bitiş günü).',
            default => 'Pencerenin kaç gün önce/sonra başlasın.',
        };
    }

    /**
     * @return list<self>
     */
    public static function selectableCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $type): bool => $type->isSelectable(),
        ));
    }

    /**
     * @return array<string, array{
     *     instant: bool,
     *     show_auto_renew: bool,
     *     offset_hint: string,
     *     timing_intro: string,
     *     default_mode: string,
     *     allowed_modes: list<string>,
     *     default_dedupe: string,
     *     allowed_dedupe: list<string>,
     *     dedupe_intro: string,
     *     dedupe_options: list<array{value: string, label: string}>
     * }>
     */
    public static function formMeta(): array
    {
        $meta = [];
        foreach (self::selectableCases() as $type) {
            $meta[$type->value] = [
                'instant' => $type->isInstantaneous(),
                'show_auto_renew' => $type->showsAutoRenewCondition(),
                'offset_hint' => $type->offsetHint(),
                'timing_intro' => $type->timingIntro(),
                'default_mode' => $type->defaultTimingMode()->value,
                'allowed_modes' => array_map(
                    fn (TimingMode $mode): string => $mode->value,
                    $type->allowedTimingModes(),
                ),
                'default_dedupe' => $type->defaultDedupePolicy()->value,
                'allowed_dedupe' => array_map(
                    fn (DedupePolicy $policy): string => $policy->value,
                    $type->allowedDedupePolicies(),
                ),
                'dedupe_intro' => $type->dedupeIntro(),
                'dedupe_options' => array_map(
                    fn (DedupePolicy $policy): array => [
                        'value' => $policy->value,
                        'label' => $type->dedupeOptionLabel($policy),
                    ],
                    $type->allowedDedupePolicies(),
                ),
            ];
        }

        return $meta;
    }
}
