<?php

declare(strict_types=1);

namespace App\Core\Settings\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * @property int $id
 * @property string $key
 * @property ?string $value
 * @property string $type
 * @property string $group
 * @property bool $is_encrypted
 * @property ?CarbonInterface $created_at
 * @property ?CarbonInterface $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> where(string|array<string, mixed>|\Closure $column, mixed $operator = null, mixed $value = null)
 * @method static static firstOrNew(array<string, mixed> $attributes = [], array<string, mixed> $values = [])
 */
class SettingRecord extends Model
{
    /**
     * @var string
     */
    protected $table = 'cms_settings';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'is_encrypted',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }

    /**
     * Get the typed and possibly decrypted value.
     */
    public function getCastValue(): mixed
    {
        $raw = $this->value;

        if ($raw === null) {
            return null;
        }

        if ($this->is_encrypted) {
            try {
                $raw = Crypt::decryptString($raw);
            } catch (Throwable) {
                return null;
            }
        }

        return match ($this->type) {
            'boolean', 'bool' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'integer', 'int' => (int) $raw,
            'float' => (float) $raw,
            'json', 'array' => json_decode($raw, true),
            default => $raw,
        };
    }

    /**
     * Set a typed and possibly encrypted value.
     */
    public function setCastValue(mixed $value, string $type = 'string', bool $encrypt = false): void
    {
        $this->type = $type;
        $this->is_encrypted = $encrypt;

        if ($value === null) {
            $this->value = null;

            return;
        }

        $serialized = match ($type) {
            'boolean', 'bool' => $value ? '1' : '0',
            'json', 'array' => is_string($value) ? $value : (string) json_encode($value),
            default => (string) $value,
        };

        if ($encrypt) {
            $this->value = Crypt::encryptString($serialized);
        } else {
            $this->value = $serialized;
        }
    }
}
