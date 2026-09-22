<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Cari extends Model
{
    protected $table = 'caris';

    protected $fillable = [
        'name',
        'short_name',
        'email',
        'notifications_enabled',
        'country_code',
        'tax_number',
        'cari_type',
        'odeme_vadesi_gun',
    ];

    protected function casts(): array
    {
        return [
            'notifications_enabled' => 'boolean',
            'odeme_vadesi_gun' => 'integer',
        ];
    }

    public function hasPaymentTerm(): bool
    {
        return $this->odeme_vadesi_gun !== null;
    }

    public function dueDateFrom(?CarbonInterface $documentDate): ?CarbonInterface
    {
        if ($documentDate === null) {
            return null;
        }

        $days = $this->odeme_vadesi_gun ?? 0;

        return Carbon::parse($documentDate->toDateString())->addDays((int) $days);
    }

    /**
     * @return list<string>
     */
    public function notificationEmails(): array
    {
        return self::parseEmails($this->email);
    }

    /**
     * @return list<string>
     */
    public static function parseEmails(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $parts = preg_split('/\s*,\s*/', $value) ?: [];
        $unique = [];
        $seen = [];
        foreach ($parts as $part) {
            $email = trim($part);
            if ($email === '') {
                continue;
            }
            $key = strtolower($email);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $email;
        }

        return $unique;
    }

    public static function normalizeEmailList(?string $value): ?string
    {
        $emails = self::parseEmails($value);

        return $emails === [] ? null : implode(', ', $emails);
    }

    public function canReceiveNotifications(): bool
    {
        return $this->notifications_enabled && $this->notificationEmails() !== [];
    }

    public function scopeReceivesNotifications(Builder $query): void
    {
        $query->where('notifications_enabled', true)
            ->whereNotNull('email')
            ->where('email', '!=', '');
    }

    protected static function booted(): void
    {
        static::creating(function (Cari $cari): void {
            if (empty($cari->uuid)) {
                $cari->uuid = (string) Str::uuid();
            }

            if (empty($cari->country_code)) {
                $cari->country_code = 'TR';
            }
        });

        static::saving(function (Cari $cari): void {
            $cari->email = self::normalizeEmailList($cari->email);
            if ($cari->notificationEmails() === []) {
                $cari->notifications_enabled = false;
            }
        });
    }

    public function subscriptionsAsCustomer(): HasMany
    {
        return $this->hasMany(Subscription::class, 'customer_cari_id');
    }

    public function subscriptionsAsProvider(): HasMany
    {
        return $this->hasMany(Subscription::class, 'provider_cari_id');
    }

    public function supplierInvoices(): HasMany
    {
        return $this->hasMany(SupplierInvoice::class, 'cari_id');
    }
}
