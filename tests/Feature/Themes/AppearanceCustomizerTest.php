<?php

declare(strict_types=1);

namespace Tests\Feature\Themes;

use App\Core\RBAC\Models\Permission;
use App\Core\RBAC\Models\Role;
use App\Core\Security\Audit\Models\AuditLog;
use App\Core\Settings\SettingManager;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppearanceCustomizerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_guest_cannot_access_appearance_customize(): void
    {
        $response = $this->get('/admin/appearance/customize');
        $response->assertRedirect('/login');

        $postResponse = $this->post('/admin/appearance/customize', [
            'custom_css' => 'body { color: red; }',
        ]);
        $postResponse->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_appearance_customize_page(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/appearance/customize');

        $response->assertOk();
        $response->assertViewIs('core::admin.appearance.customize');
        $response->assertSee("Personnaliser l'apparence publique");
        $response->assertSee('CSS Personnalisé');
        $response->assertSee('JavaScript Personnalisé');
        $response->assertSee('Balises &lt;head&gt; &amp; Métadonnées', false);
    }

    public function test_user_can_update_custom_css_js_and_head_tags(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $customCss = ":root { --custom-accent: #10b981; }\nbody { background: #f0fdf4; }";
        $customJs = "console.log('LCMS Customized');";
        $customHead = '<meta name="custom-test" content="lcms-appearance">';

        $response = $this->actingAs($user)->post('/admin/appearance/customize', [
            'custom_css' => $customCss,
            'custom_js' => $customJs,
            'custom_head_tags' => $customHead,
        ]);

        $response->assertRedirect('/admin/appearance/customize');
        $response->assertSessionHas('success');

        /** @var SettingManager $settings */
        $settings = app(SettingManager::class);
        $this->assertSame($customCss, $settings->get('appearance_custom_css'));
        $this->assertSame($customJs, $settings->get('appearance_custom_js'));
        $this->assertSame($customHead, $settings->get('appearance_custom_head_tags'));

        // Verify audit log
        $auditLog = AuditLog::where('action', 'appearance.customize.updated')->first();
        $this->assertNotNull($auditLog);
        $this->assertSame('Appearance', $auditLog->resource_type);
    }

    public function test_custom_appearance_is_injected_on_public_home_page(): void
    {
        /** @var SettingManager $settings */
        $settings = app(SettingManager::class);
        $settings->set('appearance_custom_css', '.theme-override { display: block; }');
        $settings->set('appearance_custom_js', 'window.themeInjected = true;');
        $settings->set('appearance_custom_head_tags', '<meta name="lcms-engine" content="active">');

        $response = $this->get('/');
        $response->assertOk();

        // Check CSS in head
        $response->assertSee('<style type="text/css" id="lcms-custom-css">', false);
        $response->assertSee('.theme-override { display: block; }', false);

        // Check head tags in head
        $response->assertSee('<meta name="lcms-engine" content="active">', false);

        // Check JS in footer
        $response->assertSee('<script id="lcms-custom-js">', false);
        $response->assertSee('window.themeInjected = true;', false);
    }

    public function test_appearance_and_theme_permissions_exist_in_database(): void
    {
        $this->assertNotNull(Permission::where('slug', 'themes.view')->first());
        $this->assertNotNull(Permission::where('slug', 'themes.manage')->first());
        $this->assertNotNull(Permission::where('slug', 'appearance.customize')->first());

        /** @var Role $adminRole */
        $adminRole = Role::where('slug', 'admin')->first();
        $this->assertNotNull($adminRole);
        $this->assertTrue($adminRole->permissions->contains('slug', 'themes.view'));
        $this->assertTrue($adminRole->permissions->contains('slug', 'themes.manage'));
        $this->assertTrue($adminRole->permissions->contains('slug', 'appearance.customize'));
    }
}
