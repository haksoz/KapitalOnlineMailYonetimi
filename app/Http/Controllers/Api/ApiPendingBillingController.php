<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PendingBillingResource;
use App\Models\Cari;
use App\Models\PendingBilling;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiPendingBillingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PendingBilling::query()
            ->with(['subscription.customerCari', 'subscription.providerCari', 'subscription.serviceProvider', 'subscription.product']);

        if ($request->filled('subscription_id')) {
            $query->where('subscription_id', $request->subscription_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $pendingBillings = $query->latest('period_start')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => PendingBillingResource::collection($pendingBillings),
            'meta' => [
                'current_page' => $pendingBillings->currentPage(),
                'last_page' => $pendingBillings->lastPage(),
                'per_page' => $pendingBillings->perPage(),
                'total' => $pendingBillings->total(),
            ],
        ]);
    }

    public function show(PendingBilling $pendingBilling): JsonResponse
    {
        $pendingBilling->load(['subscription.customerCari', 'subscription.providerCari', 'subscription.serviceProvider', 'subscription.product']);

        return response()->json([
            'success' => true,
            'data' => new PendingBillingResource($pendingBilling),
        ]);
    }

    public function indexByCari(Request $request, Cari $cari): JsonResponse
    {
        $query = PendingBilling::query()
            ->whereHas('subscription', function ($q) use ($cari) {
                $q->where('customer_cari_id', $cari->id);
            })
            ->with(['subscription.customerCari', 'subscription.providerCari', 'subscription.serviceProvider', 'subscription.product']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $pendingBillings = $query->latest('period_start')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => PendingBillingResource::collection($pendingBillings),
            'meta' => [
                'current_page' => $pendingBillings->currentPage(),
                'last_page' => $pendingBillings->lastPage(),
                'per_page' => $pendingBillings->perPage(),
                'total' => $pendingBillings->total(),
            ],
        ]);
    }
}
