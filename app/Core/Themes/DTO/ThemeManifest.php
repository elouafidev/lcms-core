<?php

declare(strict_types=1);

namespace App\Core\Themes\DTO;

use App\Core\Contracts\ThemeManifestInterface;
use InvalidArgumentException;

class ThemeManifest implements ThemeManifestInterface
{
    /**
     * @param  array{name: string, email?: string, url?: string}  $author
     * @param  array<string, string>  $assets
     * @param  array<string, mixed>  $settings
     */
    public function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly string $version,
        private readonly string $description = '',
        private readonly array $author = ['name' => 'Unknown'],
        private readonly ?string $parent = null,
        private readonly array $assets = [],
        private readonly array $settings = []
    ) {
        $this->validateId($this->id);

        if ($this->parent !== null) {
            $this->validateId($this->parent);
        }

        if (trim($this->name) === '') {
            throw new InvalidArgumentException('Theme name cannot be empty.');
        }

        if (trim($this->version) === '') {
            throw new InvalidArgumentException('Theme version cannot be empty.');
        }
    }

    /**
     * Factory from associative array with schema validation.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['id']) || ! is_string($data['id'])) {
            throw new InvalidArgumentException("Theme manifest must contain a valid string 'id'.");
        }

        if (! isset($data['name']) || ! is_string($data['name'])) {
            throw new InvalidArgumentException("Theme manifest must contain a valid string 'name'.");
        }

        if (! isset($data['version']) || ! is_string($data['version'])) {
            throw new InvalidArgumentException("Theme manifest must contain a valid string 'version'.");
        }

        $description = isset($data['description']) && is_string($data['description']) ? $data['description'] : '';

        $author = ['name' => 'Unknown'];
        if (isset($data['author'])) {
            if (is_array($data['author'])) {
                /** @var array<string, mixed> $authorData */
                $authorData = $data['author'];
                $author = [
                    'name' => isset($authorData['name']) && is_string($authorData['name']) ? $authorData['name'] : 'Unknown',
                    'email' => isset($authorData['email']) && is_string($authorData['email']) ? $authorData['email'] : null,
                    'url' => isset($authorData['url']) && is_string($authorData['url']) ? $authorData['url'] : null,
                ];
                $author = array_filter($author, fn ($v): bool => $v !== null);
                /** @var array{name: string, email?: string, url?: string} $author */
            } elseif (is_string($data['author'])) {
                $author = ['name' => $data['author']];
            }
        }

        $parent = isset($data['parent']) && is_string($data['parent']) && trim($data['parent']) !== '' ? trim($data['parent']) : null;

        $assets = [];
        if (isset($data['assets']) && is_array($data['assets'])) {
            foreach ($data['assets'] as $k => $v) {
                if (is_string($k) && is_string($v)) {
                    $assets[$k] = $v;
                }
            }
        }

        $settings = [];
        if (isset($data['settings']) && is_array($data['settings'])) {
            /** @var array<string, mixed> $settings */
            $settings = $data['settings'];
        }

        return new self(
            id: trim($data['id']),
            name: trim($data['name']),
            version: trim($data['version']),
            description: $description,
            author: $author,
            parent: $parent,
            assets: $assets,
            settings: $settings
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getAuthor(): array
    {
        return $this->author;
    }

    public function getParentTheme(): ?string
    {
        return $this->parent;
    }

    public function getAssets(): array
    {
        return $this->assets;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Validate theme identifier against directory traversal and invalid characters.
     */
    private function validateId(string $id): void
    {
        if (str_contains($id, '..') || str_contains($id, '/') || str_contains($id, '\\')) {
            throw new InvalidArgumentException("Theme identifier '{$id}' contains illegal path traversal characters.");
        }

        if (! preg_match('/^[a-z0-9][a-z0-9\._-]{0,63}$/i', $id)) {
            throw new InvalidArgumentException("Theme identifier '{$id}' is invalid. Allowed: alphanumeric, '.', '_', '-'.");
        }
    }
}
