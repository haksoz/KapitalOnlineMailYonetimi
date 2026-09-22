<?php

namespace App\Http\Controllers;

use App\Models\Cari;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CariController extends Controller
{
    public function index(Request $request): View
    {
        $query = Cari::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('short_name', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('tax_number', 'like', "%{$search}%");
            });
        }

        $caris = $query
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
        $validated = $this->validatedCariPayload($request);

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
        $validated = $this->validatedCariPayload($request);

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

    public function quickUpdate(Request $request, Cari $cari): JsonResponse
    {
        $updatingEmail = $request->exists('email');
        $updatingNotify = $request->exists('notifications_enabled');
        $updatingTerm = $request->exists('is_vadeli');

        if (! $updatingEmail && ! $updatingNotify && ! $updatingTerm) {
            return response()->json(['message' => 'Güncellenecek alan yok.'], 422);
        }

        $rules = [];
        if ($updatingEmail) {
            $rules['email'] = ['nullable', 'email', 'max:255'];
        }
        if ($updatingNotify) {
            $rules['notifications_enabled'] = ['boolean'];
        }
        if ($updatingTerm) {
            $rules['is_vadeli'] = ['boolean'];
            $rules['odeme_vadesi_gun'] = [
                $request->boolean('is_vadeli') ? 'required' : 'nullable',
                'integer',
                'min:0',
                'max:3650',
            ];
        }

        $validated = $request->validate($rules);

        $payload = [];
        if ($updatingEmail) {
            $payload['email'] = filled($validated['email'] ?? null) ? $validated['email'] : null;
        }
        if ($updatingNotify || $updatingEmail) {
            $email = array_key_exists('email', $payload) ? $payload['email'] : $cari->email;
            $wantNotify = $updatingNotify
                ? $request->boolean('notifications_enabled')
                : (bool) $cari->notifications_enabled;
            $payload['notifications_enabled'] = $wantNotify && filled($email);
        }
        if ($updatingTerm) {
            $payload['odeme_vadesi_gun'] = $request->boolean('is_vadeli')
                ? (int) $validated['odeme_vadesi_gun']
                : null;
        }

        $cari->update($payload);
        $cari->refresh();

        return response()->json([
            'ok' => true,
            'email' => $cari->email,
            'notifications_enabled' => $cari->notifications_enabled,
            'can_receive_notifications' => $cari->canReceiveNotifications(),
            'odeme_vadesi_gun' => $cari->odeme_vadesi_gun,
            'has_payment_term' => $cari->hasPaymentTerm(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedCariPayload(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'notifications_enabled' => ['required', 'boolean'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'cari_type' => ['nullable', 'string', 'max:32'],
            'is_vadeli' => ['nullable', 'boolean'],
            'odeme_vadesi_gun' => [$request->boolean('is_vadeli') ? 'required' : 'nullable', 'integer', 'min:0', 'max:3650'],
        ]);

        if (empty($validated['country_code'])) {
            $validated['country_code'] = 'TR';
        }

        $validated['email'] = filled($validated['email'] ?? null) ? $validated['email'] : null;
        $validated['notifications_enabled'] = $request->boolean('notifications_enabled')
            && filled($validated['email']);
        $validated['odeme_vadesi_gun'] = $request->boolean('is_vadeli')
            ? (int) $validated['odeme_vadesi_gun']
            : null;
        unset($validated['is_vadeli']);

        return $validated;
    }
}
