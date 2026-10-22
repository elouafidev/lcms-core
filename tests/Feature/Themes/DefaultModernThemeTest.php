<?php

declare(strict_types=1);

namespace Tests\Feature\Themes;

use App\Core\PluginManager\PluginManager;
use App\Core\Themes\ThemeAssetManager;
use App\Core\Themes\ThemeDiscovery;
use App\Core\Themes\ThemeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Plugins\Menus\Models\Menu;
use Plugins\Menus\Models\MenuItem;
use Plugins\Pages\Models\Page;
use Plugins\Seo\Models\SeoSetting;
use Plugins\Seo\Services\SeoMetaManager;
use Tests\TestCase;

class DefaultModernThemeTest extends TestCase
{
    use RefreshDatabase;

    private PluginManager $pluginManager;

    private ThemeManager $themeManager;

    private ThemeDiscovery $discovery;

    private ThemeAssetManager $assetManager;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cms.plugins.path' => base_path('platform/plugins')]);
        config(['cms.themes.path' => base_path('platform/themes')]);

        $this->pluginManager = $this->app->make(PluginManager::class);
        $this->themeManager = $this->app->make(ThemeManager::class);
        $this->discovery = $this->themeManager->getDiscovery();
        $this->assetManager = $this->themeManager->getAssetManager();
    }

    protected function tearDown(): void
    {
        if ($this->themeManager->hasActiveTheme()) {
            $this->themeManager->deactivate();
        }

        $this->assetManager->clean('default-modern');

        parent::tearDown();
    }

    public function test_default_modern_theme_is_discoverable_and_valid(): void
    {
        $theme = $this->discovery->get('default-modern');

        $this->assertNotNull($theme);
        $this->assertSame('default-modern', $theme->getId());
        $this->assertSame('Default Modern', $theme->getName());

        $manifest = $theme->getManifest();
        $this->assertSame('1.0.0', $manifest->getVersion());
        $this->assertSame('CMS Core Team', $manifest->getAuthor()['name']);
        $this->assertSame('css/theme.css', $manifest->getAssets()['css']);
        $this->assertSame('#2563eb', $manifest->getSettings()['primary_color']);
        $this->assertNull($manifest->getParentTheme());
    }

    public function test_default_modern_theme_activation_publishes_assets_and_registers_views(): void
    {
        $theme = $this->themeManager->activate('default-modern');

        $this->assertTrue($theme->isActive());
        $this->assertTrue($this->themeManager->hasActiveTheme());
        $this->assertSame('default-modern', $this->themeManager->getActiveThemeId());

        $this->assertTrue($this->assetManager->hasPublished('default-modern'));
        $publishedCss = $this->assetManager->getPublishedPath('default-modern', 'css/theme.css');
        $this->assertFileExists($publishedCss);
    }

    public function test_page_rendering_with_default_modern_theme_uses_theme_layout_and_styles(): void
    {
        // 1. Install and activate pages plugin
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

        // 2. Activate default-modern theme
        $this->themeManager->activate('default-modern');

        // 3. Create published page
        Page::query()->create([
            'title' => 'Bienvenue sur notre site',
            'slug' => 'bienvenue',
            'content' => '<p>Ceci est une page servie avec le thème moderne.</p>',
            'status' => 'published',
            'is_published' => true,
        ]);

        // 4. Request the public page route
        $response = $this->get('/page/bienvenue');

        $response->assertOk();
        $content = $response->getContent();
        $this->assertIsString($content);

        // Check layout structure
        $this->assertStringContainsString('class="cms-theme-default-modern"', $content);
        $this->assertStringContainsString('class="cms-header"', $content);
        $this->assertStringContainsString('class="cms-brand"', $content);
        $this->assertStringContainsString('class="cms-navbar"', $content);
        $this->assertStringContainsString('class="cms-main"', $content);
        $this->assertStringContainsString('class="cms-footer"', $content);

        // Check page template override
        $this->assertStringContainsString('class="cms-page"', $content);
        $this->assertStringContainsString('class="cms-page-title"', $content);
        $this->assertStringContainsString('Bienvenue sur notre site', $content);
        $this->assertStringContainsString('Ceci est une page servie avec le thème moderne.', $content);

        // Check stylesheet link
        $this->assertStringContainsString('vendor/themes/default-modern/css/theme.css', $content);
    }

    public function test_page_rendering_integrates_seo_metadata_via_cms_head_hook(): void
    {
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

        $this->pluginManager->install('lcmscore.seo');
        $this->pluginManager->activate('lcmscore.seo');

        $this->themeManager->activate('default-modern');

        // Configure SEO settings in database
        SeoSetting::set('default_description', 'Découvrez notre CMS modulaire haute performance.');
        SeoSetting::set('site_name', 'Mon CMS Modulaire');
        SeoSetting::set('default_canonical', 'https://example.com');

        /** @var SeoMetaManager $metaManager */
        $metaManager = $this->app->make(SeoMetaManager::class);
        $metaManager->reset();

        Page::query()->create([
            'title' => 'À Propos de Nous',
            'slug' => 'a-propos',
            'content' => '<p>Notre mission et nos valeurs.</p>',
            'status' => 'published',
            'is_published' => true,
        ]);

        $response = $this->get('/page/a-propos');

        $response->assertOk();
        $content = $response->getContent();
        $this->assertIsString($content);

        // Assert SEO metadata injected through cms.head hook filter
        $this->assertStringContainsString('<meta name="description" content="Découvrez notre CMS modulaire haute performance.">', $content);
        $this->assertStringContainsString('<meta property="og:site_name" content="Mon CMS Modulaire">', $content);
    }

    public function test_page_rendering_integrates_dynamic_navigation_menu_via_cms_menu_hook(): void
    {
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

        $this->pluginManager->install('lcmscore.menus');
        $this->pluginManager->activate('lcmscore.menus');

        $this->themeManager->activate('default-modern');

        // Create main navigation menu
        /** @var Menu $menu */
        $menu = Menu::query()->create([
            'name' => 'Menu Principal',
            'slug' => 'main-nav',
            'is_active' => true,
        ]);

        MenuItem::query()->create([
            'menu_id' => $menu->id,
            'title' => 'Accueil',
            'url' => '/',
            'order' => 1,
            'is_active' => true,
        ]);

        MenuItem::query()->create([
            'menu_id' => $menu->id,
            'title' => 'Blog',
            'url' => '/blog',
            'order' => 2,
            'is_active' => true,
        ]);

        Page::query()->create([
            'title' => 'Services',
            'slug' => 'services',
            'content' => '<p>Nos offres de conseil.</p>',
            'status' => 'published',
            'is_published' => true,
        ]);

        $response = $this->get('/page/services');

        $response->assertOk();
        $content = $response->getContent();
        $this->assertIsString($content);

        // Assert menu HTML dynamically injected into navbar
        $this->assertStringContainsString('class="menu-list"', $content);
        $this->assertStringContainsString('<a href="/" target="_self">Accueil</a>', $content);
        $this->assertStringContainsString('<a href="/blog" target="_self">Blog</a>', $content);
    }

    public function test_end_to_end_full_stack_integration_with_pages_menus_and_seo(): void
    {
        // 1. Activate all 3 essential plugins and theme
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

        $this->pluginManager->install('lcmscore.menus');
        $this->pluginManager->activate('lcmscore.menus');

        $this->pluginManager->install('lcmscore.seo');
        $this->pluginManager->activate('lcmscore.seo');

        $this->themeManager->activate('default-modern');

        // 2. Setup Menu
        $menu = Menu::query()->create([
            'name' => 'Header Navigation',
            'slug' => 'main-nav',
            'is_active' => true,
        ]);
        MenuItem::query()->create([
            'menu_id' => $menu->id,
            'title' => 'Contact',
            'url' => '/page/contact',
            'order' => 1,
            'is_active' => true,
        ]);

        // 3. Setup SEO
        SeoSetting::set('default_description', 'Contactez notre support technique disponible 24/7.');
        SeoSetting::set('site_name', 'Tech Enterprise');
        /** @var SeoMetaManager $metaManager */
        $metaManager = $this->app->make(SeoMetaManager::class);
        $metaManager->reset();

        // 4. Create Page
        Page::query()->create([
            'title' => 'Contactez-nous',
            'slug' => 'contact',
            'content' => '<p>Formulaire de contact et coordonnées.</p>',
            'status' => 'published',
            'is_published' => true,
        ]);

        // 5. Access route and verify full integration
        $response = $this->get('/page/contact');

        $response->assertOk();
        $html = (string) $response->getContent();

        // 1. Layout & Structure
        $this->assertStringContainsString('<body class="cms-theme-default-modern">', $html);
        $this->assertStringContainsString('class="cms-header"', $html);
        $this->assertStringContainsString('class="cms-footer"', $html);

        // 2. Stylesheet
        $this->assertStringContainsString('vendor/themes/default-modern/css/theme.css', $html);

        // 3. Dynamic Menu
        $this->assertStringContainsString('<a href="/page/contact" target="_self">Contact</a>', $html);

        // 4. SEO in Head
        $this->assertStringContainsString('<meta name="description" content="Contactez notre support technique disponible 24/7.">', $html);
        $this->assertStringContainsString('<meta property="og:site_name" content="Tech Enterprise">', $html);

        // 5. Content
        $this->assertStringContainsString('<h1 class="cms-page-title">Contactez-nous</h1>', $html);
        $this->assertStringContainsString('<p>Formulaire de contact et coordonnées.</p>', $html);
    }
}
