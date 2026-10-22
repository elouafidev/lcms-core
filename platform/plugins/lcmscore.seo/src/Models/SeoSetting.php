<?php

declare(strict_types=1);

namespace Plugins\Seo\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string|null $value
 */
class SeoSetting extends Model
{
    protected $table = 'plugin_seo_settings';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Retrieve a setting value with an optional fallback.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        /** @var self|null $setting */
        $setting = static::query()->where('key', $key)->first();

        return $setting !== null && $setting->value !== null ? $setting->value : $default;
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, ?string $value): self
    {
        /** @var self $setting */
        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        return $setting;
    }
}
