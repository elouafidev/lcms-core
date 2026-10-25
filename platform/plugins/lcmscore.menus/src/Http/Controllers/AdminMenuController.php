<?php

declare(strict_types=1);

namespace Plugins\Menus\Http\Controllers;

use App\Core\PluginRegistry\PluginRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Plugins\Menus\Models\Menu;
use Plugins\Menus\Models\MenuItem;

class AdminMenuController extends Controller
{
    public function __construct(
        private PluginRegistry $registry
    ) {}

    /**
     * Display a listing of navigation menus.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->ensureActive();

        $menus = Menu::query()->withCount('items')->orderBy('id', 'desc')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $menus,
            ]);
        }

        return view('menus::admin.index', [
            'menus' => $menus,
        ]);
    }

    /**
     * Store a newly created menu in storage.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureActive();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plugin_menus,slug',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        /** @var Menu $menu */
        $menu = Menu::query()->create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Menu créé avec succès.',
                'data' => $menu,
            ], 201);
        }

        return redirect()->route('admin.menus.index')->with('success', __('Menu created successfully.'));
    }

    /**
     * Display the specified menu with its items.
     */
    public function show(int $id, Request $request): View|JsonResponse
    {
        $this->ensureActive();

        /** @var Menu|null $menu */
        $menu = Menu::query()->with(['items'])->find($id);

        if ($menu === null) {
            $msg = __('Menu not found.');
            abort(404, is_string($msg) ? $msg : 'Menu not found.');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $menu,
            ]);
        }

        return view('menus::admin.show', [
            'menu' => $menu,
        ]);
    }

    /**
     * Store a new item under the specified menu.
     */
    public function storeItem(int $id, Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureActive();

        /** @var Menu|null $menu */
        $menu = Menu::query()->find($id);

        if ($menu === null) {
            $msg = __('Menu not found.');
            abort(404, is_string($msg) ? $msg : 'Menu not found.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'url' => 'required|string|max:500',
            'parent_id' => 'nullable|integer|exists:plugin_menu_items,id',
            'target' => 'nullable|string|in:_self,_blank',
            'icon' => 'nullable|string|max:50',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        /** @var MenuItem $item */
        $item = $menu->items()->create([
            'parent_id' => isset($validated['parent_id']) ? (int) $validated['parent_id'] : null,
            'title' => $validated['title'],
            'url' => $validated['url'],
            'target' => $validated['target'] ?? '_self',
            'icon' => $validated['icon'] ?? null,
            'order' => isset($validated['order']) ? (int) $validated['order'] : 0,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Élément de menu ajouté.',
                'data' => $item,
            ], 201);
        }

        return redirect()->route('admin.menus.show', $id)->with('success', __('Item added successfully.'));
    }

    /**
     * Remove the specified menu from storage.
     */
    public function destroy(int $id, Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureActive();

        /** @var Menu|null $menu */
        $menu = Menu::query()->find($id);

        if ($menu === null) {
            $msg = __('Menu not found.');
            abort(404, is_string($msg) ? $msg : 'Menu not found.');
        }

        $menu->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Menu supprimé avec succès.',
            ]);
        }

        return redirect()->route('admin.menus.index')->with('success', __('Menu deleted successfully.'));
    }

    /**
     * Remove a specific menu item from storage.
     */
    public function destroyItem(int $id, Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureActive();

        /** @var MenuItem|null $item */
        $item = MenuItem::query()->find($id);

        if ($item === null) {
            $msg = __('Menu item not found.');
            abort(404, is_string($msg) ? $msg : 'Menu item not found.');
        }

        $menuId = $item->menu_id;
        $item->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Élément de menu supprimé.',
            ]);
        }

        return redirect()->route('admin.menus.show', $menuId)->with('success', __('Item deleted successfully.'));
    }

    /**
     * Ensure plugin is active or throw 404.
     */
    private function ensureActive(): void
    {
        if (! $this->registry->isActive('lcmscore.menus')) {
            abort(404, 'Menus plugin is disabled.');
        }
    }
}
