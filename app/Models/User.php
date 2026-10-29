<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Core\RBAC\Models\Permission;
use App\Core\RBAC\Models\Role;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property bool $is_active
 * @property ?string $two_factor_secret
 * @property ?string $two_factor_recovery_codes
 * @property ?CarbonInterface $two_factor_confirmed_at
 * @property ?CarbonInterface $last_password_change_at
 * @property ?CarbonInterface $last_login_at
 * @property ?string $last_login_ip
 * @property ?CarbonInterface $created_at
 * @property ?CarbonInterface $updated_at
 * @property-read Collection<int, Role> $roles
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> where(string|array<string, mixed>|\Closure $column, mixed $operator = null, mixed $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static> whereHas(string $relation, ?\Closure $callback = null, string $operator = '>=', int $count = 1)
 * @method static \Illuminate\Database\Eloquent\Builder<static> with(array<int, string>|string $relations)
 * @method static \Illuminate\Database\Eloquent\Builder<static> create(array<string, mixed> $attributes = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static> updateOrCreate(array<string, mixed> $attributes, array<string, mixed> $values = [])
 * @method static static|null find(mixed $id, array<string> $columns = ['*'])
 * @method static UserFactory factory($count = null, $state = [])
 */
#[Fillable([
    'name',
    'email',
    'password',
    'is_active',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'two_factor_confirmed_at',
    'last_password_change_at',
    'last_login_at',
    'last_login_ip',
])]
#[Hidden([
    'password',
    'remember_token',
    'two_factor_secret',
    'two_factor_recovery_codes',
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'last_password_change_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * Check if user has a specific role or any role from a list.
     *
     * @param  string|list<string>  $roles
     */
    public function hasRole(string|array $roles): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $rolesList = is_array($roles) ? $roles : [$roles];

        /** @var Collection<int, Role> $userRoles */
        $userRoles = $this->roles;

        return $userRoles->contains(fn (Role $role): bool => in_array($role->slug, $rolesList, true));
    }

    /**
     * Check if user is a Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        /** @var Collection<int, Role> $userRoles */
        $userRoles = $this->roles;

        return $userRoles->contains('slug', 'super_admin');
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        /** @var Collection<int, Role> $userRoles */
        $userRoles = $this->roles->loadMissing('permissions');

        return $userRoles->contains(fn (Role $role): bool => $role->permissions->contains('slug', $permissionSlug));
    }

    /**
     * Check if two-factor authentication is confirmed and enabled.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }
}
