<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cari;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationCariController extends Controller
{
    public function index(Request $request): View
    {
        $query = Cari::query()->whereIn('cari_type', ['customer', 'both']);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search): void {
                $q->where('short_name', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        $caris = $query->orderBy('name')->paginate(30)->withQueryString();

        return view('admin.notifications.caris', compact('caris'));
    }

    public function update(Request $request, Cari $cari): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['nullable', 'email', 'max:255'],
            'notifications_enabled' => ['nullable', 'boolean'],
        ]);

        $email = filled($validated['email'] ?? null) ? $validated['email'] : null;
        $cari->update([
            'email' => $email,
            'notifications_enabled' => $request->boolean('notifications_enabled') && filled($email),
        ]);

        return redirect()
            ->route('admin.notifications.caris.index', $request->only('search'))
            ->with('success', ($cari->short_name ?: $cari->name).' bildirim ayarı kaydedildi.');
    }
}
