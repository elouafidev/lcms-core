<?php

declare(strict_types=1);

namespace App\Core\Lifecycle;

use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Seeder;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class PluginSeederManager
{
    public function __construct(
        private Container $container,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * Run database seeders for a given plugin.
     *
     * @param  string  $pluginId  The identifier of the plugin.
     * @param  string  $pluginPath  The absolute path to the plugin folder.
     * @param  string|null  $specificClass  Specific seeder class to execute (optional).
     * @param  Command|null  $command  Console command for contextual output (optional).
     * @return list<string> List of executed seeder class names.
     */
    public function seed(
        string $pluginId,
        string $pluginPath,
        ?string $specificClass = null,
        ?Command $command = null
    ): array {
        $seedersDir = rtrim(str_replace('\\', '/', $pluginPath), '/').'/database/seeders';

        // 1. If specific class requested
        if ($specificClass !== null && $specificClass !== '') {
            $class = $this->resolveSpecificSeederClass($specificClass, $seedersDir);
            $this->executeSeeder($class, $command);

            return [$class];
        }

        // 2. Discover seeders in database/seeders/
        if (! is_dir($seedersDir)) {
            return [];
        }

        $seederFiles = glob($seedersDir.'/*.php') ?: [];
        if (empty($seederFiles)) {
            return [];
        }

        sort($seederFiles);

        // Check if there is a main DatabaseSeeder.php or {StudlyName}DatabaseSeeder.php
        $studlyName = str_replace(' ', '', ucwords(str_replace(['.', '-'], ' ', $pluginId)));
        $preferredMainFiles = [
            $seedersDir.'/DatabaseSeeder.php',
            $seedersDir.'/'.$studlyName.'DatabaseSeeder.php',
            $seedersDir.'/'.$studlyName.'Seeder.php',
        ];

        $targetFile = null;
        foreach ($preferredMainFiles as $preferred) {
            if (file_exists($preferred)) {
                $targetFile = $preferred;
                break;
            }
        }

        $executed = [];

        if ($targetFile !== null) {
            $class = $this->resolveSeederClassFromFile($targetFile);
            $this->executeSeeder($class, $command);
            $executed[] = $class;
        } else {
            foreach ($seederFiles as $file) {
                $class = $this->resolveSeederClassFromFile($file);
                $this->executeSeeder($class, $command);
                $executed[] = $class;
            }
        }

        return $executed;
    }

    private function resolveSpecificSeederClass(string $specificClass, string $seedersDir): string
    {
        if (class_exists($specificClass) && is_subclass_of($specificClass, Seeder::class)) {
            return $specificClass;
        }

        $possibleFile = $seedersDir.'/'.basename(str_replace('\\', '/', $specificClass)).'.php';
        if (file_exists($possibleFile)) {
            return $this->resolveSeederClassFromFile($possibleFile);
        }

        throw new RuntimeException("Seeder class '{$specificClass}' not found or does not extend Illuminate\\Database\\Seeder.");
    }

    private function resolveSeederClassFromFile(string $filePath): string
    {
        $declaredClassesBefore = get_declared_classes();

        require_once $filePath;

        $declaredClassesAfter = get_declared_classes();
        $diff = array_diff($declaredClassesAfter, $declaredClassesBefore);

        foreach ($diff as $className) {
            if (is_subclass_of($className, Seeder::class)) {
                return $className;
            }
        }

        $parsedClass = $this->extractClassFromFile($filePath);
        if ($parsedClass !== null && class_exists($parsedClass) && is_subclass_of($parsedClass, Seeder::class)) {
            return $parsedClass;
        }

        throw new RuntimeException("Could not resolve Seeder instance from file '{$filePath}'.");
    }

    private function executeSeeder(string $seederClass, ?Command $command = null): void
    {
        /** @var Seeder $seeder */
        $seeder = $this->container->make($seederClass);
        $seeder->setContainer($this->container);

        if ($command !== null) {
            $seeder->setCommand($command);
        }

        try {
            $seeder->__invoke();
            $this->logger?->info("Plugin seeder executed successfully: {$seederClass}");
        } catch (Throwable $e) {
            $this->logger?->error("Plugin seeder {$seederClass} failed: ".$e->getMessage(), ['exception' => $e]);
            throw new RuntimeException(
                "Failed executing plugin seeder '{$seederClass}': ".$e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    private function extractClassFromFile(string $filePath): ?string
    {
        $contents = file_get_contents($filePath);
        if ($contents === false) {
            return null;
        }

        $namespace = '';
        $class = '';
        $tokens = token_get_all($contents);
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            if ($tokens[$i][0] === T_NAMESPACE) {
                $ns = '';
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($tokens[$j] === '{' || $tokens[$j] === ';') {
                        break;
                    }
                    if (is_array($tokens[$j])) {
                        $ns .= $tokens[$j][1];
                    }
                }
                $namespace = trim($ns);
            }

            if ($tokens[$i][0] === T_CLASS) {
                if ($i > 0 && is_array($tokens[$i - 1]) && $tokens[$i - 1][0] === T_PAAMAYIM_NEKUDOTAYIM) {
                    continue;
                }
                for ($j = $i + 1; $j < $count; $j++) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $class = $tokens[$j][1];
                        break;
                    }
                }
                if ($class !== '') {
                    break;
                }
            }
        }

        if ($class === '') {
            return null;
        }

        return $namespace !== '' ? $namespace.'\\'.$class : $class;
    }
}
