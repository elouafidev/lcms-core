<?php

declare(strict_types=1);

namespace Plugins\Pages\Http\Controllers;

use Core\Http\Controllers\Controller;
use Core\PluginRegistry\PluginRegistry;
use Illuminate\Contracts\View\View;
use Plugins\Pages\Models\Page;

class PageController extends Controller
{
    public function __construct(
        private PluginRegistry $registry
    ) {}

    public function show(string $slug): View
    {
        if (! $this->registry->isActive('lcmscore.pages')) {
            abort(404, 'Pages plugin is disabled.');
        }

        /** @var Page|null $page */
        $page = Page::published()
            ->where('slug', $slug)
            ->first();

        if ($page === null) {
            abort(404, "Page '{$slug}' not found or not published.");
        }

        $templateView = 'pages::templates.'.$page->template;
        if (view()->exists($templateView)) {
            return view($templateView, ['page' => $page]);
        }

        return view('pages::page', [
            'page' => $page,
        ]);
    }
}
