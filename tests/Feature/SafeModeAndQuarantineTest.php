<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Lifecycle\PluginState;
use App\Core\PluginManager\PluginManager;
use App\Core\PluginRegistry\Models\PluginRecord;
use App\Core\PluginRegistry\PluginRegistry;
use App\Core\Recovery\Models\PluginErrorRecord;
use App\Core\Recovery\PluginQuarantineHandler;
use App\Core\Recovery\SafeModeManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use Tests\TestCase;

class CrashingBootServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        throw new RuntimeException('Catastrophic failure in plugin boot phase');
    }
}

class CrashingRegisterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        throw new RuntimeException('Fatal error during plugin provider registration');
    }
}

class SafeModeAndQuarantineTest extends TestCase
{
    use RefreshDatabase;

    private PluginManager $pluginManager;

    private PluginRegistry $pluginRegistry;

    private SafeModeManager $safeModeManager;

    private PluginQuarantineHandler $quarantineHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginManager = $this->app->make(PluginManager::class);
        $this->pluginRegistry = $this->app->make(PluginRegistry::class);
        $this->safeModeManager = $this->app->make(SafeModeManager::class);
        $this->quarantineHandler = $this->app->make(PluginQuarantineHandler::class);

        $this->safeModeManager->disable();
    }

    protected function tearDown(): void
    {
        $this->safeModeManager->disable();

        parent::tearDown();
    }

    public function test_safe_mode_manager_enables_and_disables_sentinel_file(): void
    {
        $this->assertFalse($this->safeModeManager->isEnabled());

        $this->safeModeManager->enable();
        $this->assertTrue($this->safeModeManager->isEnabled());
        $this->assertFileExists($this->safeModeManager->getSentinelPath());

        $this->safeModeManager->disable();
        $this->assertFalse($this->safeModeManager->isEnabled());
        $this->assertFileDoesNotExist($this->safeModeManager->getSentinelPath());
    }

    public function test_safe_mode_bypasses_all_plugins_at_boot(): void
    {
        // 1. Create and activate a dummy plugin in database
        $record = PluginRecord::create([
            'id' => 'lcmscore.failing-plugin',
            'name' => 'Failing Plugin',
            'version' => '1.0.0',
            'state' => PluginState::ACTIVE,
            'is_active' => true,
            'manifest' => [
                'id' => 'lcmscore.failing-plugin',
                'name' => 'Failing Plugin',
                'version' => '1.0.0',
                'providers' => [CrashingBootServiceProvider::class],
            ],
        ]);

        // 2. Enable safe mode
        $this->safeModeManager->enable();
        $this->assertTrue($this->pluginManager->isSafeMode());

        // 3. Boot should completely skip third-party plugins without throwing or registering them
        $this->pluginManager->boot();

        $fresh = $record->fresh();
        $this->assertNotNull($fresh);
        // State remains untouched because boot was safely bypassed
        $this->assertSame(PluginState::ACTIVE, $fresh->state);
    }

    public function test_failing_plugin_boot_is_quarantined_and_error_is_recorded(): void
    {
        $this->assertTrue(Schema::hasTable('cms_plugin_errors'));

        $record = PluginRecord::create([
            'id' => 'lcmscore.crash-boot',
            'name' => 'Crash Boot Plugin',
            'version' => '1.0.0',
            'state' => PluginState::ACTIVE,
            'is_active' => true,
            'manifest' => [
                'id' => 'lcmscore.crash-boot',
                'name' => 'Crash Boot Plugin',
                'version' => '1.0.0',
                'providers' => [CrashingBootServiceProvider::class],
            ],
        ]);

        // Boot the plugin: exception must be trapped, not bubbled up
        $this->pluginManager->bootPlugin($record);

        $fresh = $record->fresh();
        $this->assertNotNull($fresh);
        $this->assertSame(PluginState::ERROR, $fresh->state);
        $this->assertFalse($fresh->is_active);
        $this->assertNotNull($fresh->error_message);
        $this->assertStringContainsString('Catastrophic failure in plugin boot phase', $fresh->error_message);

        // Verify audit log in cms_plugin_errors table
        $this->assertDatabaseHas('cms_plugin_errors', [
            'plugin_id' => 'lcmscore.crash-boot',
            'provider' => CrashingBootServiceProvider::class,
            'error_message' => 'Catastrophic failure in plugin boot phase',
        ]);

        $errors = $this->quarantineHandler->getErrorsForPlugin('lcmscore.crash-boot');
        $this->assertCount(1, $errors);
        /** @var PluginErrorRecord $error */
        $error = $errors->first();
        $this->assertNotNull($error->stack_trace);
        $this->assertIsArray($error->context);
        $this->assertSame(RuntimeException::class, $error->context['exception_class']);
    }

    public function test_failing_plugin_registration_is_quarantined(): void
    {
        $record = PluginRecord::create([
            'id' => 'lcmscore.crash-register',
            'name' => 'Crash Register Plugin',
            'version' => '1.0.0',
            'state' => PluginState::ACTIVE,
            'is_active' => true,
            'manifest' => [
                'id' => 'lcmscore.crash-register',
                'name' => 'Crash Register Plugin',
                'version' => '1.0.0',
                'providers' => [CrashingRegisterServiceProvider::class],
            ],
        ]);

        $this->pluginManager->bootPlugin($record);

        $fresh = $record->fresh();
        $this->assertNotNull($fresh);
        $this->assertSame(PluginState::ERROR, $fresh->state);
        $this->assertFalse($fresh->is_active);
        $this->assertStringContainsString('Fatal error during plugin provider registration', (string) $fresh->error_message);

        $this->assertDatabaseHas('cms_plugin_errors', [
            'plugin_id' => 'lcmscore.crash-register',
            'provider' => CrashingRegisterServiceProvider::class,
        ]);
    }

    public function test_admin_dashboard_remains_accessible_with_quarantine_warning_when_plugin_errors_exist(): void
    {
        // 1. Create quarantined plugin
        PluginRecord::create([
            'id' => 'lcmscore.broken',
            'name' => 'Broken Extension',
            'version' => '2.1.0',
            'state' => PluginState::ERROR,
            'is_active' => false,
            'error_message' => 'Quarantined due to [RuntimeException]: Something broke',
            'manifest' => [
                'id' => 'lcmscore.broken',
                'name' => 'Broken Extension',
                'version' => '2.1.0',
            ],
        ]);

        PluginErrorRecord::create([
            'plugin_id' => 'lcmscore.broken',
            'provider' => 'Lcmscore\\Broken\\BrokenServiceProvider',
            'error_message' => 'Something broke',
            'stack_trace' => 'dummy stack trace',
            'context' => ['code' => 500],
        ]);

        /** @var User $admin */
        $admin = User::factory()->create();

        // 2. HTTP request to /admin/plugins must return HTTP 200 without crashing
        $response = $this->actingAs($admin)->get('/admin/plugins');

        $response->assertOk();
        $response->assertSee('Broken Extension');
        $response->assertSee('ERROR');
        $response->assertSee('Attention critique :', false);
        $response->assertSee('Des extensions défaillantes ont été isolées en quarantaine', false);
        $response->assertSee('Quarantined due to [RuntimeException]: Something broke');
    }

    public function test_quarantine_handler_clear_and_get_all_errors(): void
    {
        PluginErrorRecord::create([
            'plugin_id' => 'lcmscore.one',
            'error_message' => 'Error 1',
        ]);

        PluginErrorRecord::create([
            'plugin_id' => 'lcmscore.two',
            'error_message' => 'Error 2',
        ]);

        $this->assertCount(2, $this->quarantineHandler->getAllErrors());

        $this->quarantineHandler->clearErrorsForPlugin('lcmscore.one');

        $this->assertCount(1, $this->quarantineHandler->getAllErrors());
        $this->assertCount(0, $this->quarantineHandler->getErrorsForPlugin('lcmscore.one'));
        $this->assertCount(1, $this->quarantineHandler->getErrorsForPlugin('lcmscore.two'));
    }
}
