<?php

namespace App\Contracts;

use App\Models\Subscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SubscriptionRepositoryInterface
{
    public function find(int $id): ?Subscription;

    public function getByCustomerCari(int $cariId, int $perPage = 15): LengthAwarePaginator;

    public function getBySozlesmeNo(string $sozlesmeNo): ?Subscription;

    public function getActiveList(int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): Subscription;

    public function update(Subscription $subscription, array $data): bool;
}
