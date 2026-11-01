<?php

declare(strict_types=1);

namespace Platform\Core\Http\Controllers\Admin;

use App\Core\PluginDiscovery\PluginZipInstaller;
use App\Core\PluginManager\PluginManager;
use App\Core\PluginRegistry\PluginRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Throwable;

class PluginController extends Controller
{
    private PluginZipInstaller $zipInstaller;

    public function __construct(
        private PluginRegistry $registry,
        private PluginManager $pluginManager,
        ?PluginZipInstaller $zipInstaller = null
    ) {
        $this->zipInstaller = $zipInstaller ?? app(PluginZipInstaller::class);
    }

    /**
     * Upload and install a plugin from a ZIP archive.
     */
    public function upload(Request $request): RedirectResponse
    {
        $rawFile = $request->file('plugin_archive');
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

            return redirect()->back(fallback: route('admin.plugins.index'))
                ->with('error', $message);
        }

        $request->validate([
            'plugin_archive' => [
                'required',
                'file',
                'extensions:zip',
                'mimetypes:application/zip,application/x-zip,application/x-zip-compressed,application/octet-stream,multipart/x-zip',
                'max:51200',
            ],
        ], [
            'plugin_archive.required' => "Veuillez sélectionner un fichier d'archive ZIP pour l'extension.",
            'plugin_archive.file' => 'Le fichier transmis est invalide ou corrompu.',
            'plugin_archive.extensions' => "L'archive doit obligatoirement posséder l'extension .zip.",
            'plugin_archive.mimetypes' => "Le fichier sélectionné n'est pas reconnu comme une archive ZIP valide.",
            'plugin_archive.max' => "L'archive de l'extension ne doit pas dépasser 50 Mo.",
        ]);

        $file = $request->file('plugin_archive');
        if (! $file instanceof UploadedFile) {
            return redirect()->back(fallback: route('admin.plugins.index'))
                ->with('error', "Aucun fichier d'archive ZIP valide n'a été transmis.");
        }

        try {
            $overwrite = $request->boolean('overwrite', false);
            $manifest = $this->zipInstaller->installArchive($file, $overwrite);

            return redirect()->route('admin.plugins.show', $manifest->getId())
                ->with('success', __("Extension ':name' (:version) imported and installed successfully.", [
                    'name' => $manifest->getName(),
                    'version' => $manifest->getVersion(),
                ]));
        } catch (Throwable $e) {
            return redirect()->back(fallback: route('admin.plugins.index'))
                ->with('error', __('Failed to import ZIP archive: :message', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Display the list of all plugins and their status.
     */
    public function index(): View
    {
        $plugins = $this->registry->getAll();

        return view('core::admin.plugins.index', [
            'plugins' => $plugins,
            'isSafeMode' => $this->pluginManager->isSafeMode(),
        ]);
    }

    /**
     * Display detailed configuration and metadata for a specific plugin.
     */
    public function show(string $id): View
    {
        $all = $this->registry->getAll();
        $plugin = $all->get($id);

        if ($plugin === null) {
            $msg = __("Plugin ':id' not found.", ['id' => $id]);
            abort(404, is_string($msg) ? $msg : "Plugin '{$id}' not found.");
        }

        $manifest = $plugin->getManifest();
        $record = $this->registry->find($id);

        $dependenciesHealth = [];
        foreach ($manifest->getDependencies() as $depId => $constraint) {
            $depPlugin = $all->get($depId);
            $isInstalled = $this->registry->isInstalled($depId);
            $isActive = $this->registry->isActive($depId);

            $status = 'missing';
            if ($depPlugin !== null) {
                if ($isActive) {
                    $status = 'satisfied';
                } elseif ($isInstalled) {
                    $status = 'disabled';
                } else {
                    $status = 'available';
                }
            }

            $dependenciesHealth[$depId] = [
                'id' => $depId,
                'constraint' => (string) $constraint,
                'plugin' => $depPlugin,
                'status' => $status,
                'is_installed' => $isInstalled,
                'is_active' => $isActive,
            ];
        }

        $dependents = $this->pluginManager->getDependents($id, onlyActive: false);
        $activeDependents = array_filter($dependents, fn ($p) => $p->isActive());

        return view('core::admin.plugins.show', [
            'plugin' => $plugin,
            'manifest' => $manifest,
            'record' => $record,
            'dependenciesHealth' => $dependenciesHealth,
            'dependents' => $dependents,
            'activeDependents' => $activeDependents,
            'isSafeMode' => $this->pluginManager->isSafeMode(),
        ]);
    }

    /**
     * Enable and activate a plugin.
     */
    public function enable(string $id): RedirectResponse
    {
        try {
            if (! $this->registry->isInstalled($id)) {
                $this->pluginManager->install($id);
            }

            $this->pluginManager->activate($id);

            return redirect()->back(fallback: route('admin.plugins.index'))
                ->with('success', __("Plugin ':id' activated successfully.", ['id' => $id]));
        } catch (Throwable $e) {
            return redirect()->back(fallback: route('admin.plugins.index'))
                ->with('error', __("Failed to activate plugin ':id': :message", ['id' => $id, 'message' => $e->getMessage()]));
        }
    }

    /**
     * Deactivate a plugin.
     */
    public function disable(Request $request, string $id): RedirectResponse
    {
        try {
            $force = $request->boolean('force', false);
            $this->pluginManager->deactivate($id, $force);

            return redirect()->back(fallback: route('admin.plugins.index'))
                ->with('success', __("Plugin ':id' deactivated successfully.", ['id' => $id]));
        } catch (Throwable $e) {
            return redirect()->back(fallback: route('admin.plugins.index'))
                ->with('error', __("Failed to deactivate plugin ':id': :message", ['id' => $id, 'message' => $e->getMessage()]));
        }
    }

    /**
     * Install a plugin.
     */
    public function install(string $id): RedirectResponse
    {
        try {
            $this->pluginManager->install($id);

            return redirect()->back(fallback: route('admin.plugins.index'))
                ->with('success', __("Plugin ':id' installed successfully.", ['id' => $id]));
        } catch (Throwable $e) {
            return redirect()->back(fallback: route('admin.plugins.index'))
                ->with('error', __("Failed to install plugin ':id': :message", ['id' => $id, 'message' => $e->getMessage()]));
        }
    }

    /**
     * Uninstall a plugin, optionally purging data.
     */
    public function uninstall(Request $request, string $id): RedirectResponse
    {
        try {
            $purgeData = $request->boolean('purge_data', false);
            $force = $request->boolean('force', false);
            $this->pluginManager->uninstall($id, $purgeData, $force);

            return redirect()->route('admin.plugins.index')
                ->with('success', __("Plugin ':id' uninstalled successfully.", ['id' => $id]));
        } catch (Throwable $e) {
            return redirect()->back(fallback: route('admin.plugins.index'))
                ->with('error', __("Failed to uninstall plugin ':id': :message", ['id' => $id, 'message' => $e->getMessage()]));
        }
    }

    /**
     * Delete an uninstalled plugin completely from the filesystem.
     */
    public function delete(string $id): RedirectResponse
    {
        try {
            $this->pluginManager->delete($id);

            return redirect()->route('admin.plugins.index')
                ->with('success', __("Extension ':id' deleted successfully.", ['id' => $id]));
        } catch (Throwable $e) {
            return redirect()->back(fallback: route('admin.plugins.index'))
                ->with('error', __("Failed to delete extension ':id': :message", ['id' => $id, 'message' => $e->getMessage()]));
        }
    }
}
