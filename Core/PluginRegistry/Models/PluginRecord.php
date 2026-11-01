<?php

declare(strict_types=1);

namespace App\Core\PluginRegistry\Models;

use App\Core\Contracts\PluginInterface;
use App\Core\Contracts\PluginManifestInterface;
use App\Core\Lifecycle\PluginState;
use App\Core\PluginDiscovery\DTO\PluginManifest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $version
 * @property PluginState $state
 * @property bool $is_active
 * @property array<string, mixed> $manifest
 * @property string|null $error_message
 * @property string|null $path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<PluginRecord> query()
 * @method static PluginRecord|null find(string $id)
 */
class PluginRecord extends Model implements PluginInterface
{
    protected $table = 'cms_plugins';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'version',
        'state',
        'is_active',
        'manifest',
        'error_message',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'state' => PluginState::class,
        'is_active' => 'boolean',
        'manifest' => 'array',
    ];

    public function getManifest(): PluginManifestInterface
    {
        $data = $this->manifest;

        /** @var array<string, string> $author */
        $author = isset($data['author']) && is_array($data['author']) ? $data['author'] : [];

        /** @var array<string, string> $compatibility */
        $compatibility = isset($data['compatibility']) && is_array($data['compatibility']) ? $data['compatibility'] : [];

        /** @var list<string> $providers */
        $providers = isset($data['providers']) && is_array($data['providers']) ? array_values(array_filter($data['providers'], 'is_string')) : [];

        /** @var array<string, string> $dependencies */
        $dependencies = isset($data['dependencies']) && is_array($data['dependencies']) ? $data['dependencies'] : [];

        /** @var list<string> $conflicts */
        $conflicts = isset($data['conflicts']) && is_array($data['conflicts']) ? array_values(array_filter($data['conflicts'], 'is_string')) : [];

        /** @var list<array{key: string, label: string}> $permissions */
        $permissions = isset($data['permissions']) && is_array($data['permissions']) ? array_values($data['permissions']) : [];

        /** @var array{web?: ?string, api?: ?string} $routes */
        $routes = isset($data['routes']) && is_array($data['routes']) ? $data['routes'] : [];

        /** @var array{psr-4?: array<string, string>} $autoload */
        $autoload = isset($data['autoload']) && is_array($data['autoload']) ? $data['autoload'] : [];

        return new PluginManifest(
            id: (string) ($data['id'] ?? $this->id),
            name: (string) ($data['name'] ?? $this->name),
            version: (string) ($data['version'] ?? $this->version),
            description: isset($data['description']) && is_string($data['description']) ? $data['description'] : '',
            author: $author,
            compatibility: $compatibility,
            providers: $providers,
            dependencies: $dependencies,
            conflicts: $conflicts,
            permissions: $permissions,
            routes: $routes,
            autoload: $autoload,
            rawAttributes: $data
        );
    }

    public function getPath(): string
    {
        return (string) config('cms.plugins.path', base_path('platform/plugins')).'/'.$this->id;
    }

    public function getState(): string
    {
        return $this->state->value;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }
}
