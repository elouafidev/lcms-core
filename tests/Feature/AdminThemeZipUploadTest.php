<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Themes\ThemeDiscovery;
use App\Core\Themes\ThemeManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

class AdminThemeZipUploadTest extends TestCase
{
    use RefreshDatabase;

    private string $tempThemesDir;

    private string $tempWorkingDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempThemesDir = sys_get_temp_dir().'/cms_zip_test_themes_'.uniqid();
        $this->tempWorkingDir = sys_get_temp_dir().'/cms_zip_test_theme_work_'.uniqid();

        mkdir($this->tempThemesDir, 0777, true);
        mkdir($this->tempWorkingDir, 0777, true);

        config(['cms.themes.path' => $this->tempThemesDir]);

        $this->app->make(ThemeDiscovery::class)->setThemesPath($this->tempThemesDir);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempThemesDir);
        $this->deleteDirectory($this->tempWorkingDir);

        parent::tearDown();
    }

    public function test_guest_cannot_upload_theme(): void
    {
        $response = $this->post(route('admin.themes.upload'), [
            'theme_archive' => UploadedFile::fake()->create('test.zip', 100, 'application/zip'),
        ]);

        $response->assertRedirect('/login');
    }

    public function test_upload_rejects_non_zip_file(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.themes.upload'), [
            'theme_archive' => UploadedFile::fake()->create('malicious.php', 100, 'text/plain'),
        ]);

        $response->assertSessionHasErrors(['theme_archive']);
    }

    public function test_upload_displays_validation_error_alert_on_admin_page(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from(route('admin.themes.index'))->followingRedirects()->post(route('admin.themes.upload'), [
            'theme_archive' => UploadedFile::fake()->create('malicious.php', 100, 'text/plain'),
        ]);

        $response->assertOk();
        $response->assertSee('Veuillez corriger les erreurs suivantes :');
        $response->assertSee("L'archive doit obligatoirement posséder l'extension .zip.");
    }

    public function test_upload_rejects_zip_slip_traversal_attempt(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $zipPath = $this->tempWorkingDir.'/zip_slip.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('../../../evil.txt', 'evil content');
        $zip->close();

        $uploadedFile = new UploadedFile($zipPath, 'zip_slip.zip', 'application/zip', null, true);

        $response = $this->actingAs($user)->post(route('admin.themes.upload'), [
            'theme_archive' => $uploadedFile,
        ]);

        $response->assertSessionHas('error');
        $response->assertSessionHas('error', fn (string $msg) => str_contains($msg, 'Zip Slip'));
    }

    public function test_upload_rejects_forbidden_executable_files(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $zipPath = $this->tempWorkingDir.'/with_exe.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('payload.exe', 'binary contents');
        $zip->addFromString('theme.json', (string) json_encode([
            'id' => 'dangerous-theme',
            'name' => 'Dangerous Theme',
            'version' => '1.0.0',
        ]));
        $zip->close();

        $uploadedFile = new UploadedFile($zipPath, 'with_exe.zip', 'application/zip', null, true);

        $response = $this->actingAs($user)->post(route('admin.themes.upload'), [
            'theme_archive' => $uploadedFile,
        ]);

        $response->assertSessionHas('error');
        $response->assertSessionHas('error', fn (string $msg) => str_contains($msg, 'interdit'));
    }

    public function test_upload_rejects_archive_without_manifest(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $zipPath = $this->tempWorkingDir.'/no_manifest.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('views/index.blade.php', '<h1>No manifest</h1>');
        $zip->close();

        $uploadedFile = new UploadedFile($zipPath, 'no_manifest.zip', 'application/zip', null, true);

        $response = $this->actingAs($user)->post(route('admin.themes.upload'), [
            'theme_archive' => $uploadedFile,
        ]);

        $response->assertSessionHas('error');
        $response->assertSessionHas('error', fn (string $msg) => str_contains($msg, 'theme.json'));
    }

    public function test_upload_rejects_archive_with_malformed_manifest_json(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $zipPath = $this->tempWorkingDir.'/bad_manifest.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('theme.json', '{bad json');
        $zip->close();

        $uploadedFile = new UploadedFile($zipPath, 'bad_manifest.zip', 'application/zip', null, true);

        $response = $this->actingAs($user)->post(route('admin.themes.upload'), [
            'theme_archive' => $uploadedFile,
        ]);

        $response->assertSessionHas('error');
        $response->assertSessionHas('error', fn (string $msg) => str_contains($msg, 'syntaxiquement invalide'));
    }

    public function test_upload_installs_valid_theme_at_archive_root(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $zipPath = $this->tempWorkingDir.'/valid_theme.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('theme.json', (string) json_encode([
            'id' => 'custom-dark',
            'name' => 'Custom Dark Theme',
            'version' => '1.2.0',
            'description' => 'A sleek dark mode theme',
            'author' => ['name' => 'LCMS Core Designers'],
        ]));
        $zip->addFromString('views/layout.blade.php', '<html><body>Theme</body></html>');
        $zip->close();

        $uploadedFile = new UploadedFile($zipPath, 'valid_theme.zip', 'application/zip', null, true);

        $response = $this->actingAs($user)->post(route('admin.themes.upload'), [
            'theme_archive' => $uploadedFile,
        ]);

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHas('success');

        $this->assertDirectoryExists($this->tempThemesDir.'/custom-dark');
        $this->assertFileExists($this->tempThemesDir.'/custom-dark/theme.json');
        $this->assertFileExists($this->tempThemesDir.'/custom-dark/views/layout.blade.php');

        $discovery = $this->app->make(ThemeDiscovery::class);
        $discovery->setThemesPath($this->tempThemesDir);
        $this->assertTrue($discovery->has('custom-dark'));
    }

    public function test_upload_installs_valid_theme_nested_in_subdirectory(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $zipPath = $this->tempWorkingDir.'/nested_theme.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('my-nested-folder/theme.json', (string) json_encode([
            'id' => 'nested-theme',
            'name' => 'Nested Theme',
            'version' => '2.0.0',
        ]));
        $zip->addFromString('my-nested-folder/views/home.blade.php', 'Home view');
        $zip->close();

        $uploadedFile = new UploadedFile($zipPath, 'nested_theme.zip', 'application/zip', null, true);

        $response = $this->actingAs($user)->post(route('admin.themes.upload'), [
            'theme_archive' => $uploadedFile,
        ]);

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHas('success');

        $this->assertDirectoryExists($this->tempThemesDir.'/nested-theme');
        $this->assertFileExists($this->tempThemesDir.'/nested-theme/views/home.blade.php');
    }

    public function test_upload_prevents_duplicate_installation_without_overwrite_flag(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        mkdir($this->tempThemesDir.'/existing-theme', 0777, true);
        file_put_contents($this->tempThemesDir.'/existing-theme/theme.json', (string) json_encode([
            'id' => 'existing-theme',
            'name' => 'Existing Theme',
            'version' => '1.0.0',
        ]));

        $zipPath = $this->tempWorkingDir.'/conflict.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('theme.json', (string) json_encode([
            'id' => 'existing-theme',
            'name' => 'Existing Theme Updated',
            'version' => '1.1.0',
        ]));
        $zip->close();

        $uploadedFile = new UploadedFile($zipPath, 'conflict.zip', 'application/zip', null, true);

        $response = $this->actingAs($user)->post(route('admin.themes.upload'), [
            'theme_archive' => $uploadedFile,
            'overwrite' => '0',
        ]);

        $response->assertSessionHas('error');
        $response->assertSessionHas('error', fn (string $msg) => str_contains($msg, 'déjà présent'));
    }

    public function test_upload_allows_overwrite_when_requested_if_not_active(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        mkdir($this->tempThemesDir.'/existing-theme', 0777, true);
        file_put_contents($this->tempThemesDir.'/existing-theme/theme.json', (string) json_encode([
            'id' => 'existing-theme',
            'name' => 'Existing Theme Old',
            'version' => '1.0.0',
        ]));

        $zipPath = $this->tempWorkingDir.'/overwrite.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('theme.json', (string) json_encode([
            'id' => 'existing-theme',
            'name' => 'Existing Theme New',
            'version' => '2.0.0',
        ]));
        $zip->close();

        $uploadedFile = new UploadedFile($zipPath, 'overwrite.zip', 'application/zip', null, true);

        $response = $this->actingAs($user)->post(route('admin.themes.upload'), [
            'theme_archive' => $uploadedFile,
            'overwrite' => '1',
        ]);

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHas('success');

        /** @var array<string, mixed> $data */
        $data = json_decode((string) file_get_contents($this->tempThemesDir.'/existing-theme/theme.json'), true);
        $this->assertSame('2.0.0', $data['version']);
    }

    public function test_upload_cannot_overwrite_currently_active_theme(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        mkdir($this->tempThemesDir.'/active-theme', 0777, true);
        file_put_contents($this->tempThemesDir.'/active-theme/theme.json', (string) json_encode([
            'id' => 'active-theme',
            'name' => 'Active Theme',
            'version' => '1.0.0',
        ]));

        $themeManager = $this->app->make(ThemeManager::class);
        $themeManager->getDiscovery()->setThemesPath($this->tempThemesDir);
        $themeManager->activate('active-theme');

        $zipPath = $this->tempWorkingDir.'/active_overwrite.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('theme.json', (string) json_encode([
            'id' => 'active-theme',
            'name' => 'Active Theme Hacked',
            'version' => '2.0.0',
        ]));
        $zip->close();

        $uploadedFile = new UploadedFile($zipPath, 'active_overwrite.zip', 'application/zip', null, true);

        $response = $this->actingAs($user)->post(route('admin.themes.upload'), [
            'theme_archive' => $uploadedFile,
            'overwrite' => '1',
        ]);

        $response->assertSessionHas('error');
        $response->assertSessionHas('error', fn (string $msg) => str_contains($msg, 'actuellement actif'));
    }

    public function test_upload_accepts_zip_with_octet_stream_mime_type(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $zipPath = $this->tempWorkingDir.'/octet_stream_theme.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('theme.json', (string) json_encode([
            'id' => 'octet-stream-theme',
            'name' => 'Octet Stream Theme',
            'version' => '1.0.0',
        ]));
        $zip->addFromString('views/index.blade.php', '<h1>Octet Stream Theme</h1>');
        $zip->close();

        // Simulate Windows browser uploading ZIP with application/octet-stream
        $uploadedFile = new UploadedFile($zipPath, 'octet_stream_theme.zip', 'application/octet-stream', null, true);

        $response = $this->actingAs($user)->post(route('admin.themes.upload'), [
            'theme_archive' => $uploadedFile,
        ]);

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHas('success');
        $this->assertFileExists($this->tempThemesDir.'/octet-stream-theme/theme.json');
    }

    public function test_upload_accepts_nested_directory_theme_archive(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $zipPath = $this->tempWorkingDir.'/nested_theme.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('nested-level-1/my-nested-theme/theme.json', (string) json_encode([
            'id' => 'nested-theme',
            'name' => 'Nested Theme',
            'version' => '1.2.0',
        ]));
        $zip->addFromString('nested-level-1/my-nested-theme/views/index.blade.php', '<h1>Nested</h1>');
        $zip->close();

        $uploadedFile = new UploadedFile($zipPath, 'nested_theme.zip', 'application/zip', null, true);

        $response = $this->actingAs($user)->post(route('admin.themes.upload'), [
            'theme_archive' => $uploadedFile,
        ]);

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHas('success');
        $this->assertFileExists($this->tempThemesDir.'/nested-theme/theme.json');
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
