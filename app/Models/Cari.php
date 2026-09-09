<?php

namespace App\Models;

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
    ];

    protected function casts(): array
    {
        return [
            'notifications_enabled' => 'boolean',
        ];
    }

    public function canReceiveNotifications(): bool
    {
        return $this->notifications_enabled && filled($this->email);
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
            if (blank($cari->email)) {
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
