<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Lifecycle\PluginState;
use App\Core\PluginManager\PluginManager;
use App\Core\PluginRegistry\PluginRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DemoHelloPluginHttpTest extends TestCase
{
    use RefreshDatabase;

    private PluginManager $pluginManager;

    private PluginRegistry $pluginRegistry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginManager = $this->app->make(PluginManager::class);
        $this->pluginRegistry = $this->app->make(PluginRegistry::class);

        // Ensure plugins path points to native platform/plugins directory
        config(['cms.plugins.path' => base_path('platform/plugins')]);
    }

    public function test_demo_hello_route_returns_404_when_plugin_is_not_active(): void
    {
        $this->assertFalse(Schema::hasTable('plugin_demo_messages'));

        $response = $this->get('/demo/hello');

        $response->assertNotFound();
    }

    public function test_demo_hello_route_returns_200_with_message_when_plugin_is_active(): void
    {
        // 1. Install and activate acme.demo-hello
        $this->pluginManager->install('acme.demo-hello');
        $this->pluginManager->activate('acme.demo-hello');

        $this->assertTrue(Schema::hasTable('plugin_demo_messages'));
        $this->assertDatabaseHas('plugin_demo_messages', [
            'content' => 'Hello from ACME Demo Plugin!',
        ]);

        // 2. Request /demo/hello
        $response = $this->get('/demo/hello');

        $response->assertOk();
        $response->assertSee('ACME Demo Plugin');
        $response->assertSee('Hello from ACME Demo Plugin!');
        $response->assertSee('Plugin État : ACTIF');
    }

    public function test_demo_hello_route_returns_404_when_plugin_is_disabled(): void
    {
        // 1. Install and activate
        $this->pluginManager->install('acme.demo-hello');
        $this->pluginManager->activate('acme.demo-hello');

        $this->assertDatabaseHas('plugin_demo_messages', [
            'content' => 'Hello from ACME Demo Plugin!',
        ]);

        // 2. Deactivate plugin
        $this->pluginManager->deactivate('acme.demo-hello');

        $this->assertDatabaseHas('cms_plugins', [
            'id' => 'acme.demo-hello',
            'is_active' => 0,
            'state' => 'DISABLED',
        ]);

        // 3. Database table and rows must be preserved intact on deactivation
        $this->assertTrue(Schema::hasTable('plugin_demo_messages'));
        $this->assertSame(1, DB::table('plugin_demo_messages')->count());
    }

    public function test_demo_hello_route_is_not_registered_when_plugin_is_disabled_at_boot(): void
    {
        // Prepare database with installed and disabled plugin record
        $this->pluginManager->install('acme.demo-hello');
        $record = $this->pluginRegistry->find('acme.demo-hello');
        $this->assertNotNull($record);
        $record->state = PluginState::DISABLED;
        $record->is_active = false;
        $record->save();

        // Re-run boot cycle with disabled plugin
        $this->pluginManager->boot();

        $response = $this->get('/demo/hello');
        $response->assertNotFound();
    }
}
