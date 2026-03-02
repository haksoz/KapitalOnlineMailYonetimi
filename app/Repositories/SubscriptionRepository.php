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

    public function getByCompany(int $companyId, int $perPage = 15): LengthAwarePaginator
    {
        return Subscription::query()
            ->where('company_id', $companyId)
            ->with(['company', 'product', 'supplier', 'serviceProvider'])
            ->latest('baslangic_tarihi')
            ->paginate($perPage);
    }

    public function getBySozlesmeNo(string $sozlesmeNo): ?Subscription
    {
        return Subscription::query()
            ->where('sozlesme_no', $sozlesmeNo)
            ->with(['company', 'product', 'supplier'])
            ->first();
    }

    public function getActiveList(int $perPage = 15): LengthAwarePaginator
    {
        return Subscription::query()
            ->where('durum', Subscription::DURUM_ACTIVE)
            ->with(['company:id,name,code', 'product:id,name,stock_code', 'supplier:id,name'])
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
