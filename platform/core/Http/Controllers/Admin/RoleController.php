<?php

declare(strict_types=1);

namespace Platform\Core\Http\Controllers\Admin;

use App\Core\RBAC\Models\Role;
use App\Core\RBAC\Services\RbacManager;
use App\Core\Security\Audit\AuditLogger;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(RbacManager $rbacManager): View
    {
        $roles = $rbacManager->allRoles();

        return view('core::admin.roles.index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create(RbacManager $rbacManager): View
    {
        $permissionsGrouped = $rbacManager->getPermissionsByGroup();

        return view('core::admin.roles.create', [
            'permissionsGrouped' => $permissionsGrouped,
        ]);
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request, RbacManager $rbacManager, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:roles,slug'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $slug = ! empty($validated['slug']) ? Str::slug($validated['slug'], '_') : Str::slug($validated['name'], '_');
        $permissionIds = array_values(array_map('intval', $validated['permissions'] ?? []));

        $role = $rbacManager->createRole(
            name: $validated['name'],
            slug: $slug,
            description: $validated['description'] ?? null,
            permissionIds: $permissionIds
        );

        $auditLogger->log(
            action: 'rbac.role.created',
            resourceType: 'Role',
            resourceId: (string) $role->id,
            newValues: ['name' => $role->name, 'slug' => $role->slug]
        );

        return redirect()->route('admin.roles.index')->with('success', "Le rôle [{$role->name}] a été créé.");
    }

    /**
     * Show the form for editing an existing role.
     */
    public function edit(Role $role, RbacManager $rbacManager): View
    {
        $role->load('permissions');
        $permissionsGrouped = $rbacManager->getPermissionsByGroup();

        return view('core::admin.roles.edit', [
            'role' => $role,
            'permissionsGrouped' => $permissionsGrouped,
        ]);
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, Role $role, RbacManager $rbacManager, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $permissionIds = array_values(array_map('intval', $validated['permissions'] ?? []));

        $rbacManager->updateRole(
            role: $role,
            name: $validated['name'],
            description: $validated['description'] ?? null,
            permissionIds: $permissionIds
        );

        $auditLogger->log(
            action: 'rbac.role.updated',
            resourceType: 'Role',
            resourceId: (string) $role->id,
            newValues: ['name' => $role->name]
        );

        return redirect()->route('admin.roles.index')->with('success', "Le rôle [{$role->name}] a été mis à jour.");
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role, RbacManager $rbacManager, AuditLogger $auditLogger): RedirectResponse
    {
        if ($role->is_system) {
            return back()->with('error', "Impossible de supprimer un rôle système [{$role->name}].");
        }

        $roleName = $role->name;
        $rbacManager->deleteRole($role);

        $auditLogger->log(
            action: 'rbac.role.deleted',
            resourceType: 'Role',
            resourceId: (string) $role->id,
            oldValues: ['name' => $roleName]
        );

        return redirect()->route('admin.roles.index')->with('success', "Le rôle [{$roleName}] a été supprimé.");
    }
}
