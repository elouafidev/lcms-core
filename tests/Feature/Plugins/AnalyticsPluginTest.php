<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use Core\Admin\NavigationRegistry;
use Core\Lifecycle\PluginState;
use Core\Models\User;
use Core\Permissions\PermissionRegistry;
use Core\PluginManager\PluginManager;
use Core\PluginRegistry\PluginRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Plugins\Analytics\Models\PageView;
use Plugins\Analytics\Services\UserAgentParser;
use Tests\TestCase;

class AnalyticsPluginTest extends TestCase
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
        $discovered = $this->pluginRegistry->getDiscovered('lcmscore.analytics');

        $this->assertNotNull($discovered);
        $this->assertSame(PluginState::AVAILABLE->value, $discovered->getState());
        $this->assertFalse(Schema::hasTable('plugin_analytics_pageviews'));
        $this->assertFalse($this->navigationRegistry->has('analytics-management'));

        // Admin route should be 404 before activation
        $this->get('/admin/analytics')->assertNotFound();
    }

    public function test_install_and_activation_creates_tables_and_navigation(): void
    {
        $this->pluginManager->install('lcmscore.analytics');
        $this->pluginManager->activate('lcmscore.analytics');

        $this->assertTrue(Schema::hasTable('plugin_analytics_pageviews'));

        // Navigation check
        $this->assertTrue($this->navigationRegistry->has('analytics-management'));
        $item = $this->navigationRegistry->get('analytics-management');
        $this->assertNotNull($item);
        $this->assertSame('Statistiques & Audience', $item->getLabel());
        $this->assertSame('/admin/analytics', $item->getUrl());

        // Permissions check
        $this->assertTrue($this->permissionRegistry->has('analytics.view'));
        $this->assertTrue($this->permissionRegistry->has('analytics.reset'));
    }

    public function test_public_pageview_is_tracked_anonymously(): void
    {
        $this->pluginManager->install('lcmscore.analytics');
        $this->pluginManager->activate('lcmscore.analytics');

        $response = $this->get('/');
        $response->assertOk();

        $this->assertDatabaseHas('plugin_analytics_pageviews', [
            'path' => '/',
            'status_code' => 200,
        ]);

        $pageview = PageView::query()->first();
        $this->assertNotNull($pageview);
        $this->assertSame(64, strlen($pageview->visitor_hash));
        $this->assertSame('desktop', $pageview->device);
    }

    public function test_admin_requests_and_admin_users_are_excluded_from_tracking(): void
    {
        $this->pluginManager->install('lcmscore.analytics');
        $this->pluginManager->activate('lcmscore.analytics');

        /** @var User $admin */
        $admin = User::factory()->create();

        // 1. Logged in admin visiting public home page is NOT tracked
        $this->actingAs($admin)->get('/');
        $this->assertSame(0, PageView::query()->count());

        // 2. Visiting admin backend paths is NOT tracked
        $this->get('/admin/login');
        $this->assertSame(0, PageView::query()->count());
    }

    public function test_bots_and_crawlers_are_excluded_from_tracking(): void
    {
        $this->pluginManager->install('lcmscore.analytics');
        $this->pluginManager->activate('lcmscore.analytics');

        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ])->get('/');

        $this->assertSame(0, PageView::query()->count());
    }

    public function test_device_and_browser_detection(): void
    {
        // 1. iPhone Mobile Safari
        $iphoneUA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Mobile/15E148 Safari/604.1';
        $this->assertSame('mobile', UserAgentParser::detectDevice($iphoneUA));
        $this->assertSame('Safari', UserAgentParser::detectBrowser($iphoneUA));

        // 2. iPad Tablet
        $ipadUA = 'Mozilla/5.0 (iPad; CPU OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1';
        $this->assertSame('tablet', UserAgentParser::detectDevice($ipadUA));

        // 3. Desktop Edge
        $edgeUA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36 Edg/114.0.1823.43';
        $this->assertSame('desktop', UserAgentParser::detectDevice($edgeUA));
        $this->assertSame('Edge', UserAgentParser::detectBrowser($edgeUA));

        // 4. Desktop Firefox
        $firefoxUA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/114.0';
        $this->assertSame('desktop', UserAgentParser::detectDevice($firefoxUA));
        $this->assertSame('Firefox', UserAgentParser::detectBrowser($firefoxUA));
    }

    public function test_referrer_host_extraction(): void
    {
        $this->pluginManager->install('lcmscore.analytics');
        $this->pluginManager->activate('lcmscore.analytics');

        $this->withHeaders([
            'Referer' => 'https://www.google.fr/search?q=lcms+cms',
        ])->get('/');

        $pageview = PageView::query()->first();
        $this->assertNotNull($pageview);
        $this->assertSame('google.fr', $pageview->referrer_host);
    }

    public function test_admin_can_view_analytics_dashboard_and_periods(): void
    {
        $this->pluginManager->install('lcmscore.analytics');
        $this->pluginManager->activate('lcmscore.analytics');

        /** @var User $admin */
        $admin = User::factory()->create();

        // Seed some pageviews
        PageView::create([
            'path' => '/a-propos',
            'visitor_hash' => 'hash_user_1',
            'referrer_host' => 'google.com',
            'device' => 'desktop',
            'browser' => 'Chrome',
            'status_code' => 200,
            'created_at' => now(),
        ]);

        PageView::create([
            'path' => '/contact',
            'visitor_hash' => 'hash_user_2',
            'referrer_host' => 'linkedin.com',
            'device' => 'mobile',
            'browser' => 'Safari',
            'status_code' => 200,
            'created_at' => now()->subDays(2),
        ]);

        // Guest cannot access
        $this->get('/admin/analytics')->assertRedirect('/login');

        // Admin views 7d default dashboard
        $response = $this->actingAs($admin)->get('/admin/analytics');
        $response->assertOk()
            ->assertSee('Statistiques d\'Audience RGPD')
            ->assertSee('Pages Vues')
            ->assertSee('Visiteurs Uniques')
            ->assertSee('/a-propos')
            ->assertSee('/contact')
            ->assertSee('google.com')
            ->assertSee('linkedin.com')
            ->assertSee('desktop')
            ->assertSee('mobile');

        // Admin views 30d period
        $response30d = $this->actingAs($admin)->get('/admin/analytics?period=30d');
        $response30d->assertOk()
            ->assertSee('Période : 30d');
    }

    public function test_admin_can_clear_analytics_history(): void
    {
        $this->pluginManager->install('lcmscore.analytics');
        $this->pluginManager->activate('lcmscore.analytics');

        /** @var User $admin */
        $admin = User::factory()->create();

        PageView::create([
            'path' => '/test',
            'visitor_hash' => 'hash_123',
            'device' => 'desktop',
            'browser' => 'Chrome',
            'status_code' => 200,
            'created_at' => now(),
        ]);

        $this->assertSame(1, PageView::query()->count());

        $this->actingAs($admin)
            ->post('/admin/analytics/clear')
            ->assertRedirect('/admin/analytics')
            ->assertSessionHas('success');

        $this->assertSame(0, PageView::query()->count());
    }

    public function test_uninstall_with_purge_drops_tables_and_navigation(): void
    {
        $this->pluginManager->install('lcmscore.analytics');
        $this->pluginManager->activate('lcmscore.analytics');

        $this->assertTrue(Schema::hasTable('plugin_analytics_pageviews'));
        $this->assertTrue($this->navigationRegistry->has('analytics-management'));

        $this->pluginManager->deactivate('lcmscore.analytics');
        $this->pluginManager->uninstall('lcmscore.analytics', purgeData: true);

        $this->assertFalse(Schema::hasTable('plugin_analytics_pageviews'));
        $this->assertFalse($this->navigationRegistry->has('analytics-management'));
    }
}
