<?php

declare(strict_types=1);

namespace Plugins\Pages\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $content
 * @property bool $is_published
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<Page> query()
 * @method static Builder<Page> published()
 */
class Page extends Model
{
    protected $table = 'plugin_pages';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'is_published',
        'status',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_published' => 'boolean',
    ];

    /**
     * Scope query to only include published pages.
     *
     * @param  Builder<Page>  $query
     * @return Builder<Page>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('status', 'published')
                ->orWhere('is_published', true);
        });
    }
}
