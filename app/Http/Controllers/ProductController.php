<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

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
        ]);

        Product::create($validated);

        return redirect()->route('products.index')->with('success', 'Ürün eklendi.');
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
        ]);

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Ürün güncellendi.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Ürün silindi.');
    }
}
