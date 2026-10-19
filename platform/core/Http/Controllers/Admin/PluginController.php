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
        $request->validate([
            'plugin_archive' => ['required', 'file', 'mimes:zip', 'max:20480'],
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
                ->with('success', "Extension '{$manifest->getName()}' ({$manifest->getVersion()}) importée et installée avec succès.");
        } catch (Throwable $e) {
            return redirect()->back(fallback: route('admin.plugins.index'))
                ->with('error', "Échec de l'importation de l'archive ZIP : ".$e->getMessage());
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
            abort(404, "Plugin '{$id}' introuvable.");
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
                ->with('success', "Plugin '{$id}' activé avec succès.");
        } catch (Throwable $e) {
            return redirect()->back(fallback: route('admin.plugins.index'))
                ->with('error', "Échec de l'activation du plugin '{$id}': ".$e->getMessage());
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
                ->with('success', "Plugin '{$id}' désactivé avec succès.");
        } catch (Throwable $e) {
            return redirect()->back(fallback: route('admin.plugins.index'))
                ->with('error', "Échec de la désactivation du plugin '{$id}': ".$e->getMessage());
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
                ->with('success', "Plugin '{$id}' installé avec succès.");
        } catch (Throwable $e) {
            return redirect()->back(fallback: route('admin.plugins.index'))
                ->with('error', "Échec de l'installation du plugin '{$id}': ".$e->getMessage());
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
                ->with('success', "Plugin '{$id}' désinstallé avec succès.");
        } catch (Throwable $e) {
            return redirect()->back(fallback: route('admin.plugins.index'))
                ->with('error', "Échec de la désinstallation du plugin '{$id}': ".$e->getMessage());
        }
    }
}
