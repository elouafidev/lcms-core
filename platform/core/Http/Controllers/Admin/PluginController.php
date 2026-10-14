<?php

declare(strict_types=1);

namespace Platform\Core\Http\Controllers\Admin;

use App\Core\PluginManager\PluginManager;
use App\Core\PluginRegistry\PluginRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Throwable;

class PluginController extends Controller
{
    public function __construct(
        private PluginRegistry $registry,
        private PluginManager $pluginManager
    ) {}

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
     * Enable and activate a plugin.
     */
    public function enable(string $id): RedirectResponse
    {
        try {
            if (! $this->registry->isInstalled($id)) {
                $this->pluginManager->install($id);
            }

            $this->pluginManager->activate($id);

            return redirect()->route('admin.plugins.index')
                ->with('success', "Plugin '{$id}' activé avec succès.");
        } catch (Throwable $e) {
            return redirect()->route('admin.plugins.index')
                ->with('error', "Échec de l'activation du plugin '{$id}': ".$e->getMessage());
        }
    }

    /**
     * Deactivate a plugin.
     */
    public function disable(string $id): RedirectResponse
    {
        try {
            $this->pluginManager->deactivate($id);

            return redirect()->route('admin.plugins.index')
                ->with('success', "Plugin '{$id}' désactivé avec succès.");
        } catch (Throwable $e) {
            return redirect()->route('admin.plugins.index')
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

            return redirect()->route('admin.plugins.index')
                ->with('success', "Plugin '{$id}' installé avec succès.");
        } catch (Throwable $e) {
            return redirect()->route('admin.plugins.index')
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
            $this->pluginManager->uninstall($id, $purgeData);

            return redirect()->route('admin.plugins.index')
                ->with('success', "Plugin '{$id}' désinstallé avec succès.");
        } catch (Throwable $e) {
            return redirect()->route('admin.plugins.index')
                ->with('error', "Échec de la désinstallation du plugin '{$id}': ".$e->getMessage());
        }
    }
}
