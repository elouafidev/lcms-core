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
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Plugins\Newsletter\Mail\ConfirmationMail;
use Plugins\Newsletter\Models\Subscriber;
use Tests\TestCase;

class NewsletterPluginTest extends TestCase
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
        $discovered = $this->pluginRegistry->getDiscovered('lcmscore.newsletter');

        $this->assertNotNull($discovered);
        $this->assertSame(PluginState::AVAILABLE->value, $discovered->getState());
        $this->assertFalse(Schema::hasTable('plugin_newsletter_subscribers'));
        $this->assertFalse($this->navigationRegistry->has('newsletter-management'));

        // Public newsletter route should be 404 before activation
        $this->post('/newsletter/subscribe', ['email' => 'test@example.com'])->assertNotFound();
    }

    public function test_install_and_activation_creates_tables_and_navigation(): void
    {
        $this->pluginManager->install('lcmscore.newsletter');
        $this->pluginManager->activate('lcmscore.newsletter');

        $this->assertTrue(Schema::hasTable('plugin_newsletter_subscribers'));

        // Navigation check
        $this->assertTrue($this->navigationRegistry->has('newsletter-management'));
        $item = $this->navigationRegistry->get('newsletter-management');
        $this->assertNotNull($item);
        $this->assertSame('Newsletter & Abonnés', $item->getLabel());
        $this->assertSame('/admin/newsletter', $item->getUrl());

        // Permissions check
        $this->assertTrue($this->permissionRegistry->has('newsletter.manage'));
    }

    public function test_public_user_can_subscribe_with_double_opt_in_email(): void
    {
        Mail::fake();

        $this->pluginManager->install('lcmscore.newsletter');
        $this->pluginManager->activate('lcmscore.newsletter');

        $response = $this->post('/newsletter/subscribe', [
            'email' => 'Jean.Dupont@example.com',
            'source' => 'footer_widget',
        ]);

        $response->assertSessionHas('newsletter_success');

        $this->assertDatabaseHas('plugin_newsletter_subscribers', [
            'email' => 'jean.dupont@example.com',
            'status' => 'pending',
            'source' => 'footer_widget',
        ]);

        $subscriber = Subscriber::query()->where('email', 'jean.dupont@example.com')->first();
        $this->assertNotNull($subscriber);
        $this->assertNotEmpty($subscriber->token);
        $this->assertNull($subscriber->confirmed_at);

        Mail::assertSent(ConfirmationMail::class, function (ConfirmationMail $mail) use ($subscriber) {
            return $mail->hasTo('jean.dupont@example.com')
                && str_contains($mail->confirmationUrl, $subscriber->token);
        });
    }

    public function test_honeypot_silently_ignores_spam_bots(): void
    {
        Mail::fake();

        $this->pluginManager->install('lcmscore.newsletter');
        $this->pluginManager->activate('lcmscore.newsletter');

        $response = $this->post('/newsletter/subscribe', [
            'email' => 'spambot@example.com',
            '_hp_email' => 'http://spam-link-payload.com',
        ]);

        $response->assertSessionHas('newsletter_success');
        $this->assertDatabaseMissing('plugin_newsletter_subscribers', [
            'email' => 'spambot@example.com',
        ]);

        Mail::assertNothingSent();
    }

    public function test_user_can_confirm_subscription_via_token(): void
    {
        $this->pluginManager->install('lcmscore.newsletter');
        $this->pluginManager->activate('lcmscore.newsletter');

        /** @var Subscriber $subscriber */
        $subscriber = Subscriber::create([
            'email' => 'alice@example.com',
            'status' => 'pending',
            'token' => Subscriber::generateToken(),
            'source' => 'web',
        ]);

        $response = $this->get('/newsletter/confirm/'.$subscriber->token);
        $response->assertOk()
            ->assertSee('Inscription confirmée !');

        $subscriber->refresh();
        $this->assertTrue($subscriber->isConfirmed());
        $this->assertNotNull($subscriber->confirmed_at);
    }

    public function test_user_can_unsubscribe_in_one_click(): void
    {
        $this->pluginManager->install('lcmscore.newsletter');
        $this->pluginManager->activate('lcmscore.newsletter');

        /** @var Subscriber $subscriber */
        $subscriber = Subscriber::create([
            'email' => 'bob@example.com',
            'status' => 'confirmed',
            'token' => Subscriber::generateToken(),
            'source' => 'web',
            'confirmed_at' => now(),
        ]);

        $response = $this->get('/newsletter/unsubscribe/'.$subscriber->token);
        $response->assertOk()
            ->assertSee('Désabonnement pris en compte');

        $subscriber->refresh();
        $this->assertTrue($subscriber->isUnsubscribed());
        $this->assertNotNull($subscriber->unsubscribed_at);
    }

    public function test_re_subscription_of_unsubscribed_user_triggers_new_opt_in(): void
    {
        Mail::fake();

        $this->pluginManager->install('lcmscore.newsletter');
        $this->pluginManager->activate('lcmscore.newsletter');

        /** @var Subscriber $subscriber */
        $subscriber = Subscriber::create([
            'email' => 'charlie@example.com',
            'status' => 'unsubscribed',
            'token' => 'old_token_xyz',
            'source' => 'web',
            'unsubscribed_at' => now()->subDays(10),
        ]);

        $this->post('/newsletter/subscribe', ['email' => 'charlie@example.com']);

        $subscriber->refresh();
        $this->assertSame('pending', $subscriber->status);
        $this->assertNotSame('old_token_xyz', $subscriber->token);
        $this->assertNull($subscriber->unsubscribed_at);

        Mail::assertSent(ConfirmationMail::class);
    }

    public function test_admin_can_view_subscribers_dashboard_and_stats(): void
    {
        $this->pluginManager->install('lcmscore.newsletter');
        $this->pluginManager->activate('lcmscore.newsletter');

        /** @var User $admin */
        $admin = User::factory()->create();

        Subscriber::create([
            'email' => 'confirmed1@example.com',
            'status' => 'confirmed',
            'token' => Subscriber::generateToken(),
            'confirmed_at' => now(),
        ]);

        Subscriber::create([
            'email' => 'pending1@example.com',
            'status' => 'pending',
            'token' => Subscriber::generateToken(),
        ]);

        // Guest is redirected to login
        $this->get('/admin/newsletter')->assertRedirect('/login');

        // Admin can view dashboard
        $response = $this->actingAs($admin)->get('/admin/newsletter');
        $response->assertOk()
            ->assertSee('Newsletter &amp; Abonnés', false)
            ->assertSee('confirmed1@example.com')
            ->assertSee('pending1@example.com')
            ->assertSee('Total Inscrits');
    }

    public function test_admin_can_add_toggle_and_delete_subscribers(): void
    {
        $this->pluginManager->install('lcmscore.newsletter');
        $this->pluginManager->activate('lcmscore.newsletter');

        /** @var User $admin */
        $admin = User::factory()->create();

        // 1. Admin adds confirmed subscriber
        $this->actingAs($admin)
            ->post('/admin/newsletter/subscribers', [
                'email' => 'new.member@company.com',
                'status' => 'confirmed',
            ])
            ->assertRedirect('/admin/newsletter')
            ->assertSessionHas('success');

        /** @var Subscriber $subscriber */
        $subscriber = Subscriber::query()->where('email', 'new.member@company.com')->first();
        $this->assertNotNull($subscriber);
        $this->assertTrue($subscriber->isConfirmed());

        // 2. Admin toggles status to unsubscribed
        $this->actingAs($admin)
            ->post('/admin/newsletter/subscribers/'.$subscriber->id.'/toggle')
            ->assertRedirect('/admin/newsletter');

        $subscriber->refresh();
        $this->assertTrue($subscriber->isUnsubscribed());

        // 3. Admin deletes subscriber
        $this->actingAs($admin)
            ->delete('/admin/newsletter/subscribers/'.$subscriber->id)
            ->assertRedirect('/admin/newsletter');

        $this->assertDatabaseMissing('plugin_newsletter_subscribers', [
            'id' => $subscriber->id,
        ]);
    }

    public function test_admin_can_export_subscribers_as_csv(): void
    {
        $this->pluginManager->install('lcmscore.newsletter');
        $this->pluginManager->activate('lcmscore.newsletter');

        /** @var User $admin */
        $admin = User::factory()->create();

        Subscriber::create([
            'email' => 'export1@test.com',
            'status' => 'confirmed',
            'token' => Subscriber::generateToken(),
            'source' => 'landing_page',
        ]);

        $response = $this->actingAs($admin)->get('/admin/newsletter/export');
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        // Capture streamed response content
        $content = $response->streamedContent();
        $this->assertStringContainsString('E-mail', $content);
        $this->assertStringContainsString('export1@test.com', $content);
        $this->assertStringContainsString('landing_page', $content);
    }

    public function test_blade_directive_renders_newsletter_form(): void
    {
        $this->pluginManager->install('lcmscore.newsletter');
        $this->pluginManager->activate('lcmscore.newsletter');

        $rendered = Blade::render('@newsletterForm');

        $this->assertStringContainsString('/newsletter/subscribe', $rendered);
        $this->assertStringContainsString('name="_hp_email"', $rendered);
        $this->assertStringContainsString('type="email"', $rendered);
    }

    public function test_uninstall_with_purge_drops_tables_and_navigation(): void
    {
        $this->pluginManager->install('lcmscore.newsletter');
        $this->pluginManager->activate('lcmscore.newsletter');

        $this->assertTrue(Schema::hasTable('plugin_newsletter_subscribers'));
        $this->assertTrue($this->navigationRegistry->has('newsletter-management'));

        $this->pluginManager->deactivate('lcmscore.newsletter');
        $this->pluginManager->uninstall('lcmscore.newsletter', purgeData: true);

        $this->assertFalse(Schema::hasTable('plugin_newsletter_subscribers'));
        $this->assertFalse($this->navigationRegistry->has('newsletter-management'));
    }
}
