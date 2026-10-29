<?php

declare(strict_types=1);

namespace Platform\Core\Http\Controllers\Admin;

use App\Core\Recovery\SafeModeManager;
use App\Core\Security\Audit\AuditLogger;
use App\Core\Settings\Services\MailTesterService;
use App\Core\Settings\SettingManager;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class SiteSettingsController extends Controller
{
    /**
     * Display the site settings and environment diagnostics.
     */
    public function index(SettingManager $settingManager, SafeModeManager $safeModeManager): View
    {
        $settings = $settingManager->all();

        // System Health & Diagnostics
        $dbVersion = 'Inconnu';
        try {
            $pdo = DB::connection()->getPdo();
            $dbVersion = (string) $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
        } catch (Throwable) {
            $dbVersion = 'SQLite / Standard';
        }

        $diagnostics = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? php_uname('s'),
            'database_driver' => config('database.default'),
            'database_version' => $dbVersion,
            'cache_driver' => config('cache.default'),
            'queue_connection' => config('queue.default'),
            'storage_writable' => is_writable(storage_path()),
            'safe_mode_active' => $safeModeManager->isEnabled(),
        ];

        return view('core::admin.settings.site', [
            'settings' => $settings,
            'diagnostics' => $diagnostics,
        ]);
    }

    /**
     * Update global site configuration.
     */
    public function updateGeneral(Request $request, SettingManager $settingManager, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:100'],
            'site_url' => ['required', 'url', 'max:255'],
            'site_timezone' => ['required', 'string', 'timezone'],
            'site_locale' => ['required', 'string', 'in:fr,en'],
            'maintenance_mode' => ['nullable', 'boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:500'],
        ]);

        $oldValues = [
            'site_name' => $settingManager->get('site_name'),
            'site_url' => $settingManager->get('site_url'),
            'site_timezone' => $settingManager->get('site_timezone'),
            'site_locale' => $settingManager->get('site_locale'),
            'maintenance_mode' => $settingManager->get('maintenance_mode'),
            'maintenance_message' => $settingManager->get('maintenance_message'),
        ];

        $settingManager->set('site_name', $validated['site_name'], 'string', 'general');
        $settingManager->set('site_url', $validated['site_url'], 'string', 'general');
        $settingManager->set('site_timezone', $validated['site_timezone'], 'string', 'general');
        $settingManager->set('site_locale', $validated['site_locale'], 'string', 'general');
        $settingManager->set('maintenance_mode', $request->boolean('maintenance_mode'), 'boolean', 'general');
        $settingManager->set('maintenance_message', $validated['maintenance_message'] ?? null, 'string', 'general');

        $auditLogger->log(
            action: 'settings.general.updated',
            resourceType: 'Setting',
            oldValues: $oldValues,
            newValues: $validated
        );

        return back()->with('success', 'Les paramètres généraux du site ont été enregistrés.');
    }

    /**
     * Update SMTP mail server settings.
     */
    public function updateSmtp(Request $request, SettingManager $settingManager, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'mail_host' => ['required', 'string', 'max:255'],
            'mail_port' => ['required', 'integer', 'between:1,65535'],
            'mail_encryption' => ['nullable', 'string', 'in:tls,ssl,none'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name' => ['required', 'string', 'max:255'],
        ]);

        $settingManager->set('mail_host', $validated['mail_host'], 'string', 'mail');
        $settingManager->set('mail_port', (int) $validated['mail_port'], 'integer', 'mail');
        $settingManager->set('mail_encryption', $validated['mail_encryption'] === 'none' ? null : $validated['mail_encryption'], 'string', 'mail');
        $settingManager->set('mail_username', $validated['mail_username'], 'string', 'mail');

        if (! empty($validated['mail_password'])) {
            $settingManager->set('mail_password', $validated['mail_password'], 'string', 'mail', encrypt: true);
        }

        $settingManager->set('mail_from_address', $validated['mail_from_address'], 'string', 'mail');
        $settingManager->set('mail_from_name', $validated['mail_from_name'], 'string', 'mail');

        $auditLogger->log(
            action: 'settings.smtp.updated',
            resourceType: 'Setting',
            oldValues: ['mail_host' => '***', 'mail_from_address' => '***'],
            newValues: ['mail_host' => $validated['mail_host'], 'mail_from_address' => $validated['mail_from_address']]
        );

        return back()->with('success', 'La configuration SMTP a été enregistrée avec succès.');
    }

    /**
     * Test sending an email through current SMTP settings.
     */
    public function testSmtp(Request $request, MailTesterService $mailTester, SettingManager $settingManager): JsonResponse
    {
        $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        $settingManager->loadAndApplyConfigOverrides();
        $result = $mailTester->sendTestMail((string) $request->input('test_email'));

        return response()->json($result);
    }
}
