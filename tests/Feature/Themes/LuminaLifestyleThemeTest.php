<?php

declare(strict_types=1);

namespace Tests\Feature\Themes;

use Core\PluginManager\PluginManager;
use Core\Themes\ThemeAssetManager;
use Core\Themes\ThemeDiscovery;
use Core\Themes\ThemeManager;
use Database\Seeders\LuminaDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LuminaLifestyleThemeTest extends TestCase
{
    use RefreshDatabase;

    private PluginManager $pluginManager;

    private ThemeManager $themeManager;

    private ThemeDiscovery $discovery;

    private ThemeAssetManager $assetManager;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cms.plugins.path' => base_path('Plugins')]);
        config(['cms.themes.path' => base_path('Themes')]);

        $this->pluginManager = $this->app->make(PluginManager::class);
        $this->themeManager = $this->app->make(ThemeManager::class);
        $this->discovery = $this->themeManager->getDiscovery();
        $this->assetManager = $this->themeManager->getAssetManager();

        // Install & Activate required plugins for full theme integration
        $plugins = [
            'lcmscore.pages',
            'lcmscore.menus',
            'lcmscore.media-library',
            'lcmscore.seo',
            'lcmscore.forms',
            'lcmscore.blog',
            'lcmscore.blocks',
            'lcmscore.i18n',
            'lcmscore.search',
            'lcmscore.newsletter',
            'lcmscore.redirects',
            'lcmscore.analytics',
            'lcmscore.api-headless',
            'lcmscore.commerce-lite',
        ];

        foreach ($plugins as $pluginId) {
            $this->pluginManager->install($pluginId);
            $this->pluginManager->activate($pluginId);
        }
    }

    protected function tearDown(): void
    {
        if ($this->themeManager->hasActiveTheme()) {
            $this->themeManager->deactivate();
        }

        $this->assetManager->clean('lumina-lifestyle');

        parent::tearDown();
    }

    public function test_lumina_lifestyle_theme_is_discoverable_and_valid(): void
    {
        $theme = $this->discovery->get('lumina-lifestyle');

        $this->assertNotNull($theme);
        $this->assertSame('lumina-lifestyle', $theme->getId());
        $this->assertSame('Lumina Lifestyle', $theme->getName());

        $manifest = $theme->getManifest();
        $this->assertSame('1.0.0', $manifest->getVersion());
        $this->assertSame('LCMS Core Design Team', $manifest->getAuthor()['name']);
        $this->assertSame('css/lumina.css', $manifest->getAssets()['css']);
        $this->assertSame('#c25e38', $manifest->getSettings()['accent_color']);
        $this->assertNull($manifest->getParentTheme());
    }

    public function test_lumina_lifestyle_theme_activation_publishes_assets_and_registers_views(): void
    {
        $theme = $this->themeManager->activate('lumina-lifestyle');

        $this->assertTrue($theme->isActive());
        $this->assertTrue($this->themeManager->hasActiveTheme());
        $this->assertSame('lumina-lifestyle', $this->themeManager->getActiveThemeId());

        $this->assertTrue($this->assetManager->hasPublished('lumina-lifestyle'));
        $publishedCss = $this->assetManager->getPublishedPath('lumina-lifestyle', 'css/lumina.css');
        $this->assertFileExists($publishedCss);
    }

    public function test_demo_seeder_populates_coordinated_content_across_all_plugins(): void
    {
        $seeder = new LuminaDemoSeeder;
        $seeder->run();

        // 1. Languages
        $this->assertDatabaseHas('plugin_i18n_languages', ['code' => 'fr', 'is_default' => 1]);
        $this->assertDatabaseHas('plugin_i18n_languages', ['code' => 'en', 'is_default' => 0]);

        // 2. Menus
        $this->assertDatabaseHas('plugin_menus', ['slug' => 'main-nav']);
        $this->assertDatabaseHas('plugin_menus', ['slug' => 'footer-nav']);

        // 3. Contact Form
        $this->assertDatabaseHas('plugin_forms', ['slug' => 'contact']);

        // 4. Content Blocks
        $this->assertDatabaseHas('plugin_blocks', ['slug' => 'home-hero']);
        $this->assertDatabaseHas('plugin_blocks', ['slug' => 'brand-values']);
        $this->assertDatabaseHas('plugin_blocks', ['slug' => 'testimonials']);

        // 5. Pages
        $this->assertDatabaseHas('plugin_pages', ['slug' => 'a-propos']);
        $this->assertDatabaseHas('plugin_pages', ['slug' => 'contact', 'template' => 'contact']);
        $this->assertDatabaseHas('plugin_pages', ['slug' => 'mentions-legales']);
        $this->assertDatabaseHas('plugin_pages', ['slug' => 'cgv']);

        // 6. Blog
        $this->assertDatabaseHas('plugin_blog_categories', ['slug' => 'art-de-vivre']);
        $this->assertDatabaseHas('plugin_blog_posts', ['slug' => 'bienfaits-cafe-filtre-extraction-lente']);
        $this->assertDatabaseCount('plugin_blog_posts', 6);

        // 7. Commerce Products & Orders
        $this->assertDatabaseHas('plugin_commerce_products', ['sku' => 'CER-MUG-01', 'price' => 2600]);
        $this->assertDatabaseCount('plugin_commerce_products', 8);
        $this->assertDatabaseCount('plugin_commerce_orders', 3);

        // 8. Newsletter
        $this->assertDatabaseHas('plugin_newsletter_subscribers', ['email' => 'lea.bernard@example.com', 'status' => 'confirmed']);

        // 9. Analytics
        $this->assertDatabaseCount('plugin_analytics_pageviews', 30);
    }

    public function test_homepage_renders_hybrid_magazine_and_storefront_components(): void
    {
        $this->themeManager->activate('lumina-lifestyle');

        $seeder = new LuminaDemoSeeder;
        $seeder->run();

        $response = $this->get('/');
        $response->assertOk();

        // Brand & Theme Identity
        $response->assertSee('Lumina');
        $response->assertSee('Lifestyle');

        // Navigation elements
        $response->assertSee('Boutique');
        $response->assertSee('Le Journal');

        // Products Section
        $response->assertSee('Nos Créations Artisanales');
        $response->assertSee('Tasse en Grès Tourné Main');
        $response->assertSee('26,00 €');

        // Blog Section
        $response->assertSee('Chroniques');
        $response->assertSee('Slow Living');
        $response->assertSee('Les bienfaits insoupçonnés du café filtre');

        // Newsletter Section
        $response->assertSee('La Lettre Lumina');
    }

    public function test_blog_views_override_displays_editorial_magazine_layout(): void
    {
        $this->themeManager->activate('lumina-lifestyle');

        $seeder = new LuminaDemoSeeder;
        $seeder->run();

        // Blog Index
        $response = $this->get('/blog');
        $response->assertOk();
        $response->assertSee('Le Journal Slow Living');
        $response->assertSee('Art de Vivre');
        $response->assertSee('Artisanat d\'Art');

        // Single Post
        $postResponse = $this->get('/blog/bienfaits-cafe-filtre-extraction-lente');
        $postResponse->assertOk();
        $postResponse->assertSee('Les bienfaits insoupçonnés du café filtre en extraction lente');
        $postResponse->assertSee('min de lecture');
        $postResponse->assertSee('Slow-Living');
    }

    public function test_shop_views_override_displays_artisan_storefront_and_cart(): void
    {
        $this->themeManager->activate('lumina-lifestyle');

        $seeder = new LuminaDemoSeeder;
        $seeder->run();

        // 1. Shop Catalog
        $shopResponse = $this->get('/shop');
        $shopResponse->assertOk();
        $shopResponse->assertSee('Catalogue &amp; Éco-Conception', false);
        $shopResponse->assertSee('Tasse en Grès Tourné Main');
        $shopResponse->assertSee('26,00 €');

        // 2. Product Detail
        $detailResponse = $this->get('/shop/tasse-gres-tourne-main');
        $detailResponse->assertOk();
        $detailResponse->assertSee('Tasse en Grès Tourné Main');
        $detailResponse->assertSee('CER-MUG-01');
        $detailResponse->assertSee('26,00 €');
        $detailResponse->assertSee('Ajouter au panier');

        // 3. Add to Cart
        $product = \Plugins\CommerceLite\Models\Product::query()->where('sku', 'CER-MUG-01')->firstOrFail();
        $addResponse = $this->from('/shop')->post('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $addResponse->assertRedirect('/shop');

        // 4. Cart View
        $cartResponse = $this->get('/cart');
        $cartResponse->assertOk();
        $cartResponse->assertSee('Votre Panier');
        $cartResponse->assertSee('Tasse en Grès Tourné Main');
        $cartResponse->assertSee('52,00 €'); // 2 * 26.00 €

        // 5. Checkout View
        $checkoutResponse = $this->get('/checkout');
        $checkoutResponse->assertOk();
        $checkoutResponse->assertSee('Finalisation de votre Commande');
        $checkoutResponse->assertSee('52,00 €');
    }

    public function test_pages_override_displays_contact_page_with_rendered_form(): void
    {
        $this->themeManager->activate('lumina-lifestyle');

        $seeder = new LuminaDemoSeeder;
        $seeder->run();

        // 1. Standard Page
        $aboutResponse = $this->get('/page/a-propos');
        $aboutResponse->assertOk();
        $aboutResponse->assertSee('Notre Histoire — L\'Atelier Lumina');

        // 2. Contact Page with Rendered Form
        $contactResponse = $this->get('/page/contact');
        $contactResponse->assertOk();
        $contactResponse->assertSee('Nous Contacter');
        $contactResponse->assertSee('Votre Nom complet');
        $contactResponse->assertSee('Adresse e-mail');
        $contactResponse->assertSee('Votre Message');
    }

    public function test_theme_deactivation_restores_clean_state(): void
    {
        $this->themeManager->activate('lumina-lifestyle');
        $this->assertTrue($this->themeManager->hasActiveTheme());

        $this->themeManager->deactivate();
        $this->assertFalse($this->themeManager->hasActiveTheme());
        $this->assertNull($this->themeManager->getActiveThemeId());
    }
}
