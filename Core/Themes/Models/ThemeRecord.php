<?php

declare(strict_types=1);

namespace App\Core\Themes\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $theme_id
 * @property string $name
 * @property bool $is_active
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<ThemeRecord> query()
 * @method static ThemeRecord|null find(int $id)
 * @method static ThemeRecord firstOrNew(array<string, mixed> $attributes, array<string, mixed> $values = [])
 */
class ThemeRecord extends Model
{
    protected $table = 'cms_themes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'theme_id',
        'name',
        'is_active',
        'settings',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];
}
