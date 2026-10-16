<?php

declare(strict_types=1);

namespace Tests\Unit\Themes;

use App\Core\Contracts\ThemeInterface;
use App\Core\Contracts\ThemeManifestInterface;
use App\Core\Themes\DTO\DiscoveredTheme;
use App\Core\Themes\DTO\ThemeManifest;
use App\Core\Themes\ThemeDiscovery;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Tests\TestCase;

class ThemeDiscoveryTest extends TestCase
{
    private string $tempThemesDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempThemesDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'cms_test_themes_'.bin2hex(random_bytes(6));
        File::makeDirectory($this->tempThemesDir, 0755, true, true);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->tempThemesDir)) {
            File::deleteDirectory($this->tempThemesDir);
        }

        parent::tearDown();
    }

    public function test_theme_manifest_instantiation_with_valid_data(): void
    {
        $manifest = ThemeManifest::fromArray([
            'id' => 'my-custom-theme',
            'name' => 'My Custom Theme',
            'version' => '1.2.0',
            'description' => 'A clean and elegant theme.',
            'author' => [
                'name' => 'Theme Artisan',
                'email' => 'artisan@example.com',
            ],
            'parent' => 'default-modern',
            'assets' => [
                'css' => 'assets/css/theme.css',
            ],
            'settings' => [
                'primary_color' => '#3b82f6',
            ],
        ]);

        $this->assertSame('my-custom-theme', $manifest->getId());
        $this->assertSame('My Custom Theme', $manifest->getName());
        $this->assertSame('1.2.0', $manifest->getVersion());
        $this->assertSame('A clean and elegant theme.', $manifest->getDescription());
        $this->assertSame('Theme Artisan', $manifest->getAuthor()['name']);
        $this->assertSame('default-modern', $manifest->getParentTheme());
        $this->assertSame('assets/css/theme.css', $manifest->getAssets()['css']);
        $this->assertSame('#3b82f6', $manifest->getSettings()['primary_color']);
    }

    public function test_theme_manifest_throws_on_invalid_or_missing_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ThemeManifest::fromArray([
            'name' => 'No ID Theme',
            'version' => '1.0.0',
        ]);
    }

    public function test_theme_manifest_rejects_path_traversal_in_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('illegal path traversal characters');

        ThemeManifest::fromArray([
            'id' => '../../malicious-theme',
            'name' => 'Exploit Theme',
            'version' => '1.0.0',
        ]);
    }

    public function test_theme_manifest_rejects_path_traversal_in_parent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('illegal path traversal characters');

        ThemeManifest::fromArray([
            'id' => 'valid-theme',
            'name' => 'Valid Theme',
            'version' => '1.0.0',
            'parent' => '../parent-traversal',
        ]);
    }

    public function test_discovered_theme_implements_theme_interface_and_exposes_paths(): void
    {
        $manifest = new ThemeManifest(
            id: 'retro-theme',
            name: 'Retro Theme',
            version: '1.0.0'
        );

        $path = $this->tempThemesDir.DIRECTORY_SEPARATOR.'retro-theme';
        $theme = new DiscoveredTheme(
            id: 'retro-theme',
            name: 'Retro Theme',
            path: $path,
            manifest: $manifest,
            active: false
        );

        $this->assertInstanceOf(ThemeInterface::class, $theme);
        $this->assertInstanceOf(ThemeManifestInterface::class, $theme->getManifest());
        $this->assertSame('retro-theme', $theme->getId());
        $this->assertSame('Retro Theme', $theme->getName());
        $this->assertSame($path, $theme->getPath());
        $this->assertFalse($theme->isActive());

        $theme->setActive(true);
        $this->assertTrue($theme->isActive());

        $this->assertSame($path.'/resources/views', $theme->getViewPath());
        $this->assertSame($path.'/resources/assets', $theme->getAssetPath());
    }

    public function test_theme_discovery_discovers_valid_themes_in_directory(): void
    {
        $theme1Dir = $this->tempThemesDir.DIRECTORY_SEPARATOR.'theme-one';
        File::makeDirectory($theme1Dir);
        File::put($theme1Dir.DIRECTORY_SEPARATOR.'theme.json', (string) json_encode([
            'id' => 'theme-one',
            'name' => 'Theme One',
            'version' => '1.0.0',
            'description' => 'First test theme',
        ]));

        $theme2Dir = $this->tempThemesDir.DIRECTORY_SEPARATOR.'theme-two';
        File::makeDirectory($theme2Dir);
        File::put($theme2Dir.DIRECTORY_SEPARATOR.'theme.json', (string) json_encode([
            'id' => 'theme-two',
            'name' => 'Theme Two',
            'version' => '2.0.0',
        ]));

        // Invalid directory without theme.json
        $emptyDir = $this->tempThemesDir.DIRECTORY_SEPARATOR.'empty-theme';
        File::makeDirectory($emptyDir);

        $discovery = new ThemeDiscovery($this->tempThemesDir);
        $themes = $discovery->discover();

        $this->assertCount(2, $themes);
        $this->assertTrue($discovery->has('theme-one'));
        $this->assertTrue($discovery->has('theme-two'));
        $this->assertFalse($discovery->has('empty-theme'));

        $themeOne = $discovery->get('theme-one');
        $this->assertNotNull($themeOne);
        $this->assertSame('Theme One', $themeOne->getName());
        $this->assertSame('1.0.0', $themeOne->getManifest()->getVersion());
    }

    public function test_theme_discovery_returns_empty_when_directory_does_not_exist(): void
    {
        $discovery = new ThemeDiscovery('/non/existent/path/for/themes');
        $themes = $discovery->discover();

        $this->assertSame([], $themes);
        $this->assertFalse($discovery->has('any-theme'));
    }

    public function test_theme_discovery_skips_malformed_theme_json(): void
    {
        $corruptDir = $this->tempThemesDir.DIRECTORY_SEPARATOR.'corrupt-theme';
        File::makeDirectory($corruptDir);
        File::put($corruptDir.DIRECTORY_SEPARATOR.'theme.json', '{ corrupt json syntax ...');

        $discovery = new ThemeDiscovery($this->tempThemesDir);
        $themes = $discovery->discover();

        $this->assertSame([], $themes);
    }
}
