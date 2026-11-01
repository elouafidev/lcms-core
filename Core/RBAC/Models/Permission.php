<?php

declare(strict_types=1);

namespace Core\RBAC\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $group
 * @property ?string $plugin_id
 * @property ?string $description
 * @property ?CarbonInterface $created_at
 * @property ?CarbonInterface $updated_at
 * @property-read Collection<int, Role> $roles
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> where(string|array<string, mixed>|\Closure $column, mixed $operator = null, mixed $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static> whereNotNull(string|array<string> $columns)
 * @method static \Illuminate\Database\Eloquent\Builder<static> whereNotIn(string $column, mixed $values)
 * @method static \Illuminate\Database\Eloquent\Builder<static> orderBy(string $column, string $direction = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder<static> firstOrCreate(array<string, mixed> $attributes, array<string, mixed> $values = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static> updateOrCreate(array<string, mixed> $attributes, array<string, mixed> $values = [])
 */
class Permission extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'group',
        'plugin_id',
        'description',
    ];

    /**
     * Determine if this permission is owned by a plugin.
     */
    public function isPluginPermission(): bool
    {
        return $this->plugin_id !== null;
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role');
    }
}
