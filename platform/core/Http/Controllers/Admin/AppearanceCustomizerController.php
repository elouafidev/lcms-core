<?php

declare(strict_types=1);

namespace Platform\Core\Http\Controllers\Admin;

use App\Core\Security\Audit\AuditLogger;
use App\Core\Settings\SettingManager;
use App\Core\Themes\ThemeManager;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AppearanceCustomizerController extends Controller
{
    /**
     * Display the appearance customizer view with current CSS, JS and Head tags.
     */
    public function index(SettingManager $settings, ThemeManager $themeManager): View
    {
        $customCss = (string) $settings->get('appearance_custom_css', '');
        $customJs = (string) $settings->get('appearance_custom_js', '');
        $customHeadTags = (string) $settings->get('appearance_custom_head_tags', '');
        $activeTheme = $themeManager->getActiveTheme();

        return view('core::admin.appearance.customize', [
            'customCss' => $customCss,
            'customJs' => $customJs,
            'customHeadTags' => $customHeadTags,
            'activeTheme' => $activeTheme,
        ]);
    }

    /**
     * Update the appearance customizer settings.
     */
    public function update(Request $request, SettingManager $settings, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'custom_css' => ['nullable', 'string', 'max:65535'],
            'custom_js' => ['nullable', 'string', 'max:65535'],
            'custom_head_tags' => ['nullable', 'string', 'max:65535'],
        ]);

        $customCss = (string) ($validated['custom_css'] ?? '');
        $customJs = (string) ($validated['custom_js'] ?? '');
        $customHeadTags = (string) ($validated['custom_head_tags'] ?? '');

        $settings->set('appearance_custom_css', $customCss);
        $settings->set('appearance_custom_js', $customJs);
        $settings->set('appearance_custom_head_tags', $customHeadTags);

        $auditLogger->log(
            action: 'appearance.customize.updated',
            resourceType: 'Appearance',
            resourceId: 'customizer',
            newValues: [
                'has_custom_css' => trim($customCss) !== '',
                'has_custom_js' => trim($customJs) !== '',
                'has_custom_head_tags' => trim($customHeadTags) !== '',
            ],
            user: $request->user()
        );

        return redirect()->route('admin.appearance.customize')
            ->with('success', __('Appearance customization settings saved successfully.'));
    }
}
