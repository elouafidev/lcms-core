<?php

declare(strict_types=1);

namespace Platform\Core\Http\Controllers\Admin;

use App\Core\Updater\CoreZipUpdater;
use App\Core\Updater\Models\UpdateHistory;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Throwable;

class CoreUpdateController extends Controller
{
    /**
     * Display current platform version, readiness checks, and update history.
     */
    public function index(): View
    {
        $currentVersion = (string) config('cms.version', '1.1.0');
        $history = UpdateHistory::with('user')->orderBy('id', 'desc')->get();

        $checks = [
            'zip_extension' => extension_loaded('zip'),
            'storage_writable' => is_writable(storage_path()),
            'max_upload_size' => ini_get('upload_max_filesize') ?: '2M',
            'post_max_size' => ini_get('post_max_size') ?: '8M',
            'memory_limit' => ini_get('memory_limit') ?: '128M',
        ];

        return view('core::admin.settings.update', [
            'currentVersion' => $currentVersion,
            'history' => $history,
            'checks' => $checks,
        ]);
    }

    /**
     * Handle upload and execution of a Core ZIP update package.
     */
    public function upload(Request $request, CoreZipUpdater $updater): RedirectResponse
    {
        $request->validate([
            'update_zip' => ['required', 'file', 'mimes:zip', 'max:51200'],
        ]);

        $uploadedFile = $request->file('update_zip');
        if (! ($uploadedFile instanceof UploadedFile) || ! $uploadedFile->isValid()) {
            return back()->with('error', 'Le fichier transmis est invalide ou corrompu.');
        }

        $tempPath = $uploadedFile->getRealPath();
        if ($tempPath === false) {
            return back()->with('error', 'Impossible de localiser le fichier temporaire sur le serveur.');
        }

        /** @var User|null $user */
        $user = Auth::user();

        try {
            $result = $updater->updateFromZip($tempPath, $user);

            return back()->with('success', "Mise à jour réussie ! LCMS Core a été mis à niveau de la version {$result['from_version']} vers la version {$result['to_version']}.");
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
