<?php

declare(strict_types=1);

namespace Platform\Core\Http\Controllers\Admin;

use App\Core\RBAC\Services\RbacManager;
use App\Core\Security\Audit\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): View
    {
        /** @var \Illuminate\Database\Eloquent\Builder<User> $query */
        $query = User::with(['roles'])->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $search = '%'.$request->input('search').'%';
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search);
            });
        }

        if ($request->filled('role')) {
            $roleSlug = (string) $request->input('role');
            $query->whereHas('roles', fn ($q) => $q->where('slug', $roleSlug));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $users = $query->paginate(15)->withQueryString();

        return view('core::admin.users.index', [
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(RbacManager $rbacManager): View
    {
        $roles = $rbacManager->allRoles();

        return view('core::admin.users.create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request, RbacManager $rbacManager, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        /** @var User $user */
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make((string) $validated['password']),
            'is_active' => $request->boolean('is_active', true),
            'email_verified_at' => now(),
        ]);

        $roleIds = array_values(array_map('intval', $validated['roles'] ?? []));
        if (! empty($roleIds)) {
            $rbacManager->assignRolesToUser($user, $roleIds);
        }

        $auditLogger->log(
            action: 'user.created',
            resourceType: 'User',
            resourceId: (string) $user->id,
            newValues: ['name' => $user->name, 'email' => $user->email]
        );

        return redirect()->route('admin.users.index')->with('success', "Le compte [{$user->name}] a été créé.");
    }

    /**
     * Show the form for editing an existing user.
     */
    public function edit(User $user, RbacManager $rbacManager): View
    {
        $user->load('roles');
        $roles = $rbacManager->allRoles();

        return view('core::admin.users.edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user, RbacManager $rbacManager, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Guard: Cannot deactivate self
        if ($user->id === Auth::id() && ! $request->boolean('is_active', true)) {
            return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte administrateur.');
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->is_active = $request->boolean('is_active', true);

        if (! empty($validated['password'])) {
            $user->password = Hash::make((string) $validated['password']);
            $user->last_password_change_at = now();
        }

        $user->save();

        // Guard against removing super_admin if this is the only super_admin
        $roleIds = array_values(array_map('intval', $validated['roles'] ?? []));
        $rbacManager->assignRolesToUser($user, $roleIds);

        $auditLogger->log(
            action: 'user.updated',
            resourceType: 'User',
            resourceId: (string) $user->id,
            newValues: ['name' => $user->name, 'email' => $user->email, 'is_active' => $user->is_active]
        );

        return redirect()->route('admin.users.index')->with('success', "Le compte [{$user->name}] a été mis à jour.");
    }

    /**
     * Toggle active/inactive status of a user.
     */
    public function toggleStatus(User $user, AuditLogger $auditLogger): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Vous ne pouvez pas modifier le statut de votre propre compte.');
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        // If deactivated, revoke all active sessions
        if (! $user->is_active && Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        $auditLogger->log(
            action: $user->is_active ? 'user.activated' : 'user.deactivated',
            resourceType: 'User',
            resourceId: (string) $user->id,
            newValues: ['is_active' => $user->is_active]
        );

        $statusLabel = $user->is_active ? 'activé' : 'désactivé';

        return back()->with('success', "Le compte [{$user->name}] a été {$statusLabel}.");
    }

    /**
     * Revoke all active sessions for a user.
     */
    public function revokeSessions(User $user, AuditLogger $auditLogger): RedirectResponse
    {
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        $auditLogger->log(
            action: 'user.sessions.revoked_admin',
            resourceType: 'User',
            resourceId: (string) $user->id
        );

        return back()->with('success', "Toutes les sessions de l'utilisateur [{$user->name}] ont été révoquées.");
    }

    /**
     * Reset 2FA for a user (admin rescue).
     */
    public function reset2fa(User $user, AuditLogger $auditLogger): RedirectResponse
    {
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        $auditLogger->log(
            action: 'security.2fa.reset_by_admin',
            resourceType: 'User',
            resourceId: (string) $user->id
        );

        return back()->with('success', "L'authentification 2FA a été réinitialisée pour l'utilisateur [{$user->name}].");
    }
}
