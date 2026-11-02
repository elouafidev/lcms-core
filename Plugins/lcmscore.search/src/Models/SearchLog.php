<?php

declare(strict_types=1);

namespace Plugins\Search\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $query
 * @property int $results_count
 * @property ?string $ip_address
 * @property ?string $user_agent
 * @property ?CarbonInterface $created_at
 * @property ?CarbonInterface $updated_at
 *
 * @method static Builder<static> where(string|array<string, mixed>|\Closure $column, mixed $operator = null, mixed $value = null)
 * @method static Builder<static> create(array<string, mixed> $attributes = [])
 * @method static Builder<static> latest(string $column = 'created_at')
 * @method static static|null find(mixed $id, array<string> $columns = ['*'])
 */
class SearchLog extends Model
{
    /**
     * @var string
     */
    protected $table = 'plugin_search_logs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'query',
        'results_count',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'results_count' => 'integer',
        ];
    }

    /**
     * Record a search query log.
     */
    public static function record(string $term, int $resultsCount, ?string $ip = null, ?string $userAgent = null): self
    {
        /** @var self $log */
        $log = static::create([
            'query' => mb_substr(trim($term), 0, 255),
            'results_count' => $resultsCount,
            'ip_address' => $ip,
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 500) : null,
        ]);

        return $log;
    }
}
