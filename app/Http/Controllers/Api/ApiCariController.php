<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CariResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Cari;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiCariController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'tax_number' => ['required', 'string'],
            'country_code' => ['nullable', 'string', 'size:2'],
        ]);

        $countryCode = $request->country_code ?? 'TR';
        $taxNumber = $request->tax_number;

        $cari = Cari::where('country_code', $countryCode)
            ->where('tax_number', $taxNumber)
            ->first();

        if (!$cari) {
            return response()->json([
                'success' => false,
                'message' => 'Cari not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new CariResource($cari),
        ]);
    }

    public function subscriptions(Cari $cari): JsonResponse
    {
        $subscriptions = Subscription::where('customer_cari_id', $cari->id)
            ->with(['customerCari', 'providerCari', 'serviceProvider', 'product'])
            ->latest('baslangic_tarihi')
            ->get();

        return response()->json([
            'success' => true,
            'data' => SubscriptionResource::collection($subscriptions),
        ]);
    }

    public function upcomingRenewals(Cari $cari): JsonResponse
    {
        $upcomingRenewals = Subscription::where('customer_cari_id', $cari->id)
            ->where('durum', Subscription::DURUM_ACTIVE)
            ->where('auto_renew', true)
            ->whereNotNull('bitis_tarihi')
            ->where('bitis_tarihi', '>=', Carbon::now())
            ->where('bitis_tarihi', '<=', Carbon::now()->addDays(30))
            ->with(['customerCari', 'providerCari', 'serviceProvider', 'product'])
            ->orderBy('bitis_tarihi')
            ->get();

        return response()->json([
            'success' => true,
            'data' => SubscriptionResource::collection($upcomingRenewals),
        ]);
    }
}
