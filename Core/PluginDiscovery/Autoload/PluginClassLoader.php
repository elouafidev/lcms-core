<?php

declare(strict_types=1);

namespace App\Core\PluginDiscovery\Autoload;

use Composer\Autoload\ClassLoader;

class PluginClassLoader
{
    /**
     * @var array<string, list<string>>
     */
    private array $registeredPrefixes = [];

    private ?ClassLoader $composerLoader;

    public function __construct(?ClassLoader $composerLoader = null)
    {
        $this->composerLoader = $composerLoader ?? $this->findComposerLoader();
    }

    /**
     * Register a PSR-4 namespace prefix mapped to a directory path.
     */
    public function register(string $prefix, string $path): void
    {
        $normalizedPrefix = trim($prefix, '\\').'\\';
        $normalizedPath = rtrim(str_replace('\\', '/', $path), '/').'/';

        $this->registeredPrefixes[$normalizedPrefix][] = $normalizedPath;

        if ($this->composerLoader !== null) {
            $this->composerLoader->addPsr4($normalizedPrefix, $normalizedPath);
        }
    }

    /**
     * Get all prefixes registered during execution.
     *
     * @return array<string, list<string>>
     */
    public function getRegisteredPrefixes(): array
    {
        return $this->registeredPrefixes;
    }

    public function getComposerLoader(): ?ClassLoader
    {
        return $this->composerLoader;
    }

    private function findComposerLoader(): ?ClassLoader
    {
        foreach (spl_autoload_functions() as $autoloader) {
            if (is_array($autoloader) && $autoloader[0] instanceof ClassLoader) {
                return $autoloader[0];
            }
        }

        return null;
    }
}
