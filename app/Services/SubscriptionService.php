<?php

namespace App\Services;

use App\Contracts\SubscriptionRepositoryInterface;
use App\Models\Subscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SubscriptionService
{
    public function __construct(
        protected SubscriptionRepositoryInterface $subscriptionRepository
    ) {}

    public function getActiveSubscriptions(int $perPage = 15): LengthAwarePaginator
    {
        return $this->subscriptionRepository->getActiveList($perPage);
    }

    public function getByCompany(int $companyId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->subscriptionRepository->getByCompany($companyId, $perPage);
    }

    public function findBySozlesmeNo(string $sozlesmeNo): ?Subscription
    {
        return $this->subscriptionRepository->getBySozlesmeNo($sozlesmeNo);
    }

    public function create(array $data): Subscription
    {
        return $this->subscriptionRepository->create($data);
    }

    public function update(Subscription $subscription, array $data): bool
    {
        return $this->subscriptionRepository->update($subscription, $data);
    }
}
