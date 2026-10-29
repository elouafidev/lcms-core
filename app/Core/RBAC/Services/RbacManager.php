<?php

declare(strict_types=1);

namespace App\Core\RBAC\Services;

use App\Core\RBAC\Models\Permission;
use App\Core\RBAC\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class RbacManager
{
    /**
     * Get all roles ordered by id.
     *
     * @return Collection<int, Role>
     */
    public function allRoles(): Collection
    {
        /** @var Collection<int, Role> $roles */
        $roles = Role::with(['permissions', 'users'])->orderBy('id')->get();

        return $roles;
    }

    /**
     * Find a role by its ID.
     */
    public function getRole(int $id): ?Role
    {
        /** @var Role|null $role */
        $role = Role::with(['permissions', 'users'])->find($id);

        return $role;
    }

    /**
     * Create a new role.
     *
     * @param  list<int>  $permissionIds
     */
    public function createRole(string $name, string $slug, ?string $description = null, array $permissionIds = []): Role
    {
        /** @var Role $role */
        $role = Role::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'is_system' => false,
        ]);

        if (! empty($permissionIds)) {
            $role->permissions()->sync($permissionIds);
        }

        return $role;
    }

    /**
     * Update an existing role.
     *
     * @param  list<int>  $permissionIds
     */
    public function updateRole(Role $role, string $name, ?string $description = null, array $permissionIds = []): Role
    {
        $role->name = $name;
        $role->description = $description;
        $role->save();

        // System super_admin always keeps all permissions
        if ($role->slug !== 'super_admin') {
            $role->permissions()->sync($permissionIds);
        }

        return $role;
    }

    /**
     * Delete a role (system roles cannot be deleted).
     */
    public function deleteRole(Role $role): bool
    {
        if ($role->is_system) {
            throw new InvalidArgumentException("Impossible de supprimer le rôle système [{$role->slug}].");
        }

        return (bool) $role->delete();
    }

    /**
     * Get all permissions grouped by their group category.
     *
     * @return array<string, Collection<int, Permission>>
     */
    public function getPermissionsByGroup(): array
    {
        /** @var Collection<int, Permission> $all */
        $all = Permission::orderBy('group')->orderBy('name')->get();

        /** @var array<string, Collection<int, Permission>> $grouped */
        $grouped = $all->groupBy('group')->all();

        return $grouped;
    }

    /**
     * Assign roles to a user.
     *
     * @param  list<int>  $roleIds
     */
    public function assignRolesToUser(User $user, array $roleIds): void
    {
        $user->roles()->sync($roleIds);
    }
}
