<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Contracts\PluginManifestInterface;
use App\Core\PluginDiscovery\DTO\PluginManifest;
use App\Core\PluginDiscovery\Exceptions\InvalidManifestException;
use App\Core\PluginDiscovery\ManifestValidator;
use Tests\TestCase;

class ManifestValidatorTest extends TestCase
{
    private ManifestValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ManifestValidator;
    }

    public function test_throws_exception_on_malformed_json(): void
    {
        $this->expectException(InvalidManifestException::class);
        $this->expectExceptionMessage('JSON syntax error');

        $this->validator->validate('{"id": "lcmscore.test", "name": "Test", incomplete_json');
    }

    public function test_throws_exception_when_required_fields_are_missing(): void
    {
        $manifestWithoutId = [
            'name' => 'Demo Plugin',
            'version' => '1.0.0',
        ];

        $this->expectException(InvalidManifestException::class);
        $this->expectExceptionMessage("Required field 'id' is missing.");

        $this->validator->validate($manifestWithoutId);
    }

    public function test_throws_exception_on_invalid_identifier(): void
    {
        $invalidIds = [
            'invalid_id_without_vendor',
            'Lcmscore.DemoPlugin', // uppercase
            'vendor.slug with spaces',
            'vendor..double_dot',
        ];

        foreach ($invalidIds as $invalidId) {
            try {
                $this->validator->validate([
                    'id' => $invalidId,
                    'name' => 'Valid Name',
                    'version' => '1.0.0',
                ]);
                $this->fail("Expected InvalidManifestException for identifier: {$invalidId}");
            } catch (InvalidManifestException $e) {
                $this->assertStringContainsString("Identifier '{$invalidId}' is invalid", $e->getMessage());
            }
        }
    }

    public function test_throws_exception_on_invalid_semver_version(): void
    {
        $invalidVersions = [
            '1.0',
            'v1.0.0',
            '1.0.0.0',
            'beta-release',
        ];

        foreach ($invalidVersions as $invalidVersion) {
            try {
                $this->validator->validate([
                    'id' => 'lcmscore.valid-slug',
                    'name' => 'Valid Name',
                    'version' => $invalidVersion,
                ]);
                $this->fail("Expected InvalidManifestException for version: {$invalidVersion}");
            } catch (InvalidManifestException $e) {
                $this->assertStringContainsString("Version '{$invalidVersion}' does not comply with SemVer", $e->getMessage());
            }
        }
    }

    public function test_throws_exception_on_directory_traversal_in_routes(): void
    {
        $payload = [
            'id' => 'lcmscore.safe-plugin',
            'name' => 'Safe Plugin',
            'version' => '1.0.0',
            'routes' => [
                'web' => '../routes/malicious.php',
            ],
        ];

        $this->expectException(InvalidManifestException::class);
        $this->expectExceptionMessage('Directory traversal detected in path');

        $this->validator->validate($payload);
    }

    public function test_throws_exception_on_directory_traversal_in_autoload(): void
    {
        $payload = [
            'id' => 'lcmscore.safe-plugin',
            'name' => 'Safe Plugin',
            'version' => '1.0.0',
            'autoload' => [
                'psr-4' => [
                    'Plugins\\Malicious\\' => '..\\..\\sensitive_folder',
                ],
            ],
        ];

        $this->expectException(InvalidManifestException::class);
        $this->expectExceptionMessage('Directory traversal detected in path');

        $this->validator->validate($payload);
    }

    public function test_throws_exception_on_absolute_path(): void
    {
        $payload = [
            'id' => 'lcmscore.safe-plugin',
            'name' => 'Safe Plugin',
            'version' => '1.0.0',
            'routes' => [
                'web' => '/etc/passwd',
            ],
        ];

        $this->expectException(InvalidManifestException::class);
        $this->expectExceptionMessage('Absolute path');

        $this->validator->validate($payload);
    }

    public function test_validates_and_parses_valid_manifest_successfully(): void
    {
        $validJson = json_encode([
            'id' => 'lcmscore.demo-hello',
            'name' => 'LCMS Core Demo Hello',
            'version' => '1.0.0',
            'description' => 'Demo plugin for testing contracts.',
            'author' => [
                'name' => 'Core Team',
                'email' => 'core@cms.local',
            ],
            'compatibility' => [
                'cms' => '^1.0.0',
                'php' => '>=8.2',
                'laravel' => '^11.0|^12.0|^13.0',
            ],
            'providers' => [
                'Plugins\\DemoHello\\Providers\\DemoPluginServiceProvider',
            ],
            'dependencies' => [
                'lcmscore.media-library' => '^1.0.0',
            ],
            'conflicts' => [
                'legacy.hello',
            ],
            'permissions' => [
                ['key' => 'demo.view', 'label' => 'Consulter les pages de démonstration'],
            ],
            'routes' => [
                'web' => 'routes/web.php',
                'api' => null,
            ],
            'autoload' => [
                'psr-4' => [
                    'Plugins\\DemoHello\\' => 'src/',
                ],
            ],
        ]);

        $this->assertIsString($validJson);
        $manifest = $this->validator->validate($validJson);

        $this->assertInstanceOf(PluginManifest::class, $manifest);
        $this->assertInstanceOf(PluginManifestInterface::class, $manifest);
        $this->assertEquals('lcmscore.demo-hello', $manifest->getId());
        $this->assertEquals('LCMS Core Demo Hello', $manifest->getName());
        $this->assertEquals('1.0.0', $manifest->getVersion());
        $this->assertEquals('Demo plugin for testing contracts.', $manifest->getDescription());
        $this->assertEquals(['Plugins\\DemoHello\\Providers\\DemoPluginServiceProvider'], $manifest->getProviders());
        $this->assertEquals(['lcmscore.media-library' => '^1.0.0'], $manifest->getDependencies());
        $this->assertEquals(['legacy.hello'], $manifest->getConflicts());
        $this->assertEquals('routes/web.php', $manifest->getRoutes()['web']);
        $this->assertEquals(['Plugins\\DemoHello\\' => 'src/'], $manifest->getAutoload()['psr-4']);
    }

    public function test_validates_manifest_file_from_disk(): void
    {
        $tempPath = sys_get_temp_dir().'/test_plugin_'.uniqid().'.json';
        file_put_contents($tempPath, json_encode([
            'id' => 'vendor.disk-plugin',
            'name' => 'Disk Plugin',
            'version' => '2.1.0-alpha.1',
        ]));

        try {
            $manifest = $this->validator->validateFile($tempPath);
            $this->assertEquals('vendor.disk-plugin', $manifest->getId());
            $this->assertEquals('2.1.0-alpha.1', $manifest->getVersion());
        } finally {
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }
}
