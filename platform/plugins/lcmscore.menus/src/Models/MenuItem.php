<?php

declare(strict_types=1);

namespace Plugins\Menus\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $menu_id
 * @property int|null $parent_id
 * @property string $title
 * @property string $url
 * @property string $target
 * @property string|null $icon
 * @property int $order
 * @property bool $is_active
 * @property Menu $menu
 * @property MenuItem|null $parent
 * @property Collection<int, MenuItem> $children
 */
class MenuItem extends Model
{
    protected $table = 'plugin_menu_items';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'url',
        'target',
        'icon',
        'order',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Parent menu.
     *
     * @return BelongsTo<Menu, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    /**
     * Parent menu item.
     *
     * @return BelongsTo<MenuItem, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /**
     * Child menu items.
     *
     * @return HasMany<MenuItem, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id');
    }
}
