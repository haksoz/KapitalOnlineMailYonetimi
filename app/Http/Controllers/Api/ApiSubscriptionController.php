<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiSubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Subscription::query()
            ->with(['customerCari', 'providerCari', 'serviceProvider', 'product']);

        if ($request->filled('customer_cari_id')) {
            $query->where('customer_cari_id', $request->customer_cari_id);
        }

        if ($request->filled('durum')) {
            $query->where('durum', $request->durum);
        }

        $subscriptions = $query->latest('baslangic_tarihi')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => SubscriptionResource::collection($subscriptions),
            'meta' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
            ],
        ]);
    }

    public function show(Subscription $subscription): JsonResponse
    {
        $subscription->load(['customerCari', 'providerCari', 'serviceProvider', 'product']);

        return response()->json([
            'success' => true,
            'data' => new SubscriptionResource($subscription),
        ]);
    }
}
