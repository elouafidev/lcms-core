<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use App\Core\Admin\NavigationRegistry;
use App\Core\Hooks\HookManager;
use App\Core\Lifecycle\PluginState;
use App\Core\PluginManager\PluginManager;
use App\Core\PluginRegistry\PluginRegistry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Plugins\Seo\Models\SeoSetting;
use Plugins\Seo\Services\SeoMetaManager;
use Tests\TestCase;

class SeoPluginTest extends TestCase
{
    use RefreshDatabase;

    private PluginManager $pluginManager;

    private PluginRegistry $pluginRegistry;

    private NavigationRegistry $navigationRegistry;

    private HookManager $hookManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginManager = $this->app->make(PluginManager::class);
        $this->pluginRegistry = $this->app->make(PluginRegistry::class);
        $this->navigationRegistry = $this->app->make(NavigationRegistry::class);
        $this->hookManager = $this->app->make(HookManager::class);

        // Ensure plugins path points to native platform/plugins directory
        config(['cms.plugins.path' => base_path('platform/plugins')]);
    }

    private function getSeoManager(): SeoMetaManager
    {
        return $this->app->make(SeoMetaManager::class);
    }

    public function test_plugin_is_discoverable_and_inactive_initially(): void
    {
        $discovered = $this->pluginRegistry->getDiscovered('lcmscore.seo');

        $this->assertNotNull($discovered);
        $this->assertSame(PluginState::AVAILABLE->value, $discovered->getState());
        $this->assertFalse(Schema::hasTable('plugin_seo_settings'));

        // cms.head filter should leave head untouched
        $originalHead = '<meta charset="UTF-8">';
        /** @var string $output */
        $output = $this->hookManager->applyFilter('cms.head', $originalHead);
        $this->assertSame($originalHead, $output);
    }

    public function test_install_and_activation_creates_table_and_navigation(): void
    {
        $this->pluginManager->install('lcmscore.seo');
        $this->pluginManager->activate('lcmscore.seo');

        $this->assertTrue(Schema::hasTable('plugin_seo_settings'));

        // Navigation registry check
        $this->assertTrue($this->navigationRegistry->has('seo-settings'));
        $item = $this->navigationRegistry->get('seo-settings');
        $this->assertNotNull($item);
        $this->assertSame('Configuration SEO', $item->getLabel());
        $this->assertSame('/admin/seo', $item->getUrl());
    }

    public function test_seo_subscriber_injects_metadata_into_cms_head_filter(): void
    {
        $this->pluginManager->install('lcmscore.seo');
        $this->pluginManager->activate('lcmscore.seo');

        $this->getSeoManager()->setTitle('Page de Contact - LCMS Core')
            ->setDescription('Prenez contact avec notre équipe technique.')
            ->setCanonical('https://example.com/contact')
            ->setOgTitle('Contactez-nous')
            ->setOgDescription('Équipe support disponible 24/7.')
            ->setOgImage('https://example.com/images/contact-og.png')
            ->setOgType('website')
            ->setSiteName('LCMS Core');

        $initialHead = '<meta charset="UTF-8">';
        /** @var string $renderedHead */
        $renderedHead = $this->hookManager->applyFilter('cms.head', $initialHead);

        $this->assertStringContainsString('<meta charset="UTF-8">', $renderedHead);
        $this->assertStringContainsString('<meta name="description" content="Prenez contact avec notre équipe technique.">', $renderedHead);
        $this->assertStringContainsString('<link rel="canonical" href="https://example.com/contact">', $renderedHead);
        $this->assertStringContainsString('<meta property="og:title" content="Contactez-nous">', $renderedHead);
        $this->assertStringContainsString('<meta property="og:description" content="Équipe support disponible 24/7.">', $renderedHead);
        $this->assertStringContainsString('<meta property="og:type" content="website">', $renderedHead);
        $this->assertStringContainsString('<meta property="og:image" content="https://example.com/images/contact-og.png">', $renderedHead);
        $this->assertStringContainsString('<meta property="og:site_name" content="LCMS Core">', $renderedHead);
    }

    public function test_seo_subscriber_uses_database_default_settings_when_page_meta_is_empty(): void
    {
        $this->pluginManager->install('lcmscore.seo');
        $this->pluginManager->activate('lcmscore.seo');

        SeoSetting::set('default_title', 'Titre Par Défaut');
        SeoSetting::set('default_description', 'Méta description par défaut du CMS.');
        SeoSetting::set('default_canonical', 'https://example.com/default');
        SeoSetting::set('site_name', 'Mon Super CMS');

        // Reset runtime values
        $this->getSeoManager()->reset();

        /** @var string $renderedHead */
        $renderedHead = $this->hookManager->applyFilter('cms.head', '');

        $this->assertStringContainsString('<meta name="description" content="Méta description par défaut du CMS.">', $renderedHead);
        $this->assertStringContainsString('<link rel="canonical" href="https://example.com/default">', $renderedHead);
        $this->assertStringContainsString('<meta property="og:title" content="Titre Par Défaut">', $renderedHead);
        $this->assertStringContainsString('<meta property="og:site_name" content="Mon Super CMS">', $renderedHead);
    }

    public function test_explicit_page_meta_overrides_database_default_settings(): void
    {
        $this->pluginManager->install('lcmscore.seo');
        $this->pluginManager->activate('lcmscore.seo');

        SeoSetting::set('default_title', 'Titre Par Défaut');
        SeoSetting::set('default_description', 'Description par défaut');

        $this->getSeoManager()->setTitle('Page Spécifique')
            ->setDescription('Description spécifique de la page');

        /** @var string $renderedHead */
        $renderedHead = $this->hookManager->applyFilter('cms.head', '');

        $this->assertStringContainsString('<meta name="description" content="Description spécifique de la page">', $renderedHead);
        $this->assertStringNotContainsString('Description par défaut', $renderedHead);
        $this->assertStringContainsString('<meta property="og:title" content="Page Spécifique">', $renderedHead);
    }

    public function test_seo_tags_are_not_injected_when_plugin_is_deactivated(): void
    {
        $this->pluginManager->install('lcmscore.seo');
        $this->pluginManager->activate('lcmscore.seo');

        $this->getSeoManager()->setTitle('Titre Actif')
            ->setDescription('Description Active');

        $baseHead = '<link rel="stylesheet" href="/app.css">';

        /** @var string $activeHead */
        $activeHead = $this->hookManager->applyFilter('cms.head', $baseHead);
        $this->assertStringContainsString('Titre Actif', $activeHead);

        // Deactivate plugin
        $this->pluginManager->deactivate('lcmscore.seo');

        // Navigation should be hidden
        $this->assertFalse($this->navigationRegistry->has('seo-settings'));

        // Head filter must leave content untouched
        /** @var string $deactivatedHead */
        $deactivatedHead = $this->hookManager->applyFilter('cms.head', $baseHead);
        $this->assertSame($baseHead, $deactivatedHead);
        $this->assertStringNotContainsString('Titre Actif', $deactivatedHead);
    }

    public function test_guest_is_redirected_to_login_on_admin_seo(): void
    {
        $this->pluginManager->install('lcmscore.seo');
        $this->pluginManager->activate('lcmscore.seo');

        $response = $this->get('/admin/seo');
        $response->assertRedirect('/login');

        $responsePost = $this->post('/admin/seo', ['default_title' => 'Test']);
        $responsePost->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_and_update_seo_settings_via_http(): void
    {
        $this->pluginManager->install('lcmscore.seo');
        $this->pluginManager->activate('lcmscore.seo');

        /** @var User $user */
        $user = User::factory()->create();

        // Check viewing admin page
        $this->actingAs($user)->get('/admin/seo')->assertOk()->assertSee('Configuration SEO');

        // Update settings via JSON API
        $response = $this->actingAs($user)->postJson('/admin/seo', [
            'default_title' => 'Mon Portail Web',
            'default_description' => 'Portail d\'actualités et de services.',
            'default_canonical' => 'https://monportail.local',
            'site_name' => 'PortailWeb',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.default_title', 'Mon Portail Web');

        $this->assertDatabaseHas('plugin_seo_settings', [
            'key' => 'default_title',
            'value' => 'Mon Portail Web',
        ]);
        $this->assertDatabaseHas('plugin_seo_settings', [
            'key' => 'site_name',
            'value' => 'PortailWeb',
        ]);
    }

    public function test_deactivation_hides_routes_and_preserves_database_data(): void
    {
        $this->pluginManager->install('lcmscore.seo');
        $this->pluginManager->activate('lcmscore.seo');

        SeoSetting::set('persisted_key', 'persisted_value');

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin/seo')->assertOk();

        // Deactivate plugin
        $this->pluginManager->deactivate('lcmscore.seo');

        // Admin route returns 404
        $this->actingAs($user)->get('/admin/seo')->assertNotFound();

        // Data preserved
        $this->assertTrue(Schema::hasTable('plugin_seo_settings'));
        $this->assertDatabaseHas('plugin_seo_settings', [
            'key' => 'persisted_key',
            'value' => 'persisted_value',
        ]);
    }

    public function test_uninstallation_with_purge_drops_table_and_cleans_record(): void
    {
        $this->pluginManager->install('lcmscore.seo');
        $this->pluginManager->activate('lcmscore.seo');

        $this->assertTrue(Schema::hasTable('plugin_seo_settings'));

        $this->pluginManager->uninstall('lcmscore.seo', purgeData: true);

        $this->assertFalse(Schema::hasTable('plugin_seo_settings'));
        $this->assertNull($this->pluginRegistry->find('lcmscore.seo'));
    }
}
