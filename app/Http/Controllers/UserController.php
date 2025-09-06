<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request)
    {
        $query = User::with('roles');
        $query->where('id', '!=', auth()->id());

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->filled('role') && $request->role !== 'all') {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        return Inertia::render('Users/Index', [
            'users' => $query->orderby('name')->paginate(10)->withQueryString(),
            'roles' => Role::all(),
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    public function create()
    {
        $rolesToManage = ['superadmin', 'admin'];
        $roles = Role::whereIn('slug', $rolesToManage)->get();

        return Inertia::render('Users/Create', [
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'roles' => ['required', 'array', Rule::in(Role::pluck('id'))],
        ]);

        $this->userService->createUser($validatedData);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan!');
    }

    public function edit(User $user)
    {
        $user->load('roles');
        $rolesToManage = ['superadmin', 'admin'];
        $roles = Role::whereIn('slug', $rolesToManage)->get();

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'roles' => $roles
        ]);
    }

    public function update(Request $request, User $user)
    {
        if ($user->auth_provider === 'sevima') {
            // Kita hanya izinkan update role, bukan nama atau email
            $validatedData = $request->validate([
                'roles' => ['required', 'array', Rule::in(Role::pluck('id'))],
            ]);
            $user->roles()->sync($validatedData['roles']);
            return redirect()->route('users.index')->with('success', 'Peran pengguna berhasil diperbarui!');
        }

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'roles' => ['required', 'array', Rule::in(Role::pluck('id'))],
        ]);

        $this->userService->updateUser($user, $validatedData);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil diperbarui!');
    }

    public function destroy(User $user)
    {
        if (!$this->userService->deleteUser($user)) {
            return redirect()->route('users.index')->with('error', 'Pengguna yang disinkronisasi dari SIAKAD Sevima tidak dapat dihapus.');
        }

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus!');
    }

    public function resetPassword(Request $request, User $user)
    {
        if (!$this->userService->resetUserPassword($user)) {
            return redirect()->route('users.index')->with('error', 'Pengguna yang disinkronisasi dari SIAKAD Sevima tidak dapat direset kata sandinya.');
        }

        return redirect()->route('users.index')->with('success', 'Kata sandi pengguna berhasil direset!');
    }
}
