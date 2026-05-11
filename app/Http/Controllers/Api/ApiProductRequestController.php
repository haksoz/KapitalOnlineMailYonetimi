<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductRequestResource;
use App\Models\Cari;
use App\Models\ProductRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiProductRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ProductRequest::query()
            ->with(['cari', 'product']);

        if ($request->filled('cari_id')) {
            $query->where('cari_id', $request->cari_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $productRequests = $query->latest()->paginate(50);

        return response()->json([
            'success' => true,
            'data' => ProductRequestResource::collection($productRequests),
            'meta' => [
                'current_page' => $productRequests->currentPage(),
                'last_page' => $productRequests->lastPage(),
                'per_page' => $productRequests->perPage(),
                'total' => $productRequests->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cari_id' => ['required', 'exists:caris,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        // En az biri olmalı
        if (empty($validated['product_id']) && empty($validated['product_name'])) {
            return response()->json([
                'success' => false,
                'message' => 'Either product_id or product_name is required',
            ], 422);
        }

        $productRequest = ProductRequest::create($validated);
        $productRequest->load(['cari', 'product']);

        return response()->json([
            'success' => true,
            'data' => new ProductRequestResource($productRequest),
        ], 201);
    }

    public function show(ProductRequest $productRequest): JsonResponse
    {
        $productRequest->load(['cari', 'product']);

        return response()->json([
            'success' => true,
            'data' => new ProductRequestResource($productRequest),
        ]);
    }

    public function indexByCari(Request $request, Cari $cari): JsonResponse
    {
        $query = ProductRequest::query()
            ->where('cari_id', $cari->id)
            ->with(['cari', 'product']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $productRequests = $query->latest()->paginate(50);

        return response()->json([
            'success' => true,
            'data' => ProductRequestResource::collection($productRequests),
            'meta' => [
                'current_page' => $productRequests->currentPage(),
                'last_page' => $productRequests->lastPage(),
                'per_page' => $productRequests->perPage(),
                'total' => $productRequests->total(),
            ],
        ]);
    }
}
