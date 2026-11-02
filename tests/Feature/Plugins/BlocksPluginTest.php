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
use Illuminate\Support\Facades\Schema;
use Plugins\Blocks\Models\Block;
use Tests\TestCase;

class BlocksPluginTest extends TestCase
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
        $discovered = $this->pluginRegistry->getDiscovered('lcmscore.blocks');

        $this->assertNotNull($discovered);
        $this->assertSame(PluginState::AVAILABLE->value, $discovered->getState());
        $this->assertFalse(Schema::hasTable('plugin_blocks'));
        $this->assertFalse($this->navigationRegistry->has('blocks-management'));
    }

    public function test_install_and_activation_creates_tables_and_navigation(): void
    {
        $this->pluginManager->install('lcmscore.blocks');
        $this->pluginManager->activate('lcmscore.blocks');

        $this->assertTrue(Schema::hasTable('plugin_blocks'));

        // Navigation check
        $this->assertTrue($this->navigationRegistry->has('blocks-management'));
        $item = $this->navigationRegistry->get('blocks-management');
        $this->assertNotNull($item);
        $this->assertSame('Blocs & Sections', $item->getLabel());
        $this->assertSame('/admin/blocks', $item->getUrl());

        // Permissions check
        $this->assertTrue($this->permissionRegistry->has('blocks.manage'));
    }

    public function test_admin_can_create_and_manage_blocks(): void
    {
        $this->pluginManager->install('lcmscore.blocks');
        $this->pluginManager->activate('lcmscore.blocks');

        /** @var User $user */
        $user = User::factory()->create();

        // Check index and create forms
        $this->actingAs($user)->get('/admin/blocks')->assertOk();
        $this->actingAs($user)->get('/admin/blocks/create')->assertOk();

        // Create new Hero block via POST
        $response = $this->actingAs($user)->post('/admin/blocks', [
            'name' => 'Bannière Héroïque Accueil',
            'slug' => 'hero-accueil',
            'type' => 'hero',
            'is_active' => true,
            'order' => 1,
            'content' => [
                'badge' => 'Nouveau en 2026',
                'title' => 'Bienvenue sur LCMS Core',
                'subtitle' => 'La solution modulaire pour vos projets web.',
                'primary_btn_text' => 'Découvrir',
                'primary_btn_url' => '/about',
                'secondary_btn_text' => 'Contact',
                'secondary_btn_url' => '/contact',
                'style' => 'dark',
            ],
        ]);

        $response->assertRedirect('/admin/blocks');
        $this->assertDatabaseHas('plugin_blocks', [
            'name' => 'Bannière Héroïque Accueil',
            'slug' => 'hero-accueil',
            'type' => 'hero',
            'is_active' => 1,
        ]);

        /** @var Block $block */
        $block = Block::query()->where('slug', 'hero-accueil')->firstOrFail();

        // Edit form view
        $this->actingAs($user)->get('/admin/blocks/'.$block->id.'/edit')->assertOk();

        // Update block
        $updateResponse = $this->actingAs($user)->put('/admin/blocks/'.$block->id, [
            'name' => 'Bannière Héroïque V2',
            'slug' => 'hero-accueil-v2',
            'type' => 'hero',
            'is_active' => true,
            'content' => [
                'title' => 'Nouveau Titre V2',
            ],
        ]);

        $updateResponse->assertRedirect('/admin/blocks');
        $this->assertDatabaseHas('plugin_blocks', [
            'id' => $block->id,
            'name' => 'Bannière Héroïque V2',
            'slug' => 'hero-accueil-v2',
        ]);

        // Toggle status
        $toggleResponse = $this->actingAs($user)->post('/admin/blocks/'.$block->id.'/toggle');
        $toggleResponse->assertRedirect('/admin/blocks');
        $block->refresh();
        $this->assertFalse($block->is_active);

        // Delete block
        $deleteResponse = $this->actingAs($user)->delete('/admin/blocks/'.$block->id);
        $deleteResponse->assertRedirect('/admin/blocks');
        $this->assertDatabaseMissing('plugin_blocks', ['id' => $block->id]);
    }

    public function test_render_block_directive_and_types(): void
    {
        $this->pluginManager->install('lcmscore.blocks');
        $this->pluginManager->activate('lcmscore.blocks');

        // Create hero block
        Block::create([
            'name' => 'Hero Demo',
            'slug' => 'hero-demo',
            'type' => 'hero',
            'is_active' => true,
            'content' => [
                'badge' => 'Exclusivité',
                'title' => 'Titre Incroyable',
                'subtitle' => 'Description captivante',
                'primary_btn_text' => 'En savoir plus',
            ],
        ]);

        // Create FAQ block
        Block::create([
            'name' => 'FAQ Demo',
            'slug' => 'faq-demo',
            'type' => 'faq',
            'is_active' => true,
            'content' => [
                'title' => 'Questions Fréquentes',
                'items' => [
                    ['question' => 'Est-ce gratuit ?', 'answer' => 'Oui, open-source sous licence MIT.'],
                ],
            ],
        ]);

        // Render via Blade
        $renderedHero = Blade::render("@renderBlock('hero-demo')");
        $this->assertStringContainsString('lcms-block-hero', $renderedHero);
        $this->assertStringContainsString('Titre Incroyable', $renderedHero);
        $this->assertStringContainsString('Exclusivité', $renderedHero);

        $renderedFaq = Blade::render("@renderBlock('faq-demo')");
        $this->assertStringContainsString('lcms-block-faq', $renderedFaq);
        $this->assertStringContainsString('Est-ce gratuit ?', $renderedFaq);
        $this->assertStringContainsString('open-source sous licence MIT', $renderedFaq);
    }

    public function test_inactive_block_renders_empty_string(): void
    {
        $this->pluginManager->install('lcmscore.blocks');
        $this->pluginManager->activate('lcmscore.blocks');

        Block::create([
            'name' => 'Bloc Inactif',
            'slug' => 'bloc-inactif',
            'type' => 'cta',
            'is_active' => false,
            'content' => ['title' => 'Devrait être invisible'],
        ]);

        $rendered = Blade::render("@renderBlock('bloc-inactif')");
        $this->assertSame('', trim($rendered));
    }

    public function test_block_preview_endpoint(): void
    {
        $this->pluginManager->install('lcmscore.blocks');
        $this->pluginManager->activate('lcmscore.blocks');

        /** @var User $user */
        $user = User::factory()->create();

        /** @var Block $block */
        $block = Block::create([
            'name' => 'CTA Preview Test',
            'slug' => 'cta-preview',
            'type' => 'cta',
            'is_active' => true,
            'content' => [
                'title' => 'Rejoignez-nous',
                'button_text' => 'Cliquez ici',
            ],
        ]);

        $response = $this->actingAs($user)->get('/admin/blocks/'.$block->id.'/preview');
        $response->assertOk();
        $response->assertSee('Aperçu — CTA Preview Test');
        $response->assertSee('Rejoignez-nous');
        $response->assertSee('Cliquez ici');
    }

    public function test_uninstall_with_purge_drops_tables_and_navigation(): void
    {
        $this->pluginManager->install('lcmscore.blocks');
        $this->pluginManager->activate('lcmscore.blocks');

        $this->assertTrue(Schema::hasTable('plugin_blocks'));

        $this->pluginManager->uninstall('lcmscore.blocks', purgeData: true);

        $this->assertFalse(Schema::hasTable('plugin_blocks'));
        $this->assertFalse($this->navigationRegistry->has('blocks-management'));
        $this->assertFalse($this->permissionRegistry->has('blocks.manage'));
    }
}
