<?php

namespace App\Http\Controllers;

use App\Models\Cari;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CariController extends Controller
{
    public function index(Request $request): View
    {
        $caris = Cari::query()
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('caris.index', compact('caris'));
    }

    public function create(): View
    {
        return view('caris.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'short_name'   => ['nullable', 'string', 'max:100'],
            'email'        => ['nullable', 'email', 'max:255'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'tax_number'   => ['nullable', 'string', 'max:50'],
            'cari_type'    => ['nullable', 'string', 'max:32'],
        ]);

        // Varsayılan ülke kodu uygulama tarafında da korunsun
        if (empty($validated['country_code'])) {
            $validated['country_code'] = 'TR';
        }

        Cari::create($validated);

        return redirect()
            ->route('caris.index')
            ->with('success', 'Cari eklendi.');
    }

    public function edit(Cari $cari): View
    {
        return view('caris.edit', compact('cari'));
    }

    public function update(Request $request, Cari $cari): RedirectResponse
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'short_name'   => ['nullable', 'string', 'max:100'],
            'email'        => ['nullable', 'email', 'max:255'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'tax_number'   => ['nullable', 'string', 'max:50'],
            'cari_type'    => ['nullable', 'string', 'max:32'],
        ]);

        if (empty($validated['country_code'])) {
            $validated['country_code'] = 'TR';
        }

        $cari->update($validated);

        return redirect()
            ->route('caris.index')
            ->with('success', 'Cari güncellendi.');
    }

    public function destroy(Cari $cari): RedirectResponse
    {
        $cari->delete();

        return redirect()
            ->route('caris.index')
            ->with('success', 'Cari silindi.');
    }
}
