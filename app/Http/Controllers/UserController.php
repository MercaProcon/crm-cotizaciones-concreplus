<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    // Copia de Concreplus: sin rol "agente" (era exclusivo del módulo
    // Fachadas de PROCON, que aquí está oculto).
    private const ROLES = ['admin', 'coordinadora', 'vendedora', 'ventanilla'];

    public const ROLE_LABELS = [
        'admin' => 'Admin',
        'coordinadora' => 'Coordinadora',
        'vendedora' => 'Vendedora',
        'ventanilla' => 'Ventanilla',
    ];

    public function index(): View
    {
        $users = User::query()
            ->with('roles')
            ->orderBy('name')
            ->paginate(20);

        return view('users.index', ['users' => $users, 'roleLabels' => self::ROLE_LABELS]);
    }

    public function create(): View
    {
        return view('users.create', ['roles' => self::ROLES, 'roleLabels' => self::ROLE_LABELS]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'role' => ['required', Rule::in(self::ROLES)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => $request->boolean('is_active', true),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()->route('users.index')->with('status', __('Usuario creado correctamente.'));
    }

    public function edit(User $user): View
    {
        return view('users.edit', [
            'user' => $user,
            'roles' => self::ROLES,
            'roleLabels' => self::ROLE_LABELS,
            'isSelf' => auth()->id() === $user->id,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'role' => ['required', Rule::in(self::ROLES)],
        ]);

        $isSelf = $request->user()->id === $user->id;
        $wantsActive = $request->boolean('is_active', false);

        if ($isSelf && $validated['role'] !== 'admin') {
            return back()
                ->withErrors(['role' => __('No puedes quitarte a ti mismo el rol de admin.')])
                ->withInput();
        }

        if ($isSelf && ! $wantsActive) {
            return back()
                ->withErrors(['is_active' => __('No puedes desactivar tu propia cuenta.')])
                ->withInput();
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $isSelf ? true : $wantsActive,
        ]);

        if (filled($validated['password'] ?? null)) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();
        $user->syncRoles([$validated['role']]);

        return redirect()->route('users.index')->with('status', __('Usuario actualizado correctamente.'));
    }
}
