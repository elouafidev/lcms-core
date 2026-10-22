<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

class AdminPluginZipUploadTest extends TestCase
{
    use RefreshDatabase;

    private string $tempPluginsDir;

    private string $tempWorkingDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempPluginsDir = sys_get_temp_dir().'/cms_zip_test_plugins_'.uniqid();
        $this->tempWorkingDir = sys_get_temp_dir().'/cms_zip_test_work_'.uniqid();

        mkdir($this->tempPluginsDir, 0777, true);
        mkdir($this->tempWorkingDir, 0777, true);

        config(['cms.plugins.path' => $this->tempPluginsDir]);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempPluginsDir);
        $this->deleteDirectory($this->tempWorkingDir);

        parent::tearDown();
    }

    public function test_guest_cannot_upload_plugin(): void
    {
        $response = $this->post(route('admin.plugins.upload'), [
            'plugin_archive' => UploadedFile::fake()->create('test.zip', 100, 'application/zip'),
        ]);

        $response->assertRedirect('/login');
    }

    public function test_upload_rejects_non_zip_file(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.plugins.upload'), [
            'plugin_archive' => UploadedFile::fake()->create('malicious.php', 100, 'text/plain'),
        ]);

        $response->assertSessionHasErrors(['plugin_archive']);
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

        $response = $this->actingAs($user)->post(route('admin.plugins.upload'), [
            'plugin_archive' => $uploadedFile,
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
        $zip->addFromString('plugin.json', json_encode([
            'id' => 'lcmscore.dangerous',
            'name' => 'Dangerous Plugin',
            'version' => '1.0.0',
        ]));
        $zip->close();

        $uploadedFile = new UploadedFile($zipPath, 'with_exe.zip', 'application/zip', null, true);

        $response = $this->actingAs($user)->post(route('admin.plugins.upload'), [
            'plugin_archive' => $uploadedFile,
        ]);

        $response->assertSessionHas('error');
        $response->assertSessionHas('error', fn (string $msg) => str_contains($msg, 'exécutable'));
    }

    public function test_upload_rejects_zip_missing_plugin_manifest(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $zipPath = $this->tempWorkingDir.'/no_manifest.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('readme.txt', 'Just a readme');
        $zip->close();

        $uploadedFile = new UploadedFile($zipPath, 'no_manifest.zip', 'application/zip', null, true);

        $response = $this->actingAs($user)->post(route('admin.plugins.upload'), [
            'plugin_archive' => $uploadedFile,
        ]);

        $response->assertSessionHas('error');
        $response->assertSessionHas('error', fn (string $msg) => str_contains($msg, 'plugin.json'));
    }

    public function test_upload_installs_valid_zip_with_root_manifest(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $manifest = [
            'id' => 'lcmscore.valid-zip',
            'name' => 'Valid Zip Plugin',
            'version' => '1.5.0',
            'description' => 'A plugin imported from ZIP archive.',
            'author' => ['name' => 'Zip Author'],
            'providers' => [],
            'autoload' => [
                'psr-4' => ['ZipPlugin\\' => 'src/'],
            ],
        ];

        $zipPath = $this->tempWorkingDir.'/valid_plugin.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('plugin.json', (string) json_encode($manifest, JSON_PRETTY_PRINT));
        $zip->addFromString('src/Plugin.php', '<?php namespace ZipPlugin; class Plugin {}');
        $zip->close();

        $uploadedFile = new UploadedFile($zipPath, 'valid_plugin.zip', 'application/zip', null, true);

        $response = $this->actingAs($user)->post(route('admin.plugins.upload'), [
            'plugin_archive' => $uploadedFile,
        ]);

        $response->assertRedirect(route('admin.plugins.show', 'lcmscore.valid-zip'));
        $response->assertSessionHas('success');

        $this->assertFileExists($this->tempPluginsDir.'/lcmscore.valid-zip/plugin.json');
        $this->assertFileExists($this->tempPluginsDir.'/lcmscore.valid-zip/src/Plugin.php');
    }

    public function test_upload_installs_valid_zip_with_nested_folder_structure(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $manifest = [
            'id' => 'lcmscore.nested-zip',
            'name' => 'Nested Zip Plugin',
            'version' => '2.1.0',
            'description' => 'A plugin wrapped in a top-level directory.',
            'author' => ['name' => 'Nested Author'],
            'providers' => [],
            'autoload' => [
                'psr-4' => ['NestedPlugin\\' => 'src/'],
            ],
        ];

        $zipPath = $this->tempWorkingDir.'/nested_plugin.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('nested-folder/plugin.json', (string) json_encode($manifest, JSON_PRETTY_PRINT));
        $zip->addFromString('nested-folder/src/Nested.php', '<?php namespace NestedPlugin; class Nested {}');
        $zip->close();

        $uploadedFile = new UploadedFile($zipPath, 'nested_plugin.zip', 'application/zip', null, true);

        $response = $this->actingAs($user)->post(route('admin.plugins.upload'), [
            'plugin_archive' => $uploadedFile,
        ]);

        $response->assertRedirect(route('admin.plugins.show', 'lcmscore.nested-zip'));
        $response->assertSessionHas('success');

        $this->assertFileExists($this->tempPluginsDir.'/lcmscore.nested-zip/plugin.json');
        $this->assertFileExists($this->tempPluginsDir.'/lcmscore.nested-zip/src/Nested.php');
    }

    public function test_upload_duplicate_without_overwrite_fails_and_with_overwrite_succeeds(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $manifestV1 = [
            'id' => 'lcmscore.overwrite-test',
            'name' => 'Overwrite Test Plugin',
            'version' => '1.0.0',
        ];

        $zipPath1 = $this->tempWorkingDir.'/v1.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath1, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('plugin.json', (string) json_encode($manifestV1, JSON_PRETTY_PRINT));
        $zip->close();

        $uploadedV1 = new UploadedFile($zipPath1, 'v1.zip', 'application/zip', null, true);
        $this->actingAs($user)->post(route('admin.plugins.upload'), ['plugin_archive' => $uploadedV1]);

        // Attempt second upload without overwrite
        $manifestV2 = [
            'id' => 'lcmscore.overwrite-test',
            'name' => 'Overwrite Test Plugin V2',
            'version' => '2.0.0',
        ];
        $zipPath2 = $this->tempWorkingDir.'/v2.zip';
        $zip2 = new ZipArchive;
        $zip2->open($zipPath2, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip2->addFromString('plugin.json', (string) json_encode($manifestV2, JSON_PRETTY_PRINT));
        $zip2->close();

        $uploadedV2 = new UploadedFile($zipPath2, 'v2.zip', 'application/zip', null, true);

        $responseNoOverwrite = $this->actingAs($user)->post(route('admin.plugins.upload'), [
            'plugin_archive' => $uploadedV2,
            'overwrite' => 0,
        ]);
        $responseNoOverwrite->assertSessionHas('error');

        // With overwrite=1
        $responseWithOverwrite = $this->actingAs($user)->post(route('admin.plugins.upload'), [
            'plugin_archive' => $uploadedV2,
            'overwrite' => 1,
        ]);
        $responseWithOverwrite->assertSessionHas('success');

        /** @var string $content */
        $content = file_get_contents($this->tempPluginsDir.'/lcmscore.overwrite-test/plugin.json');
        $this->assertStringContainsString('2.0.0', $content);
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
