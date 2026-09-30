<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ServiceProvider;
use App\Http\Resources\ProductResource;
use Closure;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::query()
            ->with('serviceProvider:id,name,code');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('stock_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('currency') && in_array($request->currency, [Product::CURRENCY_USD, Product::CURRENCY_TRY], true)) {
            $query->where('currency', $request->currency);
        }

        $products = $query
            ->orderByDesc('id')
            ->paginate(50)
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
        $validated = $this->validateProduct($request);

        Product::create($validated);

        return redirect()->route('products.index')->with('success', 'Ürün eklendi.');
    }

    public function show(Product $product): View
    {
        $product->load([
            'serviceProvider:id,name,code',
            'priceHistories' => fn ($q) => $q->with('changedBy:id,name')->limit(10),
        ]);
        return view('products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $serviceProviders = ServiceProvider::orderBy('name')->get(['id', 'name', 'code']);
        return view('products.edit', compact('product', 'serviceProviders'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validateProduct($request, $product);

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

    /**
     * @return array<string, mixed>
     */
    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $stockCode = $request->input('stock_code');
        if (is_string($stockCode)) {
            $stockCode = trim($stockCode);
            $request->merge([
                'stock_code' => $stockCode === '' ? null : $stockCode,
            ]);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'stock_code' => [
                'nullable',
                'string',
                'max:64',
                function (string $attribute, mixed $value, Closure $fail) use ($product): void {
                    if (! is_string($value) || $value === '') {
                        return;
                    }

                    $exists = Product::query()
                        ->whereRaw('LOWER(stock_code) = ?', [mb_strtolower($value)])
                        ->when($product, fn ($query) => $query->whereKeyNot($product->id))
                        ->exists();

                    if ($exists) {
                        $fail('Bu stok kodu başka bir üründe kayıtlı.');
                    }
                },
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'currency' => ['required', 'string', 'in:USD,TRY'],
            'service_provider_id' => ['nullable', 'exists:service_providers,id'],
            'alis_usd_monthly_commitment' => ['nullable', 'numeric', 'min:0'],
            'satis_usd_monthly_commitment' => ['nullable', 'numeric', 'min:0'],
            'alis_usd_monthly_no_commitment' => ['nullable', 'numeric', 'min:0'],
            'satis_usd_monthly_no_commitment' => ['nullable', 'numeric', 'min:0'],
            'alis_usd_yearly_commitment' => ['nullable', 'numeric', 'min:0'],
            'satis_usd_yearly_commitment' => ['nullable', 'numeric', 'min:0'],
        ]);
    }
}
