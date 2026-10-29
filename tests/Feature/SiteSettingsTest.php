<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Settings\Mail\SmtpTestMail;
use App\Core\Settings\SettingManager;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_guest_cannot_access_site_settings(): void
    {
        $response = $this->get('/admin/settings/site');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_site_settings(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/settings/site');
        $response->assertOk();
        $response->assertSee('Paramètres du site');
        $response->assertSee('Configuration SMTP');
        $response->assertSee('Diagnostic & Versions');
    }

    public function test_update_general_settings_persists_in_database(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/settings/site/general', [
            'site_name' => 'Mon Super CMS LCMS',
            'site_url' => 'https://example.com',
            'site_timezone' => 'Europe/Paris',
            'site_locale' => 'fr',
            'maintenance_mode' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $settingManager = app(SettingManager::class);
        $settingManager->refresh();

        $this->assertSame('Mon Super CMS LCMS', $settingManager->get('site_name'));
        $this->assertSame('https://example.com', $settingManager->get('site_url'));
        $this->assertSame('Europe/Paris', $settingManager->get('site_timezone'));
        $this->assertTrue((bool) $settingManager->get('maintenance_mode'));
    }

    public function test_update_smtp_settings_stores_encrypted_password(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/settings/site/smtp', [
            'mail_host' => 'smtp.mailtrap.io',
            'mail_port' => 2525,
            'mail_encryption' => 'tls',
            'mail_username' => 'smtp_user',
            'mail_password' => 'smtp_secret_password',
            'mail_from_address' => 'admin@example.com',
            'mail_from_name' => 'LCMS Notifications',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $settingManager = app(SettingManager::class);
        $settingManager->refresh();

        $this->assertSame('smtp.mailtrap.io', $settingManager->get('mail_host'));
        $this->assertSame(2525, $settingManager->get('mail_port'));
        $this->assertSame('smtp_secret_password', $settingManager->get('mail_password'));
    }

    public function test_test_smtp_sends_email(): void
    {
        Mail::fake();

        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/admin/settings/site/smtp/test', [
            'test_email' => 'test@example.com',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        Mail::assertSent(SmtpTestMail::class);
    }

    public function test_guest_can_access_site_when_maintenance_mode_is_disabled(): void
    {
        $settingManager = app(SettingManager::class);
        $settingManager->set('maintenance_mode', false, 'boolean', 'general');

        $response = $this->get('/');
        $response->assertOk();
    }

    public function test_public_visitor_sees_503_maintenance_page_when_maintenance_mode_is_enabled(): void
    {
        $settingManager = app(SettingManager::class);
        $settingManager->set('maintenance_mode', true, 'boolean', 'general');
        $settingManager->set('maintenance_message', 'Mise à niveau exceptionnelle en cours.', 'string', 'general');

        $response = $this->get('/');
        $response->assertStatus(503);
        $response->assertSee('Site temporairement indisponible');
        $response->assertSee('Mise à niveau exceptionnelle en cours.');
        $response->assertSee('Espace Administration');
    }

    public function test_login_page_remains_accessible_in_maintenance_mode(): void
    {
        $settingManager = app(SettingManager::class);
        $settingManager->set('maintenance_mode', true, 'boolean', 'general');

        $response = $this->get('/login');
        $response->assertOk();
    }

    public function test_authenticated_admin_can_bypass_maintenance_mode(): void
    {
        $settingManager = app(SettingManager::class);
        $settingManager->set('maintenance_mode', true, 'boolean', 'general');

        /** @var User $admin */
        $admin = User::factory()->create();
        $superAdminRole = \App\Core\RBAC\Models\Role::where('slug', 'super_admin')->first();
        if ($superAdminRole) {
            $admin->roles()->attach($superAdminRole->id);
        }

        $response = $this->actingAs($admin)->get('/');
        $response->assertOk();
    }
}
