<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Updater\CoreZipUpdater;
use App\Core\Updater\Models\UpdateHistory;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

class CoreZipUpdaterTest extends TestCase
{
    use RefreshDatabase;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->tempDir = sys_get_temp_dir().'/cms_updater_test_'.uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempDir);
        parent::tearDown();
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    private function createZipArchive(string $zipPath, array $files): void
    {
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();
    }

    public function test_admin_can_view_update_page(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->get('/admin/settings/update');
        $response->assertOk();
        $response->assertSee('Mise à jour de la plateforme');
        $response->assertSee('Vérifications préalables du serveur');
    }

    public function test_update_fails_if_manifest_missing(): void
    {
        $zipPath = $this->tempDir.'/no_manifest.zip';
        $this->createZipArchive($zipPath, [
            'app/Core/test.txt' => 'dummy',
        ]);

        /** @var CoreZipUpdater $updater */
        $updater = app(CoreZipUpdater::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Manifeste [update.json] manquant');

        $updater->updateFromZip($zipPath);
    }

    public function test_update_fails_on_zip_slip(): void
    {
        $zipPath = $this->tempDir.'/zip_slip.zip';
        $this->createZipArchive($zipPath, [
            'update.json' => (string) json_encode([
                'name' => 'lcms-core',
                'version' => '1.2.0',
            ]),
            '../evil.php' => '<?php echo "evil";',
        ]);

        /** @var CoreZipUpdater $updater */
        $updater = app(CoreZipUpdater::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('traversée de répertoire détectée');

        $updater->updateFromZip($zipPath);
    }

    public function test_update_fails_with_unauthorized_files(): void
    {
        $zipPath = $this->tempDir.'/unauthorized.zip';
        $this->createZipArchive($zipPath, [
            'update.json' => (string) json_encode([
                'name' => 'lcms-core',
                'version' => '1.2.0',
            ]),
            '.env' => 'APP_KEY=stolen',
        ]);

        /** @var CoreZipUpdater $updater */
        $updater = app(CoreZipUpdater::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Fichier non autorisé');

        $updater->updateFromZip($zipPath);
    }

    public function test_valid_update_is_applied_and_records_history(): void
    {
        $zipPath = $this->tempDir.'/valid_update.zip';
        $this->createZipArchive($zipPath, [
            'update.json' => (string) json_encode([
                'name' => 'lcms-core',
                'version' => '1.2.0',
                'min_core_version' => '1.0.0',
            ]),
            'app/Core/test_update_file.txt' => 'Updated content v1.2.0',
        ]);

        /** @var User $admin */
        $admin = User::factory()->create();

        $uploadedFile = new UploadedFile($zipPath, 'valid_update.zip', 'application/zip', null, true);

        $response = $this->actingAs($admin)->post('/admin/settings/update/upload', [
            'update_zip' => $uploadedFile,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $history = UpdateHistory::latest('id')->first();
        $this->assertNotNull($history);
        $this->assertSame('1.2.0', $history->to_version);
        $this->assertSame('SUCCESS', $history->status);

        // Verify the updated file arrived in app/Core
        $targetFile = base_path('app/Core/test_update_file.txt');
        $this->assertFileExists($targetFile);
        $this->assertSame('Updated content v1.2.0', file_get_contents($targetFile));

        // Clean up created file
        unlink($targetFile);
    }
}
