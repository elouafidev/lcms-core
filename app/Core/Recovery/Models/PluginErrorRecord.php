<?php

declare(strict_types=1);

namespace App\Core\Recovery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $plugin_id
 * @property string|null $provider
 * @property string $error_message
 * @property string|null $stack_trace
 * @property array<string, mixed>|null $context
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<PluginErrorRecord> query()
 * @method static PluginErrorRecord create(array<string, mixed> $attributes)
 */
class PluginErrorRecord extends Model
{
    protected $table = 'cms_plugin_errors';

    protected $fillable = [
        'plugin_id',
        'provider',
        'error_message',
        'stack_trace',
        'context',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'context' => 'array',
    ];
}
