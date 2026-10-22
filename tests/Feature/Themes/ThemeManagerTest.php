<?php

declare(strict_types=1);

namespace Tests\Feature\Themes;

use App\Core\PluginManager\PluginManager;
use App\Core\Themes\DTO\DiscoveredTheme;
use App\Core\Themes\Models\ThemeRecord;
use App\Core\Themes\ThemeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Plugins\Pages\Models\Page;
use Tests\TestCase;

class ThemeManagerTest extends TestCase
{
    use RefreshDatabase;

    private ThemeManager $themeManager;

    private PluginManager $pluginManager;

    private string $tempThemesDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->themeManager = $this->app->make(ThemeManager::class);
        $this->pluginManager = $this->app->make(PluginManager::class);

        $this->tempThemesDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'cms_test_themes_'.bin2hex(random_bytes(6));
        File::makeDirectory($this->tempThemesDir, 0755, true, true);

        $this->themeManager->getDiscovery()->setThemesPath($this->tempThemesDir);

        config(['cms.plugins.path' => base_path('platform/plugins')]);
    }

    protected function tearDown(): void
    {
        if ($this->themeManager->hasActiveTheme()) {
            $this->themeManager->deactivate();
        }

        if (File::isDirectory($this->tempThemesDir)) {
            File::deleteDirectory($this->tempThemesDir);
        }

        parent::tearDown();
    }

    public function test_cms_themes_table_exists_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('cms_themes'));
        $this->assertTrue(Schema::hasColumns('cms_themes', [
            'id',
            'theme_id',
            'name',
            'is_active',
            'settings',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_initial_state_has_no_active_theme(): void
    {
        $this->assertFalse($this->themeManager->hasActiveTheme());
        $this->assertNull($this->themeManager->getActiveTheme());
        $this->assertNull($this->themeManager->getActiveThemeId());
    }

    public function test_activating_theme_persists_in_cms_themes_table(): void
    {
        $this->createTheme('modern-dark', [
            'id' => 'modern-dark',
            'name' => 'Modern Dark Theme',
            'version' => '1.0.0',
            'settings' => ['dark_mode' => true],
        ]);

        $theme = $this->themeManager->activate('modern-dark');

        $this->assertInstanceOf(DiscoveredTheme::class, $theme);
        $this->assertTrue($theme->isActive());
        $this->assertTrue($this->themeManager->hasActiveTheme());
        $this->assertSame('modern-dark', $this->themeManager->getActiveThemeId());

        $this->assertDatabaseHas('cms_themes', [
            'theme_id' => 'modern-dark',
            'name' => 'Modern Dark Theme',
            'is_active' => true,
        ]);

        /** @var ThemeRecord $record */
        $record = ThemeRecord::query()->where('theme_id', 'modern-dark')->firstOrFail();
        $this->assertTrue($record->is_active);
        $this->assertSame(['dark_mode' => true], $record->settings);
    }

    public function test_activating_unknown_theme_throws_invalid_argument_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Theme 'non-existent' not found on disk.");

        $this->themeManager->activate('non-existent');
    }

    public function test_activating_new_theme_automatically_deactivates_previous_active_theme(): void
    {
        $this->createTheme('theme-one', [
            'id' => 'theme-one',
            'name' => 'Theme One',
            'version' => '1.0.0',
        ]);

        $this->createTheme('theme-two', [
            'id' => 'theme-two',
            'name' => 'Theme Two',
            'version' => '1.0.0',
        ]);

        $this->themeManager->activate('theme-one');
        $this->assertSame('theme-one', $this->themeManager->getActiveThemeId());
        $this->assertDatabaseHas('cms_themes', ['theme_id' => 'theme-one', 'is_active' => true]);

        $this->themeManager->activate('theme-two');
        $this->assertSame('theme-two', $this->themeManager->getActiveThemeId());

        // Previous theme must be deactivated in database
        $this->assertDatabaseHas('cms_themes', ['theme_id' => 'theme-one', 'is_active' => false]);
        $this->assertDatabaseHas('cms_themes', ['theme_id' => 'theme-two', 'is_active' => true]);

        // Only one active theme record in table
        $activeCount = ThemeRecord::query()->where('is_active', true)->count();
        $this->assertSame(1, $activeCount);
    }

    public function test_deactivate_marks_theme_inactive_and_clears_active_state(): void
    {
        $this->createTheme('deactivate-me', [
            'id' => 'deactivate-me',
            'name' => 'Deactivate Me',
            'version' => '1.0.0',
        ]);

        $this->themeManager->activate('deactivate-me');
        $this->assertTrue($this->themeManager->hasActiveTheme());

        $this->themeManager->deactivate();

        $this->assertFalse($this->themeManager->hasActiveTheme());
        $this->assertNull($this->themeManager->getActiveTheme());
        $this->assertNull($this->themeManager->getActiveThemeId());
        $this->assertDatabaseHas('cms_themes', ['theme_id' => 'deactivate-me', 'is_active' => false]);
    }

    public function test_view_overriding_hierarchy_theme_takes_precedence_over_plugin_view(): void
    {
        // 1. Install and activate lcmscore.pages plugin
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

        // Verify original plugin view rendering
        $dummyPage = new Page([
            'title' => 'Original Plugin Page',
            'slug' => 'original-page',
            'content' => '<p>Plugin Body</p>',
            'status' => 'PUBLISHED',
        ]);

        $originalRendered = view('pages::page', ['page' => $dummyPage])->render();
        $this->assertStringContainsString('<title>Original Plugin Page</title>', $originalRendered);
        $this->assertStringNotContainsString('THEME CUSTOM OVERRIDE', $originalRendered);

        // 2. Create theme that overrides views/plugins/pages/page.blade.php
        $themeDir = $this->createTheme('custom-pages-theme', [
            'id' => 'custom-pages-theme',
            'name' => 'Custom Pages Theme',
            'version' => '1.0.0',
        ]);

        $overrideDir = $themeDir.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'pages';
        File::makeDirectory($overrideDir, 0755, true, true);
        File::put(
            $overrideDir.DIRECTORY_SEPARATOR.'page.blade.php',
            '<div class="theme-wrapper"><h1>THEME CUSTOM OVERRIDE: {{ $page->title }}</h1></div>'
        );

        // 3. Activate theme
        $this->themeManager->activate('custom-pages-theme');

        // 4. view('pages::page') must now render the theme's custom template
        $customRendered = view('pages::page', ['page' => $dummyPage])->render();
        $this->assertStringContainsString('<div class="theme-wrapper"><h1>THEME CUSTOM OVERRIDE: Original Plugin Page</h1></div>', $customRendered);
        $this->assertStringNotContainsString('<title>Original Plugin Page</title>', $customRendered);
    }

    public function test_automatic_fallback_to_plugin_view_when_theme_does_not_override_template(): void
    {
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

        // Theme overrides page.blade.php but NOT admin/index.blade.php
        $themeDir = $this->createTheme('partial-theme', [
            'id' => 'partial-theme',
            'name' => 'Partial Override Theme',
            'version' => '1.0.0',
        ]);

        $overrideDir = $themeDir.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'pages';
        File::makeDirectory($overrideDir, 0755, true, true);
        File::put(
            $overrideDir.DIRECTORY_SEPARATOR.'page.blade.php',
            '<h1>Theme Override</h1>'
        );

        $this->themeManager->activate('partial-theme');

        // Non-overridden view must seamlessly resolve to the original plugin template
        $adminViewRendered = view('pages::admin.index', ['pages' => collect()])->render();
        $this->assertStringContainsString('Pages Statiques', $adminViewRendered);
    }

    public function test_deactivating_theme_immediately_restores_original_plugin_view(): void
    {
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

        $themeDir = $this->createTheme('revert-theme', [
            'id' => 'revert-theme',
            'name' => 'Revert Theme',
            'version' => '1.0.0',
        ]);

        $overrideDir = $themeDir.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'pages';
        File::makeDirectory($overrideDir, 0755, true, true);
        File::put(
            $overrideDir.DIRECTORY_SEPARATOR.'page.blade.php',
            '<h1>THEME TEMPORARY OVERRIDE</h1>'
        );

        $dummyPage = new Page([
            'title' => 'Restored Page',
            'slug' => 'restored',
            'content' => '<p>Restored Content</p>',
            'status' => 'PUBLISHED',
        ]);

        $this->themeManager->activate('revert-theme');
        $this->assertStringContainsString('THEME TEMPORARY OVERRIDE', view('pages::page', ['page' => $dummyPage])->render());

        // Deactivate theme
        $this->themeManager->deactivate();

        // View must instantly restore original plugin template
        $restoredRendered = view('pages::page', ['page' => $dummyPage])->render();
        $this->assertStringNotContainsString('THEME TEMPORARY OVERRIDE', $restoredRendered);
        $this->assertStringContainsString('<title>Restored Page</title>', $restoredRendered);
        $this->assertStringContainsString('<p>Restored Content</p>', $restoredRendered);
    }

    public function test_child_theme_overrides_parent_theme_and_cascades_properly(): void
    {
        $this->pluginManager->install('lcmscore.pages');
        $this->pluginManager->activate('lcmscore.pages');

        // 1. Parent theme overrides page.blade.php and dummy.blade.php
        $parentDir = $this->createTheme('parent-theme', [
            'id' => 'parent-theme',
            'name' => 'Parent Theme',
            'version' => '1.0.0',
        ]);
        $parentOverride = $parentDir.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'pages';
        File::makeDirectory($parentOverride, 0755, true, true);
        File::put($parentOverride.DIRECTORY_SEPARATOR.'page.blade.php', '<h1>PARENT: {{ $page->title }}</h1>');
        File::put($parentOverride.DIRECTORY_SEPARATOR.'parent-only.blade.php', '<div>Parent Only Template</div>');

        // 2. Child theme inherits parent-theme and overrides page.blade.php
        $childDir = $this->createTheme('child-theme', [
            'id' => 'child-theme',
            'name' => 'Child Theme',
            'version' => '1.0.0',
            'parent' => 'parent-theme',
        ]);
        $childOverride = $childDir.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'pages';
        File::makeDirectory($childOverride, 0755, true, true);
        File::put($childOverride.DIRECTORY_SEPARATOR.'page.blade.php', '<h1>CHILD: {{ $page->title }}</h1>');

        // 3. Activate child theme
        $this->themeManager->activate('child-theme');

        $dummyPage = new Page(['title' => 'Cascade Test', 'slug' => 'cascade', 'status' => 'PUBLISHED']);

        // Child override wins over parent
        $rendered = view('pages::page', ['page' => $dummyPage])->render();
        $this->assertSame('<h1>CHILD: Cascade Test</h1>', trim($rendered));

        // Parent template used when child does not provide it
        $parentRendered = view('pages::parent-only')->render();
        $this->assertSame('<div>Parent Only Template</div>', trim($parentRendered));

        // Base plugin template used when neither child nor parent provides it
        $adminRendered = view('pages::admin.index', ['pages' => collect()])->render();
        $this->assertStringContainsString('Pages Statiques', $adminRendered);
    }

    public function test_theme_views_accessible_via_theme_namespace_and_direct_locations(): void
    {
        $themeDir = $this->createTheme('layout-theme', [
            'id' => 'layout-theme',
            'name' => 'Layout Theme',
            'version' => '1.0.0',
        ]);

        $viewsDir = $themeDir.DIRECTORY_SEPARATOR.'views';
        File::makeDirectory($viewsDir, 0755, true, true);
        File::put($viewsDir.DIRECTORY_SEPARATOR.'hero.blade.php', '<section class="hero">Hero Section</section>');

        $this->themeManager->activate('layout-theme');

        // Accessible via namespace theme::hero
        $nsRendered = view('theme::hero')->render();
        $this->assertSame('<section class="hero">Hero Section</section>', trim($nsRendered));

        // Accessible directly as view('hero')
        $locRendered = view('hero')->render();
        $this->assertSame('<section class="hero">Hero Section</section>', trim($locRendered));
    }

    public function test_theme_settings_can_be_retrieved_and_updated(): void
    {
        $this->createTheme('settings-theme', [
            'id' => 'settings-theme',
            'name' => 'Settings Theme',
            'version' => '1.0.0',
            'settings' => [
                'primary_color' => '#3b82f6',
                'typography' => 'Inter',
            ],
        ]);

        $this->themeManager->activate('settings-theme');

        $settings = $this->themeManager->getSettings('settings-theme');
        $this->assertSame('#3b82f6', $settings['primary_color']);
        $this->assertSame('Inter', $settings['typography']);

        $updatedRecord = $this->themeManager->updateSettings('settings-theme', [
            'primary_color' => '#10b981',
            'custom_css' => 'a { text-decoration: none; }',
        ]);

        $this->assertTrue($updatedRecord->is_active);
        $freshSettings = $this->themeManager->getSettings('settings-theme');
        $this->assertSame('#10b981', $freshSettings['primary_color']);
        $this->assertSame('Inter', $freshSettings['typography']);
        $this->assertSame('a { text-decoration: none; }', $freshSettings['custom_css']);
    }

    public function test_theme_manager_boot_loads_persisted_active_theme_from_database(): void
    {
        $this->createTheme('persisted-theme', [
            'id' => 'persisted-theme',
            'name' => 'Persisted Theme',
            'version' => '1.0.0',
        ]);

        // Manually insert active theme record in database
        ThemeRecord::query()->create([
            'theme_id' => 'persisted-theme',
            'name' => 'Persisted Theme',
            'is_active' => true,
            'settings' => ['theme_mode' => 'auto'],
        ]);

        // Fresh ThemeManager instance simulating reboot
        $freshManager = new ThemeManager(
            discovery: $this->themeManager->getDiscovery(),
            viewFactory: $this->app->make(\Illuminate\View\Factory::class),
            assetManager: $this->themeManager->getAssetManager()
        );

        $this->assertFalse($freshManager->hasActiveTheme());

        $freshManager->boot();

        $this->assertTrue($freshManager->hasActiveTheme());
        $this->assertSame('persisted-theme', $freshManager->getActiveThemeId());
    }

    /**
     * Helper to scaffold a valid theme directory in temp path.
     *
     * @param  array<string, mixed>  $manifestData
     */
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
