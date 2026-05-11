<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ServiceProvider;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with('serviceProvider:id,name,code')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('products.index', compact('products'));
    }

    public function create(): View
    {
        $serviceProviders = ServiceProvider::orderBy('name')->get(['id', 'name', 'code']);
        return view('products.create', compact('serviceProviders'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'stock_code' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:500'],
            'service_provider_id' => ['nullable', 'exists:service_providers,id'],
            'alis_usd_monthly_commitment' => ['nullable', 'numeric', 'min:0'],
            'satis_usd_monthly_commitment' => ['nullable', 'numeric', 'min:0'],
            'alis_usd_monthly_no_commitment' => ['nullable', 'numeric', 'min:0'],
            'satis_usd_monthly_no_commitment' => ['nullable', 'numeric', 'min:0'],
            'alis_usd_yearly_commitment' => ['nullable', 'numeric', 'min:0'],
            'satis_usd_yearly_commitment' => ['nullable', 'numeric', 'min:0'],
        ]);

        Product::create($validated);

        return redirect()->route('products.index')->with('success', 'Ürün eklendi.');
    }

    public function show(Product $product): View
    {
        $product->load('serviceProvider:id,name,code');
        return view('products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $serviceProviders = ServiceProvider::orderBy('name')->get(['id', 'name', 'code']);
        return view('products.edit', compact('product', 'serviceProviders'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'stock_code' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:500'],
            'service_provider_id' => ['nullable', 'exists:service_providers,id'],
            'alis_usd_monthly_commitment' => ['nullable', 'numeric', 'min:0'],
            'satis_usd_monthly_commitment' => ['nullable', 'numeric', 'min:0'],
            'alis_usd_monthly_no_commitment' => ['nullable', 'numeric', 'min:0'],
            'satis_usd_monthly_no_commitment' => ['nullable', 'numeric', 'min:0'],
            'alis_usd_yearly_commitment' => ['nullable', 'numeric', 'min:0'],
            'satis_usd_yearly_commitment' => ['nullable', 'numeric', 'min:0'],
        ]);

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Ürün güncellendi.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Ürün silindi.');
    }

    public function api(Request $request): JsonResponse
    {
        $products = Product::query()
            ->with('serviceProvider:id,name,code')
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }
}
