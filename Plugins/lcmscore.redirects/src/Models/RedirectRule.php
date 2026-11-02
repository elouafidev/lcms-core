<?php

declare(strict_types=1);

namespace Plugins\Redirects\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $source_path
 * @property string $target_url
 * @property int $status_code
 * @property bool $is_active
 * @property int $hit_count
 * @property ?string $description
 * @property ?CarbonInterface $last_used_at
 * @property ?CarbonInterface $created_at
 * @property ?CarbonInterface $updated_at
 *
 * @method static Builder<static> where(string|array<string, mixed>|\Closure $column, mixed $operator = null, mixed $value = null)
 * @method static Builder<static> create(array<string, mixed> $attributes = [])
 * @method static Builder<static> latest(string $column = 'created_at')
 * @method static static|null find(mixed $id, array<string> $columns = ['*'])
 * @method static static findOrFail(mixed $id, array<string> $columns = ['*'])
 */
class RedirectRule extends Model
{
    /**
     * @var string
     */
    protected $table = 'plugin_redirects';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'source_path',
        'target_url',
        'status_code',
        'is_active',
        'hit_count',
        'last_used_at',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'is_active' => 'boolean',
            'hit_count' => 'integer',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Record a hit on this redirection rule.
     */
    public function recordHit(): void
    {
        $this->increment('hit_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Normalize URL path to standard leading slash without trailing slash.
     */
    public static function normalizePath(string $rawPath): string
    {
        $parsed = parse_url(trim($rawPath), PHP_URL_PATH);
        $clean = trim((string) ($parsed ?? $rawPath), '/');

        return '/'.$clean;
    }

    /**
     * Find active redirection rule matching given request path.
     */
    public static function findMatching(string $requestPath): ?self
    {
        $normalized = static::normalizePath($requestPath);
        $unslashed = ltrim($normalized, '/');

        /** @var self|null $rule */
        $rule = static::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($normalized, $unslashed): void {
                $query->where('source_path', $normalized)
                    ->orWhere('source_path', $unslashed);
            })
            ->first();

        return $rule;
    }
}
