<?php

namespace App\Http\Controllers;

use App\Models\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServiceProviderController extends Controller
{
    public function index(Request $request): View
    {
        $serviceProviders = ServiceProvider::query()
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('service-providers.index', compact('serviceProviders'));
    }

    public function create(): View
    {
        return view('service-providers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:64', Rule::unique('service_providers', 'code')],
            'service_types' => ['nullable', 'array'],
            'service_types.*' => ['string', 'max:50'],
        ]);
        $validated['service_types'] = $request->input('service_types', []);

        ServiceProvider::create($validated);

        return redirect()->route('service-providers.index')->with('success', 'Servis sağlayıcı eklendi.');
    }

    public function edit(ServiceProvider $serviceProvider): View
    {
        return view('service-providers.edit', compact('serviceProvider'));
    }

    public function update(Request $request, ServiceProvider $serviceProvider): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:64', Rule::unique('service_providers', 'code')->ignore($serviceProvider->id)],
            'service_types' => ['nullable', 'array'],
            'service_types.*' => ['string', 'max:50'],
        ]);
        $validated['service_types'] = $request->input('service_types', []);

        $serviceProvider->update($validated);

        return redirect()->route('service-providers.index')->with('success', 'Servis sağlayıcı güncellendi.');
    }

    public function destroy(ServiceProvider $serviceProvider): RedirectResponse
    {
        $serviceProvider->delete();
        return redirect()->route('service-providers.index')->with('success', 'Servis sağlayıcı silindi.');
    }
}
