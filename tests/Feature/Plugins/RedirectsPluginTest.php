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
use Plugins\Redirects\Models\NotFoundLog;
use Plugins\Redirects\Models\RedirectRule;
use Tests\TestCase;

class RedirectsPluginTest extends TestCase
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
        $discovered = $this->pluginRegistry->getDiscovered('lcmscore.redirects');

        $this->assertNotNull($discovered);
        $this->assertSame(PluginState::AVAILABLE->value, $discovered->getState());
        $this->assertFalse(Schema::hasTable('plugin_redirects'));
        $this->assertFalse(Schema::hasTable('plugin_redirects_404_logs'));
        $this->assertFalse($this->navigationRegistry->has('redirects-management'));

        // Admin route should be 404 before activation
        $this->get('/admin/redirects')->assertNotFound();
    }

    public function test_install_and_activation_creates_tables_and_navigation(): void
    {
        $this->pluginManager->install('lcmscore.redirects');
        $this->pluginManager->activate('lcmscore.redirects');

        $this->assertTrue(Schema::hasTable('plugin_redirects'));
        $this->assertTrue(Schema::hasTable('plugin_redirects_404_logs'));

        // Navigation check
        $this->assertTrue($this->navigationRegistry->has('redirects-management'));
        $item = $this->navigationRegistry->get('redirects-management');
        $this->assertNotNull($item);
        $this->assertSame('Redirections & 404', $item->getLabel());
        $this->assertSame('/admin/redirects', $item->getUrl());

        // Permissions check
        $this->assertTrue($this->permissionRegistry->has('redirects.manage'));
    }

    public function test_active_redirect_rule_intercepts_and_redirects_request(): void
    {
        $this->pluginManager->install('lcmscore.redirects');
        $this->pluginManager->activate('lcmscore.redirects');

        /** @var RedirectRule $rule */
        $rule = RedirectRule::create([
            'source_path' => '/ancien-chemin-seo',
            'target_url' => '/nouveau-chemin-propre',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $response = $this->get('/ancien-chemin-seo');
        $response->assertStatus(301);
        $response->assertRedirect('/nouveau-chemin-propre');

        $rule->refresh();
        $this->assertSame(1, $rule->hit_count);
        $this->assertNotNull($rule->last_used_at);

        // Second hit increments counter
        $this->get('/ancien-chemin-seo');
        $rule->refresh();
        $this->assertSame(2, $rule->hit_count);
    }

    public function test_inactive_redirect_rule_does_not_redirect(): void
    {
        $this->pluginManager->install('lcmscore.redirects');
        $this->pluginManager->activate('lcmscore.redirects');

        /** @var RedirectRule $rule */
        $rule = RedirectRule::create([
            'source_path' => '/lien-inactif',
            'target_url' => '/cible',
            'status_code' => 301,
            'is_active' => false,
        ]);

        $response = $this->get('/lien-inactif');
        $response->assertNotFound();

        $rule->refresh();
        $this->assertSame(0, $rule->hit_count);
    }

    public function test_temporary_302_redirect_status_code(): void
    {
        $this->pluginManager->install('lcmscore.redirects');
        $this->pluginManager->activate('lcmscore.redirects');

        RedirectRule::create([
            'source_path' => '/promo-noel',
            'target_url' => '/offres-speciales',
            'status_code' => 302,
            'is_active' => true,
        ]);

        $this->get('/promo-noel')
            ->assertStatus(302)
            ->assertRedirect('/offres-speciales');
    }

    public function test_external_url_redirect(): void
    {
        $this->pluginManager->install('lcmscore.redirects');
        $this->pluginManager->activate('lcmscore.redirects');

        RedirectRule::create([
            'source_path' => '/external-docs',
            'target_url' => 'https://docs.lcmscore.local/v1',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->get('/external-docs')
            ->assertStatus(301)
            ->assertRedirect('https://docs.lcmscore.local/v1');
    }

    public function test_404_error_is_logged_automatically(): void
    {
        $this->pluginManager->install('lcmscore.redirects');
        $this->pluginManager->activate('lcmscore.redirects');

        $this->get('/lien-completement-inconnu-xyz')
            ->assertNotFound();

        $this->assertDatabaseHas('plugin_redirects_404_logs', [
            'path' => '/lien-completement-inconnu-xyz',
            'hits' => 1,
        ]);

        // Second 404 on same URL increments hits
        $this->get('/lien-completement-inconnu-xyz')
            ->assertNotFound();

        $log = NotFoundLog::query()->where('path', '/lien-completement-inconnu-xyz')->first();
        $this->assertNotNull($log);
        $this->assertSame(2, $log->hits);
    }

    public function test_admin_can_view_redirects_dashboard_and_tabs(): void
    {
        $this->pluginManager->install('lcmscore.redirects');
        $this->pluginManager->activate('lcmscore.redirects');

        /** @var User $admin */
        $admin = User::factory()->create();

        RedirectRule::create([
            'source_path' => '/v1/api',
            'target_url' => '/v2/api',
            'status_code' => 301,
            'is_active' => true,
        ]);

        NotFoundLog::record('/ancien-lien-brise');

        // Guest cannot access
        $this->get('/admin/redirects')->assertRedirect('/login');

        // Admin views redirects tab
        $response = $this->actingAs($admin)->get('/admin/redirects');
        $response->assertOk()
            ->assertSee('Redirections &amp; Détecteur 404', false)
            ->assertSee('/v1/api')
            ->assertSee('/v2/api')
            ->assertSee('Total Redirections');

        // Admin views 404 logs tab
        $response404 = $this->actingAs($admin)->get('/admin/redirects?tab=logs404');
        $response404->assertOk()
            ->assertSee('Journal des Erreurs 404')
            ->assertSee('/ancien-lien-brise');
    }

    public function test_admin_can_create_edit_toggle_and_delete_redirect(): void
    {
        $this->pluginManager->install('lcmscore.redirects');
        $this->pluginManager->activate('lcmscore.redirects');

        /** @var User $admin */
        $admin = User::factory()->create();

        // 1. Create redirect
        $this->actingAs($admin)
            ->post('/admin/redirects', [
                'source_path' => 'old-service',
                'target_url' => '/new-service',
                'status_code' => 301,
                'description' => 'Test redirection',
                'is_active' => '1',
            ])
            ->assertRedirect('/admin/redirects')
            ->assertSessionHas('success');

        /** @var RedirectRule $rule */
        $rule = RedirectRule::query()->where('source_path', '/old-service')->first();
        $this->assertNotNull($rule);
        $this->assertSame('/new-service', $rule->target_url);

        // 2. Edit redirect
        $this->actingAs($admin)
            ->put('/admin/redirects/'.$rule->id, [
                'source_path' => 'old-service-updated',
                'target_url' => '/new-service-updated',
                'status_code' => 302,
                'is_active' => '1',
            ])
            ->assertRedirect('/admin/redirects');

        $rule->refresh();
        $this->assertSame('/old-service-updated', $rule->source_path);
        $this->assertSame(302, $rule->status_code);

        // 3. Toggle status
        $this->actingAs($admin)
            ->post('/admin/redirects/'.$rule->id.'/toggle')
            ->assertRedirect('/admin/redirects');

        $rule->refresh();
        $this->assertFalse($rule->is_active);

        // 4. Delete rule
        $this->actingAs($admin)
            ->delete('/admin/redirects/'.$rule->id)
            ->assertRedirect('/admin/redirects');

        $this->assertDatabaseMissing('plugin_redirects', [
            'id' => $rule->id,
        ]);
    }

    public function test_admin_cannot_create_redirect_loop(): void
    {
        $this->pluginManager->install('lcmscore.redirects');
        $this->pluginManager->activate('lcmscore.redirects');

        /** @var User $admin */
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post('/admin/redirects', [
                'source_path' => '/boucle-infinie',
                'target_url' => '/boucle-infinie',
                'status_code' => 301,
            ])
            ->assertSessionHasErrors(['target_url']);

        $this->assertDatabaseMissing('plugin_redirects', [
            'source_path' => '/boucle-infinie',
        ]);
    }

    public function test_admin_can_convert_404_to_redirect_rule_in_one_click(): void
    {
        $this->pluginManager->install('lcmscore.redirects');
        $this->pluginManager->activate('lcmscore.redirects');

        /** @var User $admin */
        $admin = User::factory()->create();

        $log = NotFoundLog::record('/vieux-produit-supprime');

        $this->actingAs($admin)
            ->post('/admin/redirects/404/'.$log->id.'/convert', [
                'target_url' => '/nouveau-produit-phare',
            ])
            ->assertRedirect('/admin/redirects?tab=logs404')
            ->assertSessionHas('success');

        // 404 entry was cleaned
        $this->assertDatabaseMissing('plugin_redirects_404_logs', [
            'id' => $log->id,
        ]);

        // Redirect rule was created
        $this->assertDatabaseHas('plugin_redirects', [
            'source_path' => '/vieux-produit-supprime',
            'target_url' => '/nouveau-produit-phare',
            'status_code' => 301,
        ]);
    }

    public function test_admin_can_clear_all_404_logs(): void
    {
        $this->pluginManager->install('lcmscore.redirects');
        $this->pluginManager->activate('lcmscore.redirects');

        /** @var User $admin */
        $admin = User::factory()->create();

        NotFoundLog::record('/erreur-1');
        NotFoundLog::record('/erreur-2');

        $this->assertSame(2, NotFoundLog::query()->count());

        $this->actingAs($admin)
            ->post('/admin/redirects/404/clear')
            ->assertRedirect('/admin/redirects?tab=logs404');

        $this->assertSame(0, NotFoundLog::query()->count());
    }

    public function test_uninstall_with_purge_drops_tables_and_navigation(): void
    {
        $this->pluginManager->install('lcmscore.redirects');
        $this->pluginManager->activate('lcmscore.redirects');

        $this->assertTrue(Schema::hasTable('plugin_redirects'));
        $this->assertTrue(Schema::hasTable('plugin_redirects_404_logs'));
        $this->assertTrue($this->navigationRegistry->has('redirects-management'));

        $this->pluginManager->deactivate('lcmscore.redirects');
        $this->pluginManager->uninstall('lcmscore.redirects', purgeData: true);

        $this->assertFalse(Schema::hasTable('plugin_redirects'));
        $this->assertFalse(Schema::hasTable('plugin_redirects_404_logs'));
        $this->assertFalse($this->navigationRegistry->has('redirects-management'));
    }
}
