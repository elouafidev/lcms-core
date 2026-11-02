<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use Core\Admin\NavigationRegistry;
use Core\Hooks\HookManager;
use Core\Lifecycle\PluginState;
use Core\Models\User;
use Core\Permissions\PermissionRegistry;
use Core\PluginManager\PluginManager;
use Core\PluginRegistry\PluginRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Plugins\I18n\Models\Language;
use Plugins\I18n\Models\Translation;
use Plugins\I18n\Services\LanguageManager;
use Tests\TestCase;

class I18nPluginTest extends TestCase
{
    use RefreshDatabase;

    private PluginManager $pluginManager;

    private PluginRegistry $pluginRegistry;

    private NavigationRegistry $navigationRegistry;

    private PermissionRegistry $permissionRegistry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginManager = $this->app->make(PluginManager::class);
        $this->pluginRegistry = $this->app->make(PluginRegistry::class);
        $this->navigationRegistry = $this->app->make(NavigationRegistry::class);
        $this->permissionRegistry = $this->app->make(PermissionRegistry::class);

        config(['cms.plugins.path' => base_path('Plugins')]);
    }

    public function test_plugin_is_discoverable_and_inactive_initially(): void
    {
        $discovered = $this->pluginRegistry->getDiscovered('lcmscore.i18n');

        $this->assertNotNull($discovered);
        $this->assertSame(PluginState::AVAILABLE->value, $discovered->getState());
        $this->assertFalse(Schema::hasTable('plugin_i18n_languages'));
        $this->assertFalse(Schema::hasTable('plugin_i18n_translations'));
        $this->assertFalse($this->navigationRegistry->has('i18n-management'));
    }

    public function test_install_and_activation_creates_tables_and_navigation(): void
    {
        $this->pluginManager->install('lcmscore.i18n');
        $this->pluginManager->activate('lcmscore.i18n');

        $this->assertTrue(Schema::hasTable('plugin_i18n_languages'));
        $this->assertTrue(Schema::hasTable('plugin_i18n_translations'));

        // Navigation check
        $this->assertTrue($this->navigationRegistry->has('i18n-management'));
        $item = $this->navigationRegistry->get('i18n-management');
        $this->assertNotNull($item);
        $this->assertSame('Langues & i18n', $item->getLabel());
        $this->assertSame('/admin/i18n/languages', $item->getUrl());
        $this->assertCount(2, $item->getChildren());

        // Permissions check
        $this->assertTrue($this->permissionRegistry->has('i18n.languages.manage'));
        $this->assertTrue($this->permissionRegistry->has('i18n.translations.manage'));
    }

    public function test_admin_can_add_languages_and_set_default(): void
    {
        $this->pluginManager->install('lcmscore.i18n');
        $this->pluginManager->activate('lcmscore.i18n');

        /** @var User $user */
        $user = User::factory()->create();

        // Index view
        $this->actingAs($user)->get('/admin/i18n/languages')->assertOk();

        // Add French
        $this->actingAs($user)->post('/admin/i18n/languages', [
            'name' => 'Français',
            'code' => 'fr',
            'locale' => 'fr_FR',
            'flag' => '🇫🇷',
            'is_default' => true,
            'is_rtl' => false,
            'order' => 1,
        ])->assertRedirect('/admin/i18n/languages');

        $this->assertDatabaseHas('plugin_i18n_languages', [
            'code' => 'fr',
            'is_default' => 1,
        ]);

        // Add English
        $this->actingAs($user)->post('/admin/i18n/languages', [
            'name' => 'English',
            'code' => 'en',
            'locale' => 'en_US',
            'flag' => '🇬🇧',
            'is_default' => false,
            'order' => 2,
        ])->assertRedirect('/admin/i18n/languages');

        /** @var Language $en */
        $en = Language::query()->where('code', 'en')->firstOrFail();
        $this->assertFalse($en->is_default);

        // Make English default
        $this->actingAs($user)->post('/admin/i18n/languages/'.$en->id.'/default')
            ->assertRedirect('/admin/i18n/languages');

        $en->refresh();
        $this->assertTrue($en->is_default);

        /** @var Language $fr */
        $fr = Language::query()->where('code', 'fr')->firstOrFail();
        $this->assertFalse($fr->is_default);
    }

    public function test_public_user_can_switch_language_via_route(): void
    {
        $this->pluginManager->install('lcmscore.i18n');
        $this->pluginManager->activate('lcmscore.i18n');

        Language::create(['name' => 'Français', 'code' => 'fr', 'locale' => 'fr_FR', 'is_default' => true, 'is_active' => true]);
        Language::create(['name' => 'English', 'code' => 'en', 'locale' => 'en_US', 'is_default' => false, 'is_active' => true]);

        $response = $this->get('/lang/en');
        $response->assertRedirect('/');
        $response->assertSessionHas('cms_locale', 'en');
    }

    public function test_translation_storage_and_manager_fallback(): void
    {
        $this->pluginManager->install('lcmscore.i18n');
        $this->pluginManager->activate('lcmscore.i18n');

        Language::create(['name' => 'Français', 'code' => 'fr', 'locale' => 'fr_FR', 'is_default' => true, 'is_active' => true]);
        Language::create(['name' => 'English', 'code' => 'en', 'locale' => 'en_US', 'is_default' => false, 'is_active' => true]);

        // Store translation
        Translation::setFor('page', 1, 'en', 'title', 'Welcome to our platform');
        Translation::setFor('page', 1, 'fr', 'title', 'Bienvenue sur notre plateforme');

        /** @var LanguageManager $manager */
        $manager = $this->app->make(LanguageManager::class);

        $enTitle = $manager->translate('page', 1, 'title', 'Default Title', 'en');
        $this->assertSame('Welcome to our platform', $enTitle);

        $frTitle = $manager->translate('page', 1, 'title', 'Default Title', 'fr');
        $this->assertSame('Bienvenue sur notre plateforme', $frTitle);

        // Missing translation with fallback
        $missing = $manager->translate('page', 1, 'unknown_field', 'Fallback Value', 'en');
        $this->assertSame('Fallback Value', $missing);
    }

    public function test_blade_directive_renders_language_switcher(): void
    {
        $this->pluginManager->install('lcmscore.i18n');
        $this->pluginManager->activate('lcmscore.i18n');

        Language::create(['name' => 'Français', 'code' => 'fr', 'locale' => 'fr_FR', 'flag' => '🇫🇷', 'is_default' => true, 'is_active' => true]);
        Language::create(['name' => 'English', 'code' => 'en', 'locale' => 'en_US', 'flag' => '🇬🇧', 'is_default' => false, 'is_active' => true]);

        $html = Blade::render('@langSwitcher');

        $this->assertStringContainsString('lcms-language-switcher', $html);
        $this->assertStringContainsString('/lang/en', $html);
        $this->assertStringContainsString('English', $html);
        $this->assertStringContainsString('Français', $html);
    }

    public function test_cms_head_hook_injects_hreflang_tags(): void
    {
        $this->pluginManager->install('lcmscore.i18n');
        $this->pluginManager->activate('lcmscore.i18n');

        Language::create(['name' => 'Français', 'code' => 'fr', 'locale' => 'fr_FR', 'is_default' => true, 'is_active' => true]);
        Language::create(['name' => 'English', 'code' => 'en', 'locale' => 'en_US', 'is_default' => false, 'is_active' => true]);

        /** @var HookManager $hookManager */
        $hookManager = $this->app->make(HookManager::class);

        $head = (string) $hookManager->applyFilter('cms.head', '<meta charset="utf-8">');

        $this->assertStringContainsString('hreflang="fr"', $head);
        $this->assertStringContainsString('hreflang="en"', $head);
        $this->assertStringContainsString('hreflang="x-default"', $head);
    }

    public function test_uninstall_with_purge_drops_tables_and_navigation(): void
    {
        $this->pluginManager->install('lcmscore.i18n');
        $this->pluginManager->activate('lcmscore.i18n');

        $this->assertTrue(Schema::hasTable('plugin_i18n_languages'));

        $this->pluginManager->uninstall('lcmscore.i18n', purgeData: true);

        $this->assertFalse(Schema::hasTable('plugin_i18n_languages'));
        $this->assertFalse(Schema::hasTable('plugin_i18n_translations'));
        $this->assertFalse($this->navigationRegistry->has('i18n-management'));
        $this->assertFalse($this->permissionRegistry->has('i18n.languages.manage'));
    }
}
