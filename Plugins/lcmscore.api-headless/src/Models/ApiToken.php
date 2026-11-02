<?php

declare(strict_types=1);

namespace Plugins\ApiHeadless\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $token
 * @property array<string> $abilities
 * @property ?CarbonInterface $last_used_at
 * @property ?CarbonInterface $expires_at
 * @property ?CarbonInterface $created_at
 * @property ?CarbonInterface $updated_at
 *
 * @method static Builder<static> where(string|array<string, mixed>|\Closure $column, mixed $operator = null, mixed $value = null)
 * @method static Builder<static> create(array<string, mixed> $attributes = [])
 * @method static Builder<static> latest(string $column = 'created_at')
 * @method static static|null find(mixed $id, array<string> $columns = ['*'])
 * @method static static findOrFail(mixed $id, array<string> $columns = ['*'])
 */
class ApiToken extends Model
{
    /**
     * @var string
     */
    protected $table = 'plugin_api_tokens';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'last_used_at',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Check if token is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Check if token is currently valid.
     */
    public function isValid(): bool
    {
        return ! $this->isExpired();
    }

    /**
     * Check if token has specific ability.
     */
    public function can(string $ability): bool
    {
        $abilities = $this->abilities;

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    /**
     * Generate and persist a new API token.
     *
     * @param  list<string>  $abilities
     * @return array{plainTextToken: string, token: self}
     */
    public static function generate(string $name, array $abilities = ['*'], ?int $daysValid = null): array
    {
        $plainTextToken = 'lcms_'.Str::random(40);
        $hashedToken = hash('sha256', $plainTextToken);

        $expiresAt = $daysValid !== null && $daysValid > 0
            ? now()->addDays($daysValid)
            : null;

        /** @var self $token */
        $token = static::create([
            'name' => mb_substr(trim($name), 0, 100),
            'token' => $hashedToken,
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return [
            'plainTextToken' => $plainTextToken,
            'token' => $token,
        ];
    }

    /**
     * Find token record by plain text token string.
     */
    public static function findToken(string $plainTextToken): ?self
    {
        $hashed = hash('sha256', trim($plainTextToken));

        /** @var self|null $token */
        $token = static::query()->where('token', $hashed)->first();

        return $token;
    }
}
