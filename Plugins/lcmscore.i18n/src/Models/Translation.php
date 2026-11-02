<?php

declare(strict_types=1);

namespace Plugins\I18n\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $translatable_type
 * @property ?int $translatable_id
 * @property string $locale
 * @property string $field
 * @property string $value
 * @property ?CarbonInterface $created_at
 * @property ?CarbonInterface $updated_at
 *
 * @method static Builder<static> where(string|array<string, mixed>|\Closure $column, mixed $operator = null, mixed $value = null)
 * @method static Builder<static> create(array<string, mixed> $attributes = [])
 * @method static Builder<static> updateOrCreate(array<string, mixed> $attributes, array<string, mixed> $values = [])
 * @method static static|null find(mixed $id, array<string> $columns = ['*'])
 */
class Translation extends Model
{
    /**
     * @var string
     */
    protected $table = 'plugin_i18n_translations';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'translatable_type',
        'translatable_id',
        'locale',
        'field',
        'value',
    ];

    /**
     * Retrieve translated value with optional fallback.
     */
    public static function getFor(string $type, ?int $id, string $locale, string $field, ?string $fallback = null): ?string
    {
        $query = static::query()
            ->where('translatable_type', $type)
            ->where('locale', $locale)
            ->where('field', $field);

        if ($id !== null) {
            $query->where('translatable_id', $id);
        } else {
            $query->whereNull('translatable_id');
        }

        /** @var static|null $record */
        $record = $query->first();

        return $record !== null ? $record->value : $fallback;
    }

    /**
     * Store or update a translated value.
     */
    public static function setFor(string $type, ?int $id, string $locale, string $field, string $value): self
    {
        /** @var static $record */
        $record = static::query()->updateOrCreate(
            [
                'translatable_type' => $type,
                'translatable_id' => $id,
                'locale' => $locale,
                'field' => $field,
            ],
            [
                'value' => $value,
            ]
        );

        return $record;
    }
}
