<?php

namespace App\Repositories;

use App\Contracts\SubscriptionRepositoryInterface;
use App\Models\Subscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function find(int $id): ?Subscription
    {
        return Subscription::find($id);
    }

    public function getByCustomerCari(int $cariId, int $perPage = 15): LengthAwarePaginator
    {
        return Subscription::query()
            ->where('customer_cari_id', $cariId)
            ->with(['customerCari', 'providerCari', 'product', 'serviceProvider'])
            ->latest('baslangic_tarihi')
            ->paginate($perPage);
    }

    public function getBySozlesmeNo(string $sozlesmeNo): ?Subscription
    {
        return Subscription::query()
            ->where('sozlesme_no', $sozlesmeNo)
            ->with(['customerCari', 'providerCari', 'product'])
            ->first();
    }

    public function getActiveList(int $perPage = 15): LengthAwarePaginator
    {
        return Subscription::query()
            ->where('durum', Subscription::DURUM_ACTIVE)
            ->with([
                'customerCari:id,name,short_name,uuid',
                'providerCari:id,name,short_name,uuid',
                'product:id,name,stock_code',
                'serviceProvider:id,name,code',
            ])
            ->latest('baslangic_tarihi')
            ->paginate($perPage);
    }

    public function create(array $data): Subscription
    {
        return Subscription::create($data);
    }

    public function update(Subscription $subscription, array $data): bool
    {
        return $subscription->update($data);
    }
}
