<?php

declare(strict_types=1);

namespace Plugins\Blocks\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $type
 * @property array<string, mixed> $content
 * @property bool $is_active
 * @property int $order
 * @property ?CarbonInterface $created_at
 * @property ?CarbonInterface $updated_at
 *
 * @method static Builder<static> where(string|array<string, mixed>|\Closure $column, mixed $operator = null, mixed $value = null)
 * @method static Builder<static> create(array<string, mixed> $attributes = [])
 * @method static Builder<static> orderBy(string $column, string $direction = 'asc')
 * @method static Builder<static> latest(string $column = 'created_at')
 * @method static static|null find(mixed $id, array<string> $columns = ['*'])
 */
class Block extends Model
{
    /**
     * @var string
     */
    protected $table = 'plugin_blocks';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'type',
        'content',
        'is_active',
        'order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => 'array',
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    /**
     * Render the block into HTML markup.
     */
    public function render(): string
    {
        if (! $this->is_active) {
            return '';
        }

        // 1. Theme override by specific block slug
        if (view()->exists("theme::blocks.{$this->slug}")) {
            return view("theme::blocks.{$this->slug}", ['block' => $this, 'data' => $this->content])->render();
        }

        // 2. Theme override by block type
        if (view()->exists("theme::blocks.{$this->type}")) {
            return view("theme::blocks.{$this->type}", ['block' => $this, 'data' => $this->content])->render();
        }

        // 3. Fallback to plugin default template
        if (view()->exists("blocks::types.{$this->type}")) {
            return view("blocks::types.{$this->type}", ['block' => $this, 'data' => $this->content])->render();
        }

        return "<!-- Block [{$this->slug}] of type [{$this->type}] has no template -->";
    }

    /**
     * Helper to render a block by its slug.
     */
    public static function renderBySlug(string $slug): string
    {
        /** @var static|null $block */
        $block = static::query()->where('slug', $slug)->where('is_active', true)->first();

        return $block !== null ? $block->render() : '';
    }

    /**
     * Scope query to only active blocks.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to a specific block type.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
}
