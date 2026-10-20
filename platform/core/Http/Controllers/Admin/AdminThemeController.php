<?php

declare(strict_types=1);

namespace Platform\Core\Http\Controllers\Admin;

use App\Core\Themes\ThemeDiscovery;
use App\Core\Themes\ThemeManager;
use App\Core\Themes\ThemeZipInstaller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Throwable;

class AdminThemeController extends Controller
{
    private ThemeZipInstaller $zipInstaller;

    public function __construct(
        private ThemeDiscovery $discovery,
        private ThemeManager $themeManager,
        ?ThemeZipInstaller $zipInstaller = null
    ) {
        $this->zipInstaller = $zipInstaller ?? app(ThemeZipInstaller::class);
    }

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
     * Upload and install a theme from a ZIP archive.
     */
    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'theme_archive' => ['required', 'file', 'mimes:zip', 'max:20480'],
        ]);

        $file = $request->file('theme_archive');
        if (! $file instanceof UploadedFile) {
            return redirect()->back(fallback: route('admin.themes.index'))
                ->with('error', "Aucun fichier d'archive ZIP valide n'a été transmis.");
        }

        try {
            $overwrite = $request->boolean('overwrite', false);
            $manifest = $this->zipInstaller->installArchive($file, $overwrite);

            return redirect()->route('admin.themes.index')
                ->with('success', "Thème '{$manifest->getName()}' ({$manifest->getVersion()}) importé et installé avec succès.");
        } catch (Throwable $e) {
            return redirect()->back(fallback: route('admin.themes.index'))
                ->with('error', "Échec de l'importation de l'archive ZIP : ".$e->getMessage());
        }
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

    /**
     * Delete an inactive theme completely from the filesystem.
     */
    public function delete(string $id): RedirectResponse
    {
        try {
            $this->themeManager->delete($id);

            return redirect()->route('admin.themes.index')
                ->with('success', "Thème '{$id}' supprimé avec succès.");
        } catch (Throwable $e) {
            return redirect()->back(fallback: route('admin.themes.index'))
                ->with('error', "Échec de la suppression du thème '{$id}': ".$e->getMessage());
        }
    }
}
