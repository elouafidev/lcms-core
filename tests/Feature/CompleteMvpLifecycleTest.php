<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Lifecycle\PluginState;
use App\Core\PluginManager\PluginManager;
use App\Core\PluginRegistry\PluginRegistry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CompleteMvpLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private PluginManager $pluginManager;

    private PluginRegistry $pluginRegistry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginManager = $this->app->make(PluginManager::class);
        $this->pluginRegistry = $this->app->make(PluginRegistry::class);

        config(['cms.plugins.path' => base_path('platform/plugins')]);
    }

    protected function tearDown(): void
    {
        $sentinel = storage_path('framework/cms_safe_mode');
        if (file_exists($sentinel)) {
            unlink($sentinel);
        }

        parent::tearDown();
    }

    /**
     * Complete End-to-End MVP lifecycle verification covering all Phase 1 acceptance criteria.
     */
    public function test_complete_end_to_end_mvp_lifecycle(): void
    {
        $pluginId = 'lcmscore.demo-hello';

        // 1. Initial State: plugin discovered on disk in AVAILABLE state
        $discovered = $this->pluginRegistry->getDiscovered($pluginId);
        $this->assertNotNull($discovered, "Plugin '{$pluginId}' must be discoverable on disk.");
        $this->assertSame(PluginState::AVAILABLE->value, $discovered->getState());
        $this->assertFalse($discovered->isActive());
        $this->assertFalse(Schema::hasTable('plugin_demo_messages'));

        // Route /demo/hello should not be accessible yet
        $this->get('/demo/hello')->assertNotFound();

        // 2. Installation: installs plugin and runs isolated migrations
        $installedRecord = $this->pluginManager->install($pluginId);
        $this->assertSame(PluginState::INSTALLED, $installedRecord->state);
        $this->assertFalse($installedRecord->is_active);
        $this->assertTrue(Schema::hasTable('plugin_demo_messages'));
        $this->assertDatabaseHas('cms_plugin_migrations', [
            'plugin_id' => $pluginId,
            'migration' => '2026_10_13_000010_create_plugin_demo_messages_table.php',
        ]);
        $this->assertDatabaseHas('plugin_demo_messages', [
            'content' => 'Hello from LCMS Core Demo Plugin!',
        ]);

        // 3. Activation: boots service providers and mounts routes
        $activeRecord = $this->pluginManager->activate($pluginId);
        $this->assertSame(PluginState::ACTIVE, $activeRecord->state);
        $this->assertTrue($activeRecord->is_active);

        // Verification of HTTP route execution
        $httpResponse = $this->get('/demo/hello');
        $httpResponse->assertOk();
        $httpResponse->assertSee('LCMS Core Demo Plugin');
        $httpResponse->assertSee('Hello from LCMS Core Demo Plugin!');
        $httpResponse->assertSee('Plugin État : ACTIF');

        // 4. Admin Panel GUI Check: /admin/plugins reflects ACTIVE status
        /** @var User $adminUser */
        $adminUser = User::factory()->create();
        $adminResponse = $this->actingAs($adminUser)->get('/admin/plugins');
        $adminResponse->assertOk();
        $adminResponse->assertSee($pluginId);
        $adminResponse->assertSee('Demo Hello');
        $adminResponse->assertSee('ACTIVE');

        // 5. Artisan CLI Check: cms:plugin:list output
        $this->artisan('cms:plugin:list')
            ->expectsOutputToContain('lcmscore.demo-hello')
            ->assertSuccessful();

        // 6. Deactivation: preserves all database tables without data loss
        $disabledRecord = $this->pluginManager->deactivate($pluginId);
        $this->assertSame(PluginState::DISABLED, $disabledRecord->state);
        $this->assertFalse($disabledRecord->is_active);

        // Tables remain completely intact
        $this->assertTrue(Schema::hasTable('plugin_demo_messages'));
        $this->assertSame(1, DB::table('plugin_demo_messages')->count());

        // 7. Emergency Safe Mode Verification
        $this->artisan('cms:recovery:safe-mode', ['--on' => true])
            ->assertSuccessful();

        $this->assertTrue($this->pluginManager->isSafeMode());

        $this->artisan('cms:recovery:safe-mode', ['--off' => true])
            ->assertSuccessful();

        $this->assertFalse($this->pluginManager->isSafeMode());

        // 8. Full Uninstallation with data purge
        $this->pluginManager->uninstall($pluginId, purgeData: true);

        $this->assertDatabaseMissing('cms_plugins', ['id' => $pluginId]);
        $this->assertFalse(Schema::hasTable('plugin_demo_messages'));
        $this->assertDatabaseMissing('cms_plugin_migrations', ['plugin_id' => $pluginId]);
    }
}
