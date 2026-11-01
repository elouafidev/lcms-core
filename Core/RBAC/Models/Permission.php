<?php

declare(strict_types=1);

namespace App\Core\RBAC\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $group
 * @property ?string $description
 * @property ?CarbonInterface $created_at
 * @property ?CarbonInterface $updated_at
 * @property-read Collection<int, Role> $roles
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> where(string|array<string, mixed>|\Closure $column, mixed $operator = null, mixed $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static> orderBy(string $column, string $direction = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder<static> firstOrCreate(array<string, mixed> $attributes, array<string, mixed> $values = [])
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
        'description',
    ];

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role');
    }
}
