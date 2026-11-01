<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use Core\Settings\Services\StorageCacheService;
use Tests\TestCase;

class StorageCacheServiceTest extends TestCase
{
    private StorageCacheService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StorageCacheService;
    }

    public function test_get_storage_status_returns_expected_structure(): void
    {
        $status = $this->service->getStorageStatus();

        $this->assertArrayHasKey('link_exists', $status);
        $this->assertArrayHasKey('is_linked', $status);
        $this->assertArrayHasKey('public_storage_path', $status);
        $this->assertArrayHasKey('target_storage_path', $status);
        $this->assertArrayHasKey('storage_writable', $status);
        $this->assertArrayHasKey('free_space_human', $status);
        $this->assertIsBool($status['link_exists']);
        $this->assertIsBool($status['is_linked']);
        $this->assertIsBool($status['storage_writable']);
        $this->assertIsString($status['free_space_human']);
    }

    public function test_link_storage_creates_public_link(): void
    {
        $result = $this->service->linkStorage(true);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['message']);

        // Calling without force when already linked returns success
        $repeatResult = $this->service->linkStorage(false);
        $this->assertTrue($repeatResult['success']);
        $this->assertStringContainsString('déjà', $repeatResult['message']);
    }

    public function test_get_cache_status_returns_expected_structure(): void
    {
        $cacheStatus = $this->service->getCacheStatus();

        $this->assertArrayHasKey('driver', $cacheStatus);
        $this->assertArrayHasKey('views_count', $cacheStatus);
        $this->assertArrayHasKey('is_routes_cached', $cacheStatus);
        $this->assertArrayHasKey('is_config_cached', $cacheStatus);
        $this->assertArrayHasKey('is_events_cached', $cacheStatus);
        $this->assertIsString($cacheStatus['driver']);
        $this->assertIsInt($cacheStatus['views_count']);
        $this->assertIsBool($cacheStatus['is_routes_cached']);
        $this->assertIsBool($cacheStatus['is_config_cached']);
        $this->assertIsBool($cacheStatus['is_events_cached']);
    }

    public function test_clear_cache_all_clears_application_caches(): void
    {
        $result = $this->service->clearCache('all');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('succès', $result['message']);
    }

    public function test_clear_cache_specific_types(): void
    {
        foreach (['cache', 'views', 'routes', 'config'] as $type) {
            $result = $this->service->clearCache($type);
            $this->assertTrue($result['success'], "Failed clearing {$type}");
            $this->assertStringContainsString('succès', $result['message']);
        }
    }

    public function test_clear_cache_unknown_type_fails(): void
    {
        $result = $this->service->clearCache('non_existent_type');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('non reconnu', $result['message']);
    }
}
