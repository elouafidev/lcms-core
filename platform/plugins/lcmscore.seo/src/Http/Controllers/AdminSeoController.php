<?php

declare(strict_types=1);

namespace Plugins\Seo\Http\Controllers;

use App\Core\PluginRegistry\PluginRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Plugins\Seo\Models\SeoSetting;

class AdminSeoController extends Controller
{
    public function __construct(
        private PluginRegistry $registry
    ) {}

    /**
     * Display current SEO default configuration.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->ensureActive();

        $settings = [
            'default_title' => SeoSetting::get('default_title', ''),
            'default_description' => SeoSetting::get('default_description', ''),
            'default_canonical' => SeoSetting::get('default_canonical', ''),
            'default_og_image' => SeoSetting::get('default_og_image', ''),
            'site_name' => SeoSetting::get('site_name', ''),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $settings,
            ]);
        }

        return view('seo::admin.index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update SEO default configuration in storage.
     */
    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureActive();

        $validated = $request->validate([
            'default_title' => 'nullable|string|max:255',
            'default_description' => 'nullable|string|max:1000',
            'default_canonical' => 'nullable|string|max:500',
            'default_og_image' => 'nullable|string|max:500',
            'site_name' => 'nullable|string|max:255',
        ]);

        foreach ($validated as $key => $value) {
            SeoSetting::set((string) $key, is_string($value) ? $value : null);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Paramètres SEO enregistrés.',
                'data' => $validated,
            ]);
        }

        return redirect('/admin/seo')->with('success', 'Paramètres SEO enregistrés avec succès.');
    }

    private function ensureActive(): void
    {
        if (! $this->registry->isActive('lcmscore.seo')) {
            abort(404, 'SEO plugin is disabled.');
        }
    }
}
