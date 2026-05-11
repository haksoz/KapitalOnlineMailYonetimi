<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalesInvoiceResource;
use App\Models\Cari;
use App\Models\SalesInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiSalesInvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SalesInvoice::query()
            ->with(['customerCari', 'lines.pendingBilling']);

        if ($request->filled('customer_cari_id')) {
            $query->where('customer_cari_id', $request->customer_cari_id);
        }

        $salesInvoices = $query->latest('our_invoice_date')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => SalesInvoiceResource::collection($salesInvoices),
            'meta' => [
                'current_page' => $salesInvoices->currentPage(),
                'last_page' => $salesInvoices->lastPage(),
                'per_page' => $salesInvoices->perPage(),
                'total' => $salesInvoices->total(),
            ],
        ]);
    }

    public function show(SalesInvoice $salesInvoice): JsonResponse
    {
        $salesInvoice->load(['customerCari', 'lines.pendingBilling']);

        return response()->json([
            'success' => true,
            'data' => new SalesInvoiceResource($salesInvoice),
        ]);
    }

    public function indexByCari(Request $request, Cari $cari): JsonResponse
    {
        $query = SalesInvoice::query()
            ->where('customer_cari_id', $cari->id)
            ->with(['customerCari', 'lines.pendingBilling']);

        $salesInvoices = $query->latest('our_invoice_date')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => SalesInvoiceResource::collection($salesInvoices),
            'meta' => [
                'current_page' => $salesInvoices->currentPage(),
                'last_page' => $salesInvoices->lastPage(),
                'per_page' => $salesInvoices->perPage(),
                'total' => $salesInvoices->total(),
            ],
        ]);
    }
}
