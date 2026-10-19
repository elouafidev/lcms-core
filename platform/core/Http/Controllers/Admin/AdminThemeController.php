<?php

declare(strict_types=1);

namespace Platform\Core\Http\Controllers\Admin;

use App\Core\Themes\ThemeDiscovery;
use App\Core\Themes\ThemeManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Throwable;

class AdminThemeController extends Controller
{
    public function __construct(
        private ThemeDiscovery $discovery,
        private ThemeManager $themeManager
    ) {}

    /**
     * Display the list of available themes and active theme status.
     */
    public function index(): View
    {
        $this->discovery->discover();
        $themes = $this->discovery->all();
        $activeTheme = $this->themeManager->getActiveTheme();

        return view('core::admin.themes.index', [
            'themes' => $themes,
            'activeTheme' => $activeTheme,
            'activeThemeId' => $activeTheme?->getId(),
        ]);
    }

    /**
     * Activate a theme.
     */
    public function activate(string $id): RedirectResponse
    {
        try {
            $theme = $this->themeManager->activate($id);

            return redirect()->route('admin.themes.index')
                ->with('success', "Thème '{$theme->getName()}' activé avec succès.");
        } catch (Throwable $e) {
            return redirect()->route('admin.themes.index')
                ->with('error', "Échec de l'activation du thème '{$id}': ".$e->getMessage());
        }
    }

    /**
     * Deactivate the active theme.
     */
    public function deactivate(): RedirectResponse
    {
        try {
            $this->themeManager->deactivate();

            return redirect()->route('admin.themes.index')
                ->with('success', "Thème désactivé avec succès. Rendu d'origine restauré.");
        } catch (Throwable $e) {
            return redirect()->route('admin.themes.index')
                ->with('error', 'Échec de la désactivation du thème: '.$e->getMessage());
        }
    }
}
