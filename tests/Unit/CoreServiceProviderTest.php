<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\CoreServiceProvider;
use Tests\TestCase;

class CoreServiceProviderTest extends TestCase
{
    public function test_core_service_provider_is_registered_and_loads_cms_config(): void
    {
        $this->assertTrue($this->app->providerIsLoaded(CoreServiceProvider::class));

        $cmsConfig = config('cms');
        $this->assertIsArray($cmsConfig);
        $this->assertArrayHasKey('plugins', $cmsConfig);
        $this->assertArrayHasKey('safe_mode', $cmsConfig);
        $this->assertArrayHasKey('table_prefix', $cmsConfig);

        $this->assertEquals(base_path('platform/plugins'), config('cms.plugins.path'));
        $this->assertFalse(config('cms.safe_mode.enabled'));
        $this->assertEquals('cms_', config('cms.table_prefix'));
    }

    public function test_lean_core_does_not_contain_business_domain_directories(): void
    {
        $appPath = app_path();
        $forbiddenDirectories = ['Blog', 'Shop', 'Posts', 'Pages', 'Catalog', 'Cart'];

        foreach ($forbiddenDirectories as $dir) {
            $this->assertDirectoryDoesNotExist(
                $appPath.'/'.$dir,
                "Violation of Lean Core rule: Directory {$dir} found in app/."
            );
        }
    }
}
