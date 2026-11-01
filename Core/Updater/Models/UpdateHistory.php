<?php

declare(strict_types=1);

namespace App\Core\Updater\Models;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $from_version
 * @property string $to_version
 * @property string $status
 * @property ?string $log
 * @property ?string $backup_path
 * @property ?int $user_id
 * @property CarbonInterface $created_at
 * @property-read ?User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> where(string|array<string, mixed>|\Closure $column, mixed $operator = null, mixed $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static> with(array<int, string>|string $relations)
 * @method static \Illuminate\Database\Eloquent\Builder<static> orderBy(string $column, string $direction = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder<static> latest(string $column = 'created_at')
 * @method static \Illuminate\Database\Eloquent\Builder<static> create(array<string, mixed> $attributes = [])
 */
class UpdateHistory extends Model
{
    /**
     * @var string
     */
    protected $table = 'cms_updates_history';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'from_version',
        'to_version',
        'status',
        'log',
        'backup_path',
        'user_id',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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
