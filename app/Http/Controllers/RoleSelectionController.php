<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RoleSelectionController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        return Inertia::render('Auth/SelectRole', [
            'userRoles' => $user->roles
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id']
        ]);

        $user = Auth::user();

        if (!$user->roles->contains('id', $validated['role_id'])) {
            return redirect()->back()->with('error', 'Peran yang dipilih tidak valid.');
        }

        $request->session()->put('active_role_id', $validated['role_id']);
        return redirect()->route('dashboard');
    }
}
