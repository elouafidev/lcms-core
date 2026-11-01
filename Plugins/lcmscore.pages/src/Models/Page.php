<?php

declare(strict_types=1);

namespace Plugins\Pages\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $title
 * @property string $slug
 * @property string|null $content
 * @property string $template
 * @property string|null $featured_image
 * @property bool $is_published
 * @property string $status
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Page|null $parent
 * @property-read Collection<int, Page> $children
 *
 * @method static Builder<Page> query()
 * @method static Builder<Page> published()
 */
class Page extends Model
{
    protected $table = 'plugin_pages';

    protected $fillable = [
        'parent_id',
        'title',
        'slug',
        'content',
        'template',
        'featured_image',
        'is_published',
        'status',
        'published_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'parent_id' => 'integer',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Optional parent page in the hierarchy.
     *
     * @return BelongsTo<Page, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Sub-pages belonging to this parent page.
     *
     * @return HasMany<Page, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Scope query to only include published pages whose scheduled date has arrived.
     *
     * @param  Builder<Page>  $query
     * @return Builder<Page>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('status', 'published')
                ->orWhere('is_published', true);
        })->where(function (Builder $q): void {
            $q->whereNull('published_at')
                ->orWhere('published_at', '<=', now());
        });
    }

    /**
     * Retrieve the hierarchical path representation of the page.
     */
    public function getFullPath(): string
    {
        if ($this->parent !== null) {
            return $this->parent->getFullPath().'/'.$this->slug;
        }

        return $this->slug;
    }
}
