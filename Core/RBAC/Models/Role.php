<?php

declare(strict_types=1);

namespace App\Core\RBAC\Models;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property ?string $description
 * @property bool $is_system
 * @property ?CarbonInterface $created_at
 * @property ?CarbonInterface $updated_at
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, Permission> $permissions
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> where(string|array<string, mixed>|\Closure $column, mixed $operator = null, mixed $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static> with(array<int, string>|string $relations)
 * @method static \Illuminate\Database\Eloquent\Builder<static> create(array<string, mixed> $attributes = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static> firstOrCreate(array<string, mixed> $attributes, array<string, mixed> $values = [])
 * @method static static|null find(mixed $id, array<string> $columns = ['*'])
 */
class Role extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

    /**
     * Check if this role possesses a specific permission slug.
     */
    public function hasPermission(string $permissionSlug): bool
    {
        return $this->permissions->contains('slug', $permissionSlug);
    }
}
