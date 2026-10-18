<?php

declare(strict_types=1);

namespace Tests\Feature\Themes;

use App\Core\Themes\ThemeAssetManager;
use App\Core\Themes\ThemeDiscovery;
use App\Core\Themes\ThemeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ThemeAssetManagerTest extends TestCase
{
    use RefreshDatabase;

    private ThemeAssetManager $assetManager;

    private ThemeManager $themeManager;

    private ThemeDiscovery $discovery;

    private string $tempThemesDir;

    private string $tempPublicDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempThemesDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'cms_test_themes_'.bin2hex(random_bytes(6));
        $this->tempPublicDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'cms_test_public_'.bin2hex(random_bytes(6));

        File::makeDirectory($this->tempThemesDir, 0755, true, true);
        File::makeDirectory($this->tempPublicDir, 0755, true, true);

        $this->assetManager = new ThemeAssetManager(
            publicBasePath: $this->tempPublicDir
        );

        // Bind isolated asset manager into the container so commands and ThemeManager resolve it
        $this->app->instance(ThemeAssetManager::class, $this->assetManager);
        $this->app->forgetInstance(ThemeManager::class);

        $this->themeManager = $this->app->make(ThemeManager::class);
        $this->discovery = $this->themeManager->getDiscovery();
        $this->discovery->setThemesPath($this->tempThemesDir);
    }

    protected function tearDown(): void
    {
        if ($this->themeManager->hasActiveTheme()) {
            $this->themeManager->deactivate();
        }

        if (File::isDirectory($this->tempThemesDir)) {
            File::deleteDirectory($this->tempThemesDir);
        }

        if (File::isDirectory($this->tempPublicDir)) {
            File::deleteDirectory($this->tempPublicDir);
        }

        parent::tearDown();
    }

    public function test_publish_assets_from_resources_assets_directory(): void
    {
        $themeDir = $this->createTheme('modern-theme', [
            'id' => 'modern-theme',
            'name' => 'Modern Theme',
            'version' => '1.0.0',
        ]);

        $assetsDir = $themeDir.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'assets';
        File::makeDirectory($assetsDir.DIRECTORY_SEPARATOR.'css', 0755, true, true);
        File::put($assetsDir.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'style.css', 'body { margin: 0; }');

        $result = $this->assetManager->publish('modern-theme', $themeDir);

        $this->assertTrue($result);
        $this->assertTrue($this->assetManager->hasPublished('modern-theme'));

        $publishedCss = $this->tempPublicDir.DIRECTORY_SEPARATOR.'modern-theme'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'style.css';
        $this->assertFileExists($publishedCss);
        $this->assertSame('body { margin: 0; }', File::get($publishedCss));
    }

    public function test_publish_assets_from_assets_directory_convention(): void
    {
        $themeDir = $this->createTheme('assets-theme', [
            'id' => 'assets-theme',
            'name' => 'Assets Theme',
            'version' => '1.0.0',
        ]);

        $assetsDir = $themeDir.DIRECTORY_SEPARATOR.'assets';
        File::makeDirectory($assetsDir.DIRECTORY_SEPARATOR.'js', 0755, true, true);
        File::put($assetsDir.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'main.js', 'console.log("theme loaded");');

        $result = $this->assetManager->publish('assets-theme', $themeDir);

        $this->assertTrue($result);
        $publishedJs = $this->tempPublicDir.DIRECTORY_SEPARATOR.'assets-theme'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'main.js';
        $this->assertFileExists($publishedJs);
        $this->assertSame('console.log("theme loaded");', File::get($publishedJs));
    }

    public function test_publish_returns_false_when_no_assets_directory_exists(): void
    {
        $themeDir = $this->createTheme('bare-theme', [
            'id' => 'bare-theme',
            'name' => 'Bare Theme',
            'version' => '1.0.0',
        ]);

        $result = $this->assetManager->publish('bare-theme', $themeDir);

        $this->assertFalse($result);
        $this->assertFalse($this->assetManager->hasPublished('bare-theme'));
    }

    public function test_publish_with_force_cleans_and_overwrites_existing_files(): void
    {
        $themeDir = $this->createTheme('overwrite-theme', [
            'id' => 'overwrite-theme',
            'name' => 'Overwrite Theme',
            'version' => '1.0.0',
        ]);

        $assetsDir = $themeDir.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'assets';
        File::makeDirectory($assetsDir, 0755, true, true);
        File::put($assetsDir.DIRECTORY_SEPARATOR.'app.css', '/* V1 */');

        $this->assetManager->publish('overwrite-theme', $themeDir);
        $published = $this->tempPublicDir.DIRECTORY_SEPARATOR.'overwrite-theme'.DIRECTORY_SEPARATOR.'app.css';
        $this->assertSame('/* V1 */', File::get($published));

        // Update source and publish with force
        File::put($assetsDir.DIRECTORY_SEPARATOR.'app.css', '/* V2 */');
        $this->assetManager->publish('overwrite-theme', $themeDir, force: true);

        $this->assertSame('/* V2 */', File::get($published));
    }

    public function test_publish_with_symlink_creates_valid_link_or_copies(): void
    {
        $themeDir = $this->createTheme('symlink-theme', [
            'id' => 'symlink-theme',
            'name' => 'Symlink Theme',
            'version' => '1.0.0',
        ]);

        $assetsDir = $themeDir.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'assets';
        File::makeDirectory($assetsDir, 0755, true, true);
        File::put($assetsDir.DIRECTORY_SEPARATOR.'theme.css', '.symlink { display: block; }');

        $result = $this->assetManager->publish('symlink-theme', $themeDir, symlink: true);

        $this->assertTrue($result);
        $this->assertTrue($this->assetManager->hasPublished('symlink-theme'));

        $published = $this->tempPublicDir.DIRECTORY_SEPARATOR.'symlink-theme'.DIRECTORY_SEPARATOR.'theme.css';
        $this->assertFileExists($published);
    }

    public function test_clean_removes_published_assets(): void
    {
        $themeDir = $this->createTheme('clean-theme', [
            'id' => 'clean-theme',
            'name' => 'Clean Theme',
            'version' => '1.0.0',
        ]);

        $assetsDir = $themeDir.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'assets';
        File::makeDirectory($assetsDir, 0755, true, true);
        File::put($assetsDir.DIRECTORY_SEPARATOR.'style.css', 'a { color: blue; }');

        $this->assetManager->publish('clean-theme', $themeDir);
        $this->assertTrue($this->assetManager->hasPublished('clean-theme'));

        $cleaned = $this->assetManager->clean('clean-theme');
        $this->assertTrue($cleaned);
        $this->assertFalse($this->assetManager->hasPublished('clean-theme'));
        $this->assertFalse(File::isDirectory($this->tempPublicDir.DIRECTORY_SEPARATOR.'clean-theme'));
    }

    public function test_theme_asset_url_helper_generates_valid_absolute_url(): void
    {
        $url = ThemeAssetManager::url('default-modern', 'css/app.css');

        $this->assertStringContainsString('vendor/themes/default-modern/css/app.css', $url);
        $this->assertStringStartsWith('http', $url);

        // Leading slashes handled properly
        $urlWithSlash = ThemeAssetManager::url('default-modern', '/js/bundle.js');
        $this->assertStringContainsString('vendor/themes/default-modern/js/bundle.js', $urlWithSlash);
    }

    public function test_console_command_publishes_assets_for_specific_theme(): void
    {
        $themeDir = $this->createTheme('command-theme', [
            'id' => 'command-theme',
            'name' => 'Command Theme',
            'version' => '1.0.0',
        ]);

        $assetsDir = $themeDir.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'assets';
        File::makeDirectory($assetsDir, 0755, true, true);
        File::put($assetsDir.DIRECTORY_SEPARATOR.'cli.css', 'header { color: gold; }');

        $this->artisan('cms:theme:publish-assets', ['id' => 'command-theme'])
            ->expectsOutputToContain("Assets for theme 'command-theme' published successfully")
            ->assertSuccessful();

        $published = $this->tempPublicDir.DIRECTORY_SEPARATOR.'command-theme'.DIRECTORY_SEPARATOR.'cli.css';
        $this->assertFileExists($published);
        $this->assertSame('header { color: gold; }', File::get($published));
    }

    public function test_console_command_publishes_active_theme_when_no_arguments_given(): void
    {
        $themeDir = $this->createTheme('active-publish-theme', [
            'id' => 'active-publish-theme',
            'name' => 'Active Publish Theme',
            'version' => '1.0.0',
        ]);

        $assetsDir = $themeDir.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'assets';
        File::makeDirectory($assetsDir, 0755, true, true);
        File::put($assetsDir.DIRECTORY_SEPARATOR.'active.css', 'body { background: #000; }');

        // Activate theme
        $this->themeManager->activate('active-publish-theme');

        // Command with no arguments should default to active theme
        $this->artisan('cms:theme:publish-assets')
            ->expectsOutputToContain("Assets for theme 'active-publish-theme' published successfully")
            ->assertSuccessful();

        $published = $this->tempPublicDir.DIRECTORY_SEPARATOR.'active-publish-theme'.DIRECTORY_SEPARATOR.'active.css';
        $this->assertFileExists($published);
    }

    public function test_console_command_fails_gracefully_when_no_theme_specified_and_none_active(): void
    {
        $this->artisan('cms:theme:publish-assets')
            ->expectsOutputToContain('Please specify a theme identifier, activate a theme, or use the --all option.')
            ->assertFailed();
    }

    public function test_console_command_publishes_all_themes_with_all_flag(): void
    {
        $theme1 = $this->createTheme('batch-one', [
            'id' => 'batch-one',
            'name' => 'Batch One',
            'version' => '1.0.0',
        ]);
        File::makeDirectory($theme1.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'assets', 0755, true, true);
        File::put($theme1.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'one.css', '/* 1 */');

        $theme2 = $this->createTheme('batch-two', [
            'id' => 'batch-two',
            'name' => 'Batch Two',
            'version' => '1.0.0',
        ]);
        File::makeDirectory($theme2.DIRECTORY_SEPARATOR.'assets', 0755, true, true);
        File::put($theme2.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'two.css', '/* 2 */');

        $this->artisan('cms:theme:publish-assets', ['--all' => true])
            ->expectsOutputToContain('Published assets for 2 theme(s).')
            ->assertSuccessful();

        $this->assertFileExists($this->tempPublicDir.DIRECTORY_SEPARATOR.'batch-one'.DIRECTORY_SEPARATOR.'one.css');
        $this->assertFileExists($this->tempPublicDir.DIRECTORY_SEPARATOR.'batch-two'.DIRECTORY_SEPARATOR.'two.css');
    }

    public function test_console_command_fails_for_non_existent_theme(): void
    {
        $this->artisan('cms:theme:publish-assets', ['id' => 'unknown-theme'])
            ->expectsOutputToContain("Theme 'unknown-theme' not found.")
            ->assertFailed();
    }

    public function test_theme_manager_activation_automatically_publishes_assets(): void
    {
        $themeDir = $this->createTheme('auto-theme', [
            'id' => 'auto-theme',
            'name' => 'Auto Theme',
            'version' => '1.0.0',
        ]);

        $assetsDir = $themeDir.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'assets';
        File::makeDirectory($assetsDir, 0755, true, true);
        File::put($assetsDir.DIRECTORY_SEPARATOR.'auto.css', 'div { opacity: 1; }');

        // Activating should automatically trigger asset publishing
        $this->themeManager->activate('auto-theme');

        $published = $this->tempPublicDir.DIRECTORY_SEPARATOR.'auto-theme'.DIRECTORY_SEPARATOR.'auto.css';
        $this->assertFileExists($published);
        $this->assertSame('div { opacity: 1; }', File::get($published));
    }

    private function createTheme(string $id, array $manifestData): string
    {
        $themeDir = $this->tempThemesDir.DIRECTORY_SEPARATOR.$id;
        File::makeDirectory($themeDir, 0755, true, true);

        File::put(
            $themeDir.DIRECTORY_SEPARATOR.'theme.json',
            (string) json_encode($manifestData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return $themeDir;
    }
}
