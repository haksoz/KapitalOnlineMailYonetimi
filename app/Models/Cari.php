<?php

namespace App\Models;

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
        'country_code',
        'tax_number',
        'cari_type',
    ];

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
