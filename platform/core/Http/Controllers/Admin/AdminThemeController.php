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
                UPLOAD_ERR_INI_SIZE => __('The file exceeds the maximum allowed size configured on the PHP server (upload_max_filesize = :size).', ['size' => $maxUpload]),
                UPLOAD_ERR_FORM_SIZE => __('The file exceeds the form maximum size directive.'),
                UPLOAD_ERR_PARTIAL => __('The file was only partially uploaded.'),
                UPLOAD_ERR_NO_FILE => __('No archive file was uploaded.'),
                UPLOAD_ERR_NO_TMP_DIR => __('The temporary folder is missing on the PHP server.'),
                UPLOAD_ERR_CANT_WRITE => __('Failed to write file to server disk.'),
                UPLOAD_ERR_EXTENSION => __('A PHP extension stopped the file upload.'),
                default => __('Error during file upload (code: :code).', ['code' => $uploadError]),
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
            'theme_archive.required' => __('Please select a ZIP archive file for the theme.'),
            'theme_archive.file' => __('The transmitted file is invalid or corrupted.'),
            'theme_archive.extensions' => __('The archive must have a .zip extension.'),
            'theme_archive.mimetypes' => __('The selected file is not recognized as a valid ZIP archive.'),
            'theme_archive.max' => __('The theme archive must not exceed 50 MB.'),
        ]);

        $file = $request->file('theme_archive');
        if (! $file instanceof UploadedFile) {
            return redirect()->back(fallback: route('admin.themes.index'))
                ->with('error', __('No valid ZIP archive file was submitted.'));
        }

        try {
            $overwrite = $request->boolean('overwrite', false);
            $manifest = $this->zipInstaller->installArchive($file, $overwrite);

            return redirect()->route('admin.themes.index')
                ->with('success', __("Theme ':name' (:version) imported and installed successfully.", [
                    'name' => $manifest->getName(),
                    'version' => $manifest->getVersion(),
                ]));
        } catch (Throwable $e) {
            return redirect()->back(fallback: route('admin.themes.index'))
                ->with('error', __('Failed to import ZIP archive: :message', ['message' => $e->getMessage()]));
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
                ->with('success', __("Theme ':name' activated successfully.", ['name' => $theme->getName()]));
        } catch (Throwable $e) {
            return redirect()->route('admin.themes.index')
                ->with('error', __("Failed to activate theme ':id': :message", ['id' => $id, 'message' => $e->getMessage()]));
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
                ->with('success', __('Theme deactivated successfully. Default rendering restored.'));
        } catch (Throwable $e) {
            return redirect()->route('admin.themes.index')
                ->with('error', __('Failed to deactivate theme: :message', ['message' => $e->getMessage()]));
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
                ->with('success', __("Theme ':id' deleted successfully.", ['id' => $id]));
        } catch (Throwable $e) {
            return redirect()->back(fallback: route('admin.themes.index'))
                ->with('error', __("Failed to delete theme ':id': :message", ['id' => $id, 'message' => $e->getMessage()]));
        }
    }
}
