<?php

declare(strict_types=1);

namespace App\Core\Security\Audit\Models;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property ?int $user_id
 * @property string $action
 * @property ?string $resource_type
 * @property ?string $resource_id
 * @property ?array<string, mixed> $old_values
 * @property ?array<string, mixed> $new_values
 * @property ?string $ip_address
 * @property ?string $user_agent
 * @property CarbonInterface $created_at
 * @property-read ?User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> where(string|array<string, mixed>|\Closure $column, mixed $operator = null, mixed $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static> create(array<string, mixed> $attributes = [])
 */
class AuditLog extends Model
{
    /**
     * @var string
     */
    protected $table = 'cms_audit_logs';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'resource_type',
        'resource_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
