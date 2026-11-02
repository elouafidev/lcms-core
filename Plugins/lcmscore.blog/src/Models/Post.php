<?php

declare(strict_types=1);

namespace Plugins\Blog\Models;

use Carbon\CarbonInterface;
use Core\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property ?int $author_id
 * @property ?int $category_id
 * @property string $title
 * @property string $slug
 * @property ?string $excerpt
 * @property string $content
 * @property ?string $featured_image
 * @property int $reading_time
 * @property string $status
 * @property ?CarbonInterface $published_at
 * @property int $views_count
 * @property bool $is_featured
 * @property ?string $meta_title
 * @property ?string $meta_description
 * @property ?CarbonInterface $created_at
 * @property ?CarbonInterface $updated_at
 * @property-read ?User $author
 * @property-read ?Category $category
 * @property-read Collection<int, Tag> $tags
 *
 * @method static Builder<static> where(string|array<string, mixed>|\Closure $column, mixed $operator = null, mixed $value = null)
 * @method static Builder<static> whereIn(string $column, mixed $values)
 * @method static Builder<static> create(array<string, mixed> $attributes = [])
 * @method static Builder<static> orderBy(string $column, string $direction = 'asc')
 * @method static Builder<static> latest(string $column = 'created_at')
 * @method static Builder<static> with(array<int, string>|string $relations)
 * @method static Builder<static> published()
 * @method static Builder<static> featured()
 * @method static static|null find(mixed $id, array<string> $columns = ['*'])
 */
class Post extends Model
{
    /**
     * @var string
     */
    protected $table = 'plugin_blog_posts';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'author_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'reading_time',
        'status',
        'published_at',
        'views_count',
        'is_featured',
        'meta_title',
        'meta_description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'views_count' => 'integer',
            'reading_time' => 'integer',
            'is_featured' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'plugin_blog_post_tag', 'post_id', 'tag_id');
    }

    /**
     * Calculate estimated reading time in minutes (approx. 200 words per minute).
     */
    public static function calculateReadingTime(string $content): int
    {
        $cleanText = strip_tags($content);
        $wordCount = str_word_count($cleanText);

        return max(1, (int) ceil($wordCount / 200));
    }

    /**
     * Check if the post is publicly published.
     */
    public function isPublished(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        if ($this->published_at !== null && $this->published_at->isFuture()) {
            return false;
        }

        return true;
    }

    /**
     * Build query for published posts.
     *
     * @param  Builder<static>|null  $query
     * @return Builder<static>
     */
    public static function queryPublished(?Builder $query = null): Builder
    {
        $builder = $query ?? static::query();

        return $builder->where('status', 'published')
            ->where(function (Builder $sub): void {
                $sub->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Build query for featured posts.
     *
     * @param  Builder<static>|null  $query
     * @return Builder<static>
     */
    public static function queryFeatured(?Builder $query = null): Builder
    {
        $builder = $query ?? static::query();

        return $builder->where('is_featured', true);
    }

    /**
     * Scope to only published posts.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return static::queryPublished($query);
    }

    /**
     * Scope to featured posts.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return static::queryFeatured($query);
    }
}
