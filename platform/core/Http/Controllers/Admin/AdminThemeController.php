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
        $rawFile = $request->file('theme_archive');
        if ($rawFile instanceof UploadedFile && ! $rawFile->isValid()) {
            $uploadError = $rawFile->getError();
            $maxUpload = ini_get('upload_max_filesize') ?: '2M';
            $message = match ($uploadError) {
                UPLOAD_ERR_INI_SIZE => "Le fichier dépasse la taille maximale autorisée par le serveur PHP (upload_max_filesize = {$maxUpload}).",
                UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la directive de taille maximale du formulaire.',
                UPLOAD_ERR_PARTIAL => "Le fichier n'a été que partiellement téléversé.",
                UPLOAD_ERR_NO_FILE => "Aucun fichier d'archive n'a été téléversé.",
                UPLOAD_ERR_NO_TMP_DIR => 'Le dossier temporaire est manquant sur le serveur PHP.',
                UPLOAD_ERR_CANT_WRITE => "Échec de l'écriture du fichier sur le disque du serveur.",
                UPLOAD_ERR_EXTENSION => 'Une extension PHP a interrompu le téléversement du fichier.',
                default => "Erreur lors du téléversement du fichier (code: {$uploadError}).",
            };

            return redirect()->back(fallback: route('admin.themes.index'))
                ->with('error', $message);
        }

        $request->validate([
            'theme_archive' => [
                'required',
                'file',
                'extensions:zip',
                'mimetypes:application/zip,application/x-zip,application/x-zip-compressed,application/octet-stream,multipart/x-zip',
                'max:51200',
            ],
        ], [
            'theme_archive.required' => "Veuillez sélectionner un fichier d'archive ZIP pour le thème.",
            'theme_archive.file' => 'Le fichier transmis est invalide ou corrompu.',
            'theme_archive.extensions' => "L'archive doit obligatoirement posséder l'extension .zip.",
            'theme_archive.mimetypes' => "Le fichier sélectionné n'est pas reconnu comme une archive ZIP valide.",
            'theme_archive.max' => "L'archive du thème ne doit pas dépasser 50 Mo.",
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
