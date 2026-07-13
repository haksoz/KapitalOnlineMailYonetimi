<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductPriceHistory;
use App\Models\SubscriptionPriceHistory;
use App\Models\SubscriptionQuantityHistory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $entityId = $request->input('entity_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $productId = $request->input('product_id');
        $subscriptionId = $request->input('subscription_id');

        $productPriceLogs = ProductPriceHistory::query()
            ->with(['product:id,name', 'changedBy:id,name'])
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->when($entityId, fn ($q) => $q->where('product_id', $entityId))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->latest('created_at')
            ->paginate(25, ['*'], 'product_price_page')
            ->withQueryString();

        $subscriptionPriceLogs = SubscriptionPriceHistory::query()
            ->with(['subscription:id,sozlesme_no', 'changedBy:id,name'])
            ->when($subscriptionId, fn ($q) => $q->where('subscription_id', $subscriptionId))
            ->when($entityId, fn ($q) => $q->where('subscription_id', $entityId))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->latest('created_at')
            ->paginate(25, ['*'], 'subscription_price_page')
            ->withQueryString();

        $subscriptionQuantityLogs = SubscriptionQuantityHistory::query()
            ->with(['subscription:id,sozlesme_no', 'changedBy:id,name'])
            ->when($subscriptionId, fn ($q) => $q->where('subscription_id', $subscriptionId))
            ->when($entityId, fn ($q) => $q->where('subscription_id', $entityId))
            ->when($dateFrom, fn ($q) => $q->whereDate('effective_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('effective_date', '<=', $dateTo))
            ->latest('effective_date')
            ->latest('created_at')
            ->paginate(25, ['*'], 'subscription_quantity_page')
            ->withQueryString();

        return view('admin.activity-logs.index', compact(
            'productPriceLogs',
            'subscriptionPriceLogs',
            'subscriptionQuantityLogs'
        ));
    }
}
