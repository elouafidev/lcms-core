<?php

declare(strict_types=1);

namespace Plugins\Pages\Http\Controllers;

use App\Core\PluginRegistry\PluginRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Plugins\Pages\Models\Page;

class PageController extends Controller
{
    public function __construct(
        private PluginRegistry $registry
    ) {}

    public function show(string $slug): View
    {
        if (! $this->registry->isActive('acme.pages')) {
            abort(404, 'Pages plugin is disabled.');
        }

        /** @var Page|null $page */
        $page = Page::query()
            ->where('slug', $slug)
            ->where(function (Builder $query): void {
                $query->where('status', 'published')
                    ->orWhere('is_published', true);
            })
            ->first();

        if ($page === null) {
            abort(404, "Page '{$slug}' not found or not published.");
        }

        return view('pages::page', [
            'page' => $page,
        ]);
    }
}
