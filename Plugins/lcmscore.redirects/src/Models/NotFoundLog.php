<?php

declare(strict_types=1);

namespace Plugins\Redirects\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $path
 * @property int $hits
 * @property ?string $referrer
 * @property ?string $user_agent
 * @property ?CarbonInterface $last_hit_at
 * @property ?CarbonInterface $created_at
 * @property ?CarbonInterface $updated_at
 *
 * @method static Builder<static> where(string|array<string, mixed>|\Closure $column, mixed $operator = null, mixed $value = null)
 * @method static Builder<static> create(array<string, mixed> $attributes = [])
 * @method static Builder<static> latest(string $column = 'created_at')
 * @method static static|null find(mixed $id, array<string> $columns = ['*'])
 * @method static static findOrFail(mixed $id, array<string> $columns = ['*'])
 */
class NotFoundLog extends Model
{
    /**
     * @var string
     */
    protected $table = 'plugin_redirects_404_logs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'path',
        'hits',
        'referrer',
        'user_agent',
        'last_hit_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hits' => 'integer',
            'last_hit_at' => 'datetime',
        ];
    }

    /**
     * Record a 404 hit or increment existing counter.
     */
    public static function record(string $rawPath, ?string $referrer = null, ?string $userAgent = null): self
    {
        $cleanPath = RedirectRule::normalizePath($rawPath);

        /** @var self $log */
        $log = static::query()->firstOrNew(['path' => $cleanPath]);

        $log->hits = $log->exists ? ($log->hits + 1) : 1;
        $log->referrer = $referrer !== null ? mb_substr($referrer, 0, 500) : null;
        $log->user_agent = $userAgent !== null ? mb_substr($userAgent, 0, 500) : null;
        $log->last_hit_at = now();
        $log->save();

        return $log;
    }
}
