<?php

declare(strict_types=1);

namespace Plugins\I18n\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $locale
 * @property ?string $flag
 * @property bool $is_default
 * @property bool $is_active
 * @property bool $is_rtl
 * @property int $order
 * @property ?CarbonInterface $created_at
 * @property ?CarbonInterface $updated_at
 *
 * @method static Builder<static> where(string|array<string, mixed>|\Closure $column, mixed $operator = null, mixed $value = null)
 * @method static Builder<static> create(array<string, mixed> $attributes = [])
 * @method static static|null find(mixed $id, array<string> $columns = ['*'])
 */
class Language extends Model
{
    /**
     * @var string
     */
    protected $table = 'plugin_i18n_languages';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'locale',
        'flag',
        'is_default',
        'is_active',
        'is_rtl',
        'order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'is_rtl' => 'boolean',
            'order' => 'integer',
        ];
    }

    /**
     * Define this language as the system default language.
     */
    public function makeDefault(): void
    {
        static::query()->where('id', '!=', $this->id)->update(['is_default' => false]);
        $this->is_default = true;
        $this->is_active = true;
        $this->save();
    }

    /**
     * Retrieve the current default language.
     */
    public static function getDefault(): ?self
    {
        /** @var static|null $lang */
        $lang = static::query()->where('is_default', true)->first();

        return $lang;
    }

    /**
     * Retrieve all active languages ordered by priority.
     *
     * @return Collection<int, static>
     */
    public static function getActive(): Collection
    {
        return static::query()
            ->where('is_active', true)
            ->get()
            ->sortBy('order')
            ->values();
    }

    /**
     * Scope query to only active languages.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to default language.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }
}
