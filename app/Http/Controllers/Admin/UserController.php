<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->orderBy('name')
            ->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', ['user' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:user,admin'],
            'is_active' => ['required', 'boolean'],
        ]);

        $user->update([
            'role' => $validated['role'],
            'is_active' => (bool) $validated['is_active'],
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Kullanıcı güncellendi.');
    }
}
