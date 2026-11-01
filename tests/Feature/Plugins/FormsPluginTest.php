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
use Plugins\Forms\Models\Form;
use Plugins\Forms\Models\FormSubmission;
use Tests\TestCase;

class FormsPluginTest extends TestCase
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

        // Ensure plugins path points to native Plugins directory
        config(['cms.plugins.path' => base_path('Plugins')]);
    }

    public function test_plugin_is_discoverable_and_inactive_initially(): void
    {
        $discovered = $this->pluginRegistry->getDiscovered('lcmscore.forms');

        $this->assertNotNull($discovered);
        $this->assertSame(PluginState::AVAILABLE->value, $discovered->getState());
        $this->assertFalse(Schema::hasTable('plugin_forms'));
        $this->assertFalse(Schema::hasTable('plugin_form_submissions'));

        // Public submission route should be unavailable (404)
        $this->post('/forms/contact/submit', ['nom' => 'Test'])->assertNotFound();
    }

    public function test_install_and_activation_creates_tables_and_navigation(): void
    {
        $this->pluginManager->install('lcmscore.forms');
        $this->pluginManager->activate('lcmscore.forms');

        $this->assertTrue(Schema::hasTable('plugin_forms'));
        $this->assertTrue(Schema::hasTable('plugin_form_submissions'));

        // Navigation check
        $this->assertTrue($this->navigationRegistry->has('forms-management'));
        $item = $this->navigationRegistry->get('forms-management');
        $this->assertNotNull($item);
        $this->assertSame('Formulaires', $item->getLabel());
        $this->assertSame('/admin/forms', $item->getUrl());

        // Permissions check
        $this->assertTrue($this->permissionRegistry->has('forms.manage'));
        $this->assertTrue($this->permissionRegistry->has('forms.submissions.view'));
        $this->assertTrue($this->permissionRegistry->has('forms.submissions.delete'));
    }

    public function test_admin_can_create_and_manage_forms(): void
    {
        $this->pluginManager->install('lcmscore.forms');
        $this->pluginManager->activate('lcmscore.forms');

        /** @var User $user */
        $user = User::factory()->create();

        // Check create form view
        $viewResponse = $this->actingAs($user)->get('/admin/forms/create');
        $viewResponse->assertOk();
        $viewResponse->assertSee('Créer un formulaire');

        // Create new form via POST
        $response = $this->actingAs($user)->postJson('/admin/forms', [
            'name' => 'Formulaire Devis',
            'slug' => 'demande-devis',
            'description' => 'Demandes de devis en ligne',
            'submit_label' => 'Demander un devis',
            'recipient_email' => 'commercial@example.com',
            'send_notification' => true,
            'is_active' => true,
            'honeypot_enabled' => true,
            'fields' => [
                [
                    'name' => 'nom',
                    'label' => 'Nom du client',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => 'Votre nom',
                ],
                [
                    'name' => 'email',
                    'label' => 'Adresse email',
                    'type' => 'email',
                    'required' => true,
                ],
                [
                    'name' => 'budget',
                    'label' => 'Budget estimé',
                    'type' => 'number',
                    'required' => false,
                ],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.name', 'Formulaire Devis');
        $response->assertJsonPath('data.slug', 'demande-devis');

        $this->assertDatabaseHas('plugin_forms', [
            'slug' => 'demande-devis',
            'recipient_email' => 'commercial@example.com',
        ]);

        /** @var Form $form */
        $form = Form::query()->where('slug', 'demande-devis')->firstOrFail();

        // Update form
        $updateResponse = $this->actingAs($user)->putJson('/admin/forms/'.$form->id, [
            'name' => 'Formulaire Devis Entreprise',
            'slug' => 'demande-devis',
            'recipient_email' => 'pro@example.com',
            'fields' => [
                [
                    'name' => 'nom',
                    'label' => 'Société ou Nom',
                    'type' => 'text',
                    'required' => true,
                ],
            ],
        ]);

        $updateResponse->assertOk();
        $updateResponse->assertJsonPath('data.name', 'Formulaire Devis Entreprise');
        $updateResponse->assertJsonPath('data.recipient_email', 'pro@example.com');
    }

    public function test_public_user_can_submit_form_with_validation(): void
    {
        $this->pluginManager->install('lcmscore.forms');
        $this->pluginManager->activate('lcmscore.forms');

        /** @var Form $form */
        $form = Form::query()->create([
            'name' => 'Contact Rapide',
            'slug' => 'contact-rapide',
            'is_active' => true,
            'honeypot_enabled' => true,
            'submit_label' => 'Envoyer',
            'success_message' => 'Message bien transmis.',
            'fields' => [
                [
                    'name' => 'nom',
                    'label' => 'Nom',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'email',
                    'label' => 'Email',
                    'type' => 'email',
                    'required' => true,
                ],
                [
                    'name' => 'message',
                    'label' => 'Message',
                    'type' => 'textarea',
                    'required' => false,
                ],
            ],
        ]);

        // Submit invalid data (missing required name)
        $invalidResponse = $this->postJson('/forms/contact-rapide/submit', [
            'email' => 'not-an-email',
        ]);
        $invalidResponse->assertStatus(422);

        // Submit valid data
        $validResponse = $this->postJson('/forms/contact-rapide/submit', [
            'nom' => 'Alice Dupont',
            'email' => 'alice@example.com',
            'message' => 'Bonjour, ceci est un test.',
        ]);

        $validResponse->assertCreated();
        $validResponse->assertJsonPath('success', true);
        $validResponse->assertJsonPath('message', 'Message bien transmis.');

        $this->assertDatabaseHas('plugin_form_submissions', [
            'form_id' => $form->id,
            'is_read' => false,
        ]);

        /** @var FormSubmission $submission */
        $submission = FormSubmission::query()->where('form_id', $form->id)->firstOrFail();
        $this->assertSame('Alice Dupont', $submission->data['nom']);
        $this->assertSame('alice@example.com', $submission->data['email']);
    }

    public function test_honeypot_blocks_bot_submissions(): void
    {
        $this->pluginManager->install('lcmscore.forms');
        $this->pluginManager->activate('lcmscore.forms');

        /** @var Form $form */
        $form = Form::query()->create([
            'name' => 'Contact',
            'slug' => 'contact',
            'is_active' => true,
            'honeypot_enabled' => true,
            'fields' => [
                [
                    'name' => 'email',
                    'label' => 'Email',
                    'type' => 'email',
                    'required' => true,
                ],
            ],
        ]);

        // Bot fills the hidden honeypot field
        $botResponse = $this->postJson('/forms/contact/submit', [
            'email' => 'spammer@botnet.org',
            '_hp_website' => 'http://spam-site.xyz',
        ]);

        $botResponse->assertOk();
        // Database must remain clean: zero submissions created
        $this->assertDatabaseCount('plugin_form_submissions', 0);
    }

    public function test_admin_can_view_submissions_mark_as_read_and_export_csv(): void
    {
        $this->pluginManager->install('lcmscore.forms');
        $this->pluginManager->activate('lcmscore.forms');

        /** @var User $user */
        $user = User::factory()->create();

        /** @var Form $form */
        $form = Form::query()->create([
            'name' => 'Recrutement',
            'slug' => 'recrutement',
            'is_active' => true,
            'fields' => [
                ['name' => 'nom', 'label' => 'Nom', 'type' => 'text'],
                ['name' => 'poste', 'label' => 'Poste', 'type' => 'text'],
            ],
        ]);

        /** @var FormSubmission $submission */
        $submission = FormSubmission::query()->create([
            'form_id' => $form->id,
            'data' => ['nom' => 'Bob Martin', 'poste' => 'Développeur PHP'],
            'ip_address' => '127.0.0.1',
            'is_read' => false,
        ]);

        // Inspect submission details (marks as read automatically)
        $detailResponse = $this->actingAs($user)->getJson('/admin/forms/submissions/'.$submission->id);
        $detailResponse->assertOk();
        $detailResponse->assertJsonPath('success', true);
        $detailResponse->assertJsonPath('data.data.nom', 'Bob Martin');

        $submission->refresh();
        $this->assertTrue($submission->is_read);

        // Test CSV export
        $csvResponse = $this->actingAs($user)->get('/admin/forms/'.$form->id.'/submissions/export');
        $csvResponse->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csvResponse->headers->get('content-type'));

        // Delete submission
        $deleteResponse = $this->actingAs($user)->delete('/admin/forms/submissions/'.$submission->id);
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('plugin_form_submissions', ['id' => $submission->id]);
    }

    public function test_deactivation_hides_navigation_and_preserves_data(): void
    {
        $this->pluginManager->install('lcmscore.forms');
        $this->pluginManager->activate('lcmscore.forms');

        Form::query()->create([
            'name' => 'Newsletter',
            'slug' => 'newsletter',
            'fields' => [['name' => 'email', 'label' => 'Email', 'type' => 'email']],
        ]);

        // Deactivate plugin
        $this->pluginManager->deactivate('lcmscore.forms');

        // Navigation should be hidden
        $this->assertFalse($this->navigationRegistry->has('forms-management'));

        // Public route should be unavailable
        $this->post('/forms/newsletter/submit', ['email' => 'test@test.com'])->assertNotFound();

        // Data must be strictly preserved
        $this->assertTrue(Schema::hasTable('plugin_forms'));
        $this->assertDatabaseHas('plugin_forms', ['slug' => 'newsletter']);
    }

    public function test_uninstall_with_purge_drops_tables_and_cleans_permissions(): void
    {
        $this->pluginManager->install('lcmscore.forms');
        $this->pluginManager->activate('lcmscore.forms');

        $this->assertTrue(Schema::hasTable('plugin_forms'));
        $this->assertTrue(Schema::hasTable('plugin_form_submissions'));

        $this->pluginManager->uninstall('lcmscore.forms', purgeData: true);

        $this->assertFalse(Schema::hasTable('plugin_forms'));
        $this->assertFalse(Schema::hasTable('plugin_form_submissions'));
        $this->assertNull($this->pluginRegistry->find('lcmscore.forms'));
    }

    public function test_blade_component_renders_form_properly(): void
    {
        $this->pluginManager->install('lcmscore.forms');
        $this->pluginManager->activate('lcmscore.forms');

        /** @var Form $form */
        $form = Form::query()->create([
            'name' => 'Support Client',
            'slug' => 'support',
            'is_active' => true,
            'honeypot_enabled' => true,
            'submit_label' => 'Contacter le support',
            'fields' => [
                ['name' => 'nom', 'label' => 'Votre nom', 'type' => 'text', 'required' => true],
                ['name' => 'priorite', 'label' => 'Priorité', 'type' => 'select', 'options' => ['Basse', 'Urgente']],
            ],
        ]);

        $rendered = view('forms::components.render', ['form' => $form])->render();

        $this->assertStringContainsString('action="/forms/support/submit"', $rendered);
        $this->assertStringContainsString('name="nom"', $rendered);
        $this->assertStringContainsString('name="priorite"', $rendered);
        $this->assertStringContainsString('name="_hp_website"', $rendered);
        $this->assertStringContainsString('Contacter le support', $rendered);
        $this->assertStringContainsString('value="Urgente"', $rendered);
    }
}
