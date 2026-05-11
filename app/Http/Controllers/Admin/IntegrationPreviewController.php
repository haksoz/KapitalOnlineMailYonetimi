<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Integration\CariIntegrationResource;
use App\Http\Resources\Integration\InvoicedOrderIntegrationResource;
use App\Http\Resources\Integration\PendingBillingIntegrationResource;
use App\Http\Resources\Integration\ProductIntegrationResource;
use App\Http\Resources\Integration\SubscriptionIntegrationResource;
use App\Models\Cari;
use App\Models\ExchangeRate;
use App\Models\PendingBilling;
use App\Models\Product;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IntegrationPreviewController extends Controller
{
    public function cariIndex(Request $request): View
    {
        $caris = Cari::query()
            ->orderBy('name')
            ->paginate(20);

        return view('admin.integration.cari-preview', compact('caris'));
    }

    public function cariData(Request $request): JsonResponse
    {
        $caris = Cari::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('tax_number', 'like', '%' . $request->search . '%');
            })
            ->orderBy('name')
            ->paginate(20);

        $resource = CariIntegrationResource::collection($caris);

        return response()->json([
            'data' => $resource->toArray($request),
            'meta' => [
                'total' => $caris->total(),
                'per_page' => $caris->perPage(),
                'current_page' => $caris->currentPage(),
                'last_page' => $caris->lastPage(),
            ],
        ]);
    }

    public function subscriptionIndex(Request $request): View
    {
        $subscriptions = Subscription::query()
            ->with(['customerCari', 'providerCari', 'product'])
            ->orderByDesc('baslangic_tarihi')
            ->paginate(20);

        return view('admin.integration.subscription-preview', compact('subscriptions'));
    }

    public function subscriptionData(Request $request): JsonResponse
    {
        $subscriptions = Subscription::query()
            ->with(['customerCari', 'providerCari', 'product'])
            ->when($request->filled('customer_cari_id'), function ($q) use ($request) {
                $q->where('customer_cari_id', $request->customer_cari_id);
            })
            ->when($request->filled('durum'), function ($q) use ($request) {
                $q->where('durum', $request->durum);
            })
            ->orderByDesc('baslangic_tarihi')
            ->paginate(20);

        $resource = SubscriptionIntegrationResource::collection($subscriptions);

        return response()->json([
            'data' => $resource->toArray($request),
            'meta' => [
                'total' => $subscriptions->total(),
                'per_page' => $subscriptions->perPage(),
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
            ],
        ]);
    }

    public function openOrderIndex(Request $request): View
    {
        return view('admin.integration.open-order-preview');
    }

    public function openOrderData(Request $request): JsonResponse
    {
        $query = PendingBilling::query()
            ->with([
                'subscription.customerCari',
                'subscription.providerCari',
                'subscription.product',
            ])
            ->whereIn('status', [
                PendingBilling::STATUS_PENDING,
                PendingBilling::STATUS_POSTPONED,
                PendingBilling::STATUS_CANCELLED,
            ])
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->filled('period_year'), function ($q) use ($request) {
                $q->whereYear('period_start', $request->period_year);
            })
            ->when($request->filled('period_month'), function ($q) use ($request) {
                $q->whereMonth('period_start', $request->period_month);
            })
            ->orderByDesc('period_start');

        $billings = $query->paginate(20);

        $latestRate = ExchangeRate::where('currency_code', 'USD')
            ->whereNotNull('forex_selling')
            ->orderByDesc('effective_date')
            ->value('forex_selling');

        foreach ($billings as $billing) {
            $billing->latestExchangeRate = $latestRate;
        }

        $resource = PendingBillingIntegrationResource::collection($billings);

        return response()->json([
            'data' => $resource->toArray($request),
            'meta' => [
                'total' => $billings->total(),
                'per_page' => $billings->perPage(),
                'current_page' => $billings->currentPage(),
                'last_page' => $billings->lastPage(),
            ],
        ]);
    }

    public function invoicedOrderIndex(Request $request): View
    {
        return view('admin.integration.invoiced-order-preview');
    }

    public function invoicedOrderData(Request $request): JsonResponse
    {
        $query = PendingBilling::query()
            ->with([
                'subscription.customerCari',
                'subscription.providerCari',
                'subscription.product',
                'salesInvoiceLine.salesInvoice',
            ])
            ->where('status', PendingBilling::STATUS_INVOICED)
            ->when($request->filled('period_year'), function ($q) use ($request) {
                $q->whereYear('period_start', $request->period_year);
            })
            ->when($request->filled('period_month'), function ($q) use ($request) {
                $q->whereMonth('period_start', $request->period_month);
            })
            ->orderByDesc('period_start');

        $billings = $query->paginate(20);

        $resource = InvoicedOrderIntegrationResource::collection($billings);

        return response()->json([
            'data' => $resource->toArray($request),
            'meta' => [
                'total' => $billings->total(),
                'per_page' => $billings->perPage(),
                'current_page' => $billings->currentPage(),
                'last_page' => $billings->lastPage(),
            ],
        ]);
    }

    public function productIndex(Request $request): View
    {
        return view('admin.integration.product-preview');
    }

    public function productData(Request $request): JsonResponse
    {
        $products = Product::query()
            ->with('serviceProvider:id,name,code')
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('stock_code', 'like', '%' . $request->search . '%');
            })
            ->orderBy('name')
            ->paginate(20);

        $resource = ProductIntegrationResource::collection($products);

        return response()->json([
            'data' => $resource->toArray($request),
            'meta' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }
}
