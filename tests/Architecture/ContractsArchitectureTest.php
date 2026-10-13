<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Core\Contracts\PluginInterface;
use App\Core\Contracts\PluginLifecycleListenerInterface;
use App\Core\Contracts\PluginManifestInterface;
use DirectoryIterator;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class ContractsArchitectureTest extends TestCase
{
    /**
     * @var list<class-string>
     */
    protected array $contracts = [
        PluginInterface::class,
        PluginManifestInterface::class,
        PluginLifecycleListenerInterface::class,
    ];

    public function test_all_contract_types_are_interfaces(): void
    {
        foreach ($this->contracts as $contract) {
            $reflection = new ReflectionClass($contract);
            $this->assertTrue(
                $reflection->isInterface(),
                "Expected {$contract} to be an interface."
            );
        }
    }

    public function test_all_contract_methods_have_strict_return_types(): void
    {
        foreach ($this->contracts as $contract) {
            $reflection = new ReflectionClass($contract);
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $this->assertTrue(
                    $method->hasReturnType(),
                    "Contract method {$contract}::{$method->getName()} must declare a strict return type."
                );
            }
        }
    }

    public function test_contracts_do_not_couple_to_concrete_core_or_plugin_classes(): void
    {
        $contractsDir = app_path('Core/Contracts');
        $this->assertDirectoryExists($contractsDir);

        $iterator = new DirectoryIterator($contractsDir);
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($fileInfo->getPathname());
            $this->assertIsString($content);

            // Verify strict types declaration
            $this->assertStringContainsString(
                'declare(strict_types=1);',
                $content,
                "File {$fileInfo->getFilename()} must declare strict_types=1."
            );

            // Verify no coupling to Plugins namespace
            $this->assertStringNotContainsString(
                'use Plugins\\',
                $content,
                "Contract {$fileInfo->getFilename()} must not import any Plugins\\ classes."
            );

            // Verify no coupling to concrete Core classes
            $this->assertDoesNotMatchRegularExpression(
                '/use App\\\\Core\\\\(?!(Contracts\\\\)).*;/',
                $content,
                "Contract {$fileInfo->getFilename()} must only reference sibling Contracts, not concrete Core implementations."
            );
        }
    }
}
