<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use App\Core\Admin\NavigationRegistry;
use App\Core\Contracts\MediaStorageInterface;
use App\Core\Lifecycle\PluginState;
use App\Core\PluginManager\PluginManager;
use App\Core\PluginRegistry\PluginRegistry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaLibraryPluginTest extends TestCase
{
    use RefreshDatabase;

    private PluginManager $pluginManager;

    private PluginRegistry $pluginRegistry;

    private NavigationRegistry $navigationRegistry;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->pluginManager = $this->app->make(PluginManager::class);
        $this->pluginRegistry = $this->app->make(PluginRegistry::class);
        $this->navigationRegistry = $this->app->make(NavigationRegistry::class);

        // Ensure plugins path points to native platform/plugins directory
        config(['cms.plugins.path' => base_path('platform/plugins')]);
    }

    public function test_plugin_is_discoverable_and_inactive_initially(): void
    {
        $discovered = $this->pluginRegistry->getDiscovered('acme.media-library');

        $this->assertNotNull($discovered);
        $this->assertSame(PluginState::AVAILABLE->value, $discovered->getState());
        $this->assertFalse(Schema::hasTable('plugin_media_items'));
    }

    public function test_install_and_activation_creates_table_and_registers_service(): void
    {
        $this->pluginManager->install('acme.media-library');
        $this->pluginManager->activate('acme.media-library');

        $this->assertTrue(Schema::hasTable('plugin_media_items'));
        $this->assertTrue($this->app->bound(MediaStorageInterface::class));

        $storage = $this->app->make(MediaStorageInterface::class);
        $this->assertInstanceOf(MediaStorageInterface::class, $storage);

        // Navigation check
        $this->assertTrue($this->navigationRegistry->has('media-library'));
        $item = $this->navigationRegistry->get('media-library');
        $this->assertNotNull($item);
        $this->assertSame('Médiathèque', $item->getLabel());
        $this->assertSame('/admin/media', $item->getUrl());
    }

    public function test_media_storage_service_can_store_and_retrieve_file(): void
    {
        $this->pluginManager->install('acme.media-library');
        $this->pluginManager->activate('acme.media-library');

        /** @var MediaStorageInterface $storage */
        $storage = $this->app->make(MediaStorageInterface::class);

        $file = UploadedFile::fake()->create('banner.png', 100, 'image/png');
        $metadata = $storage->store($file, 'media');

        $this->assertArrayHasKey('id', $metadata);
        $this->assertSame('banner.png', $metadata['name']);
        $this->assertStringStartsWith('media/', $metadata['file_path']);
        $this->assertSame('image/png', $metadata['mime_type']);
        $this->assertTrue($storage->exists($metadata['file_path']));

        $found = $storage->find($metadata['id']);
        $this->assertNotNull($found);
        $this->assertSame('banner.png', $found['name']);

        $all = $storage->all();
        $this->assertCount(1, $all);
        $this->assertSame('banner.png', $all[0]['name']);

        // Test delete
        $deleted = $storage->delete($metadata['id']);
        $this->assertTrue($deleted);
        $this->assertFalse($storage->exists($metadata['file_path']));
        $this->assertNull($storage->find($metadata['id']));
    }

    public function test_guest_is_redirected_to_login_when_accessing_media_routes(): void
    {
        $this->pluginManager->install('acme.media-library');
        $this->pluginManager->activate('acme.media-library');

        $response = $this->get('/admin/media');
        $response->assertRedirect('/login');

        $responseUpload = $this->post('/admin/media/upload', []);
        $responseUpload->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_media_gallery(): void
    {
        $this->pluginManager->install('acme.media-library');
        $this->pluginManager->activate('acme.media-library');

        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/media');

        $response->assertOk();
        $response->assertSee('Médiathèque');
    }

    public function test_authenticated_user_can_upload_valid_media_via_http(): void
    {
        $this->pluginManager->install('acme.media-library');
        $this->pluginManager->activate('acme.media-library');

        /** @var User $user */
        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('portfolio.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($user)->postJson('/admin/media/upload', [
            'file' => $file,
            'alt_text' => 'Portfolio Photo',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.name', 'portfolio.jpg');
        $response->assertJsonPath('data.alt_text', 'Portfolio Photo');

        $this->assertDatabaseHas('plugin_media_items', [
            'name' => 'portfolio.jpg',
            'alt_text' => 'Portfolio Photo',
        ]);
    }

    public function test_authenticated_user_can_upload_media_via_standard_html_form_to_upload_route(): void
    {
        $this->pluginManager->install('acme.media-library');
        $this->pluginManager->activate('acme.media-library');

        /** @var User $user */
        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('document.pdf', 200, 'application/pdf');

        $response = $this->actingAs($user)->post('/admin/media/upload', [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('plugin_media_items', [
            'name' => 'document.pdf',
        ]);
    }

    public function test_authenticated_user_can_upload_media_via_post_to_base_media_route(): void
    {
        $this->pluginManager->install('acme.media-library');
        $this->pluginManager->activate('acme.media-library');

        /** @var User $user */
        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('photo.png', 150, 'image/png');

        $response = $this->actingAs($user)->post('/admin/media', [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('plugin_media_items', [
            'name' => 'photo.png',
        ]);
    }

    public function test_upload_rejects_forbidden_mime_types(): void
    {
        $this->pluginManager->install('acme.media-library');
        $this->pluginManager->activate('acme.media-library');

        /** @var User $user */
        $user = User::factory()->create();

        $maliciousFile = UploadedFile::fake()->create('exploit.php', 10, 'application/x-php');

        $response = $this->actingAs($user)->postJson('/admin/media/upload', [
            'file' => $maliciousFile,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_authenticated_user_can_delete_media_via_http(): void
    {
        $this->pluginManager->install('acme.media-library');
        $this->pluginManager->activate('acme.media-library');

        /** @var MediaStorageInterface $storage */
        $storage = $this->app->make(MediaStorageInterface::class);
        $metadata = $storage->store(UploadedFile::fake()->create('temp.png', 50, 'image/png'), 'media');

        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->deleteJson('/admin/media/'.$metadata['id']);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseMissing('plugin_media_items', ['id' => $metadata['id']]);
    }

    public function test_deactivation_hides_navigation_and_preserves_data(): void
    {
        $this->pluginManager->install('acme.media-library');
        $this->pluginManager->activate('acme.media-library');

        /** @var MediaStorageInterface $storage */
        $storage = $this->app->make(MediaStorageInterface::class);
        $storage->store(UploadedFile::fake()->create('keep_me.jpg', 50, 'image/jpeg'), 'media');

        // Deactivate plugin
        $this->pluginManager->deactivate('acme.media-library');

        // Navigation should be hidden
        $this->assertFalse($this->navigationRegistry->has('media-library'));

        // Data must be strictly preserved
        $this->assertTrue(Schema::hasTable('plugin_media_items'));
        $this->assertDatabaseHas('plugin_media_items', [
            'name' => 'keep_me.jpg',
        ]);
    }

    public function test_uninstall_with_purge_drops_table(): void
    {
        $this->pluginManager->install('acme.media-library');
        $this->pluginManager->activate('acme.media-library');

        $this->assertTrue(Schema::hasTable('plugin_media_items'));

        $this->pluginManager->uninstall('acme.media-library', purgeData: true);

        $this->assertFalse(Schema::hasTable('plugin_media_items'));
        $this->assertNull($this->pluginRegistry->find('acme.media-library'));
    }
}
