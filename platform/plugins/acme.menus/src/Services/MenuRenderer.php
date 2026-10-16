<?php

declare(strict_types=1);

namespace Plugins\Menus\Services;

use App\Core\Hooks\HookManager;
use App\Core\PluginRegistry\PluginRegistry;
use Illuminate\Database\Eloquent\Collection;
use Plugins\Menus\Models\Menu;
use Plugins\Menus\Models\MenuItem;

class MenuRenderer
{
    public function __construct(
        private PluginRegistry $registry,
        private ?HookManager $hookManager = null
    ) {}

    /**
     * Render the menu HTML structure by menu slug.
     *
     * @param  array<string, mixed>  $options
     */
    public function render(string $slug, array $options = []): string
    {
        // Must return empty string if the plugin is not active
        if (! $this->registry->isActive('acme.menus')) {
            return '';
        }

        /** @var Menu|null $menu */
        $menu = Menu::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with(['items'])
            ->first();

        if ($menu === null) {
            return '';
        }

        $items = $menu->items;

        /** @var Collection<int, MenuItem> $rootItems */
        $rootItems = $items
            ->filter(fn (MenuItem $item): bool => $item->parent_id === null && $item->is_active)
            ->sortBy('order')
            ->values();

        $cssClass = (string) ($options['class'] ?? "menu-nav menu-{$slug}");
        $idAttr = isset($options['id']) ? ' id="'.htmlspecialchars((string) $options['id'], ENT_QUOTES, 'UTF-8').'"' : '';

        $html = '<nav'.$idAttr.' class="'.htmlspecialchars($cssClass, ENT_QUOTES, 'UTF-8').'"><ul class="menu-list">';

        foreach ($rootItems as $item) {
            $html .= $this->renderItem($item, $items);
        }

        $html .= '</ul></nav>';

        if ($this->hookManager !== null) {
            /** @var string $filtered */
            $filtered = $this->hookManager->applyFilter('menu.rendered', $html, $slug, $options);

            return $filtered;
        }

        return $html;
    }

    /**
     * Recursively render a single menu item with its children.
     *
     * @param  Collection<int, MenuItem>  $allItems
     */
    private function renderItem(MenuItem $item, Collection $allItems): string
    {
        if (! $item->is_active) {
            return '';
        }

        $url = htmlspecialchars($item->url, ENT_QUOTES, 'UTF-8');
        $title = htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8');
        $target = htmlspecialchars($item->target ?: '_self', ENT_QUOTES, 'UTF-8');

        /** @var Collection<int, MenuItem> $children */
        $children = $allItems
            ->filter(fn (MenuItem $child): bool => $child->parent_id === $item->id && $child->is_active)
            ->sortBy('order')
            ->values();

        $hasChildren = $children->isNotEmpty();
        $liClass = $hasChildren ? 'menu-item has-children' : 'menu-item';

        $html = '<li class="'.$liClass.'">';
        $html .= '<a href="'.$url.'" target="'.$target.'">'.$title.'</a>';

        if ($hasChildren) {
            $html .= '<ul class="sub-menu">';
            foreach ($children as $child) {
                $html .= $this->renderItem($child, $allItems);
            }
            $html .= '</ul>';
        }

        $html .= '</li>';

        return $html;
    }
}
