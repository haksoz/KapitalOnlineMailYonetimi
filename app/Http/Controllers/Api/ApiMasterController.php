<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Integration\ProductIntegrationResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\PendingBilling;
use App\Models\SalesInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiMasterController extends Controller
{
    /**
     * Ürün Listesi (Satış Kanalı için)
     * Sadece satış USD fiyatları içerir
     */
    public function products(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with('serviceProvider:id,name,code')
            ->orderBy('name');

        // Filtreleme
        if ($request->filled('service_provider_id')) {
            $query->where('service_provider_id', $request->service_provider_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('stock_code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $query->paginate(50);

        return response()->json([
            'success' => true,
            'data' => ProductIntegrationResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Kullanıcı Bazlı Abonelikler
     * Master platform müşterinin aboneliklerini takip eder
     */
    public function subscriptions(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:caris,id',
            'status' => 'nullable|in:active,cancelled,pending,expired',
        ]);

        $query = Subscription::query()
            ->with(['customerCari', 'providerCari', 'serviceProvider', 'product'])
            ->where('customer_cari_id', $request->user_id);

        // Durum filtresi
        if ($request->filled('status')) {
            $query->where('durum', $request->status);
        }

        // Aktif abonelikler (bitiş tarihi geçmemiş)
        if ($request->get('active_only', false)) {
            $query->where('bitis_tarihi', '>=', now()->format('Y-m-d'));
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

    /**
     * Kullanıcı Bazlı Siparişler (Açık + Faturalanmış)
     * Master platform müşterinin borçlandırma bilgilerini alır
     */
    public function orders(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:caris,id',
            'status' => 'nullable|in:pending,invoiced,all',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $status = $request->get('status', 'all');
        $orders = collect();
        $meta = [];

        if ($status === 'pending' || $status === 'all') {
            // Pending Billings (Açık siparişler)
            $pendingQuery = PendingBilling::query()
                ->with(['subscription.customerCari', 'subscription.product', 'subscription.serviceProvider'])
                ->whereHas('subscription', function ($q) use ($request) {
                    $q->where('customer_cari_id', $request->user_id);
                });

            if ($request->filled('date_from')) {
                $pendingQuery->where('period_start', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $pendingQuery->where('period_end', '<=', $request->date_to);
            }

            $pendingBillings = $pendingQuery->latest('period_start')->get();
            $orders = $orders->merge($pendingBillings->map(function ($billing) {
                return [
                    'id' => $billing->id,
                    'type' => 'pending',
                    'order_number' => 'PB-' . str_pad($billing->id, 6, '0', STR_PAD_LEFT),
                    'period_start' => $billing->period_start,
                    'period_end' => $billing->period_end,
                    'status' => $billing->status,
                    'amount_tl' => $billing->actual_satis_tl ?? $billing->expected_satis_tl,
                    'amount_usd' => $billing->actual_satis_usd ?? $billing->expected_satis_usd,
                    'currency' => 'TL',
                    'due_date' => $billing->period_end,
                    'description' => $billing->subscription->product->name ?? 'Bilinmeyen Ürün',
                    'subscription_id' => $billing->subscription_id,
                    'customer' => $billing->subscription->customerCari,
                    'product' => $billing->subscription->product,
                    'service_provider' => $billing->subscription->serviceProvider,
                    'created_at' => $billing->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $billing->updated_at->format('Y-m-d H:i:s'),
                ];
            }));
        }

        if ($status === 'invoiced' || $status === 'all') {
            // Sales Invoices (Faturalanmış siparişler)
            $invoiceQuery = SalesInvoice::query()
                ->with(['customerCari', 'lines'])
                ->where('customer_cari_id', $request->user_id);

            if ($request->filled('date_from')) {
                $invoiceQuery->where('our_invoice_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $invoiceQuery->where('our_invoice_date', '<=', $request->date_to);
            }

            $salesInvoices = $invoiceQuery->latest('our_invoice_date')->get();
            $orders = $orders->merge($salesInvoices->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'type' => 'invoiced',
                    'order_number' => $invoice->our_invoice_number,
                    'period_start' => null,
                    'period_end' => null,
                    'status' => 'invoiced',
                    'amount_tl' => $invoice->total_amount_tl,
                    'amount_usd' => null,
                    'currency' => 'TL',
                    'due_date' => $invoice->our_invoice_date,
                    'description' => 'Satış Faturası',
                    'subscription_id' => null,
                    'customer' => $invoice->customerCari,
                    'product' => null,
                    'service_provider' => null,
                    'lines' => $invoice->lines,
                    'created_at' => $invoice->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $invoice->updated_at->format('Y-m-d H:i:s'),
                ];
            }));
        }

        // Tarihe göre sıralama
        $orders = $orders->sortByDesc('created_at')->values();

        // Manuel pagination
        $page = $request->get('page', 1);
        $perPage = 50;
        $total = $orders->count();
        $lastPage = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginatedOrders = $orders->slice($offset, $perPage)->values();

        $meta = [
            'current_page' => (int) $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
        ];

        return response()->json([
            'success' => true,
            'data' => $paginatedOrders,
            'meta' => $meta,
        ]);
    }

    /**
     * Abonelik Durum Özeti
     * Master platform için hızlı durum kontrolü
     */
    public function subscriptionSummary(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:caris,id',
        ]);

        $userId = $request->user_id;

        $activeCount = Subscription::where('customer_cari_id', $userId)
            ->where('durum', 'active')
            ->where('bitis_tarihi', '>=', now()->format('Y-m-d'))
            ->count();

        $expiredCount = Subscription::where('customer_cari_id', $userId)
            ->where('bitis_tarihi', '<', now()->format('Y-m-d'))
            ->count();

        $autoRenewDisabledCount = Subscription::where('customer_cari_id', $userId)
            ->where('durum', 'active')
            ->where('auto_renew', false)
            ->where('bitis_tarihi', '>=', now()->format('Y-m-d'))
            ->count();

        $upcomingRenewalsCount = Subscription::where('customer_cari_id', $userId)
            ->where('durum', 'active')
            ->where('auto_renew', true)
            ->whereBetween('bitis_tarihi', [now()->format('Y-m-d'), now()->addDays(30)->format('Y-m-d')])
            ->count();

        $pendingOrdersCount = PendingBilling::whereHas('subscription', function ($q) use ($userId) {
            $q->where('customer_cari_id', $userId);
        })->where('status', 'pending')->count();

        $invoicedOrdersCount = SalesInvoice::where('customer_cari_id', $userId)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'subscriptions' => [
                    'active' => $activeCount,
                    'expired' => $expiredCount,
                    'auto_renew_disabled' => $autoRenewDisabledCount,
                    'upcoming_renewals' => $upcomingRenewalsCount,
                ],
                'orders' => [
                    'pending' => $pendingOrdersCount,
                    'invoiced' => $invoicedOrdersCount,
                ],
            ],
        ]);
    }
}
