<?php

declare(strict_types=1);

namespace Plugins\Newsletter\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $email
 * @property string $status
 * @property string $token
 * @property string $source
 * @property ?string $ip_address
 * @property ?CarbonInterface $confirmed_at
 * @property ?CarbonInterface $unsubscribed_at
 * @property ?CarbonInterface $created_at
 * @property ?CarbonInterface $updated_at
 *
 * @method static Builder<static> where(string|array<string, mixed>|\Closure $column, mixed $operator = null, mixed $value = null)
 * @method static Builder<static> create(array<string, mixed> $attributes = [])
 * @method static Builder<static> latest(string $column = 'created_at')
 * @method static Builder<static> confirmed()
 * @method static Builder<static> pending()
 * @method static Builder<static> unsubscribed()
 * @method static static|null find(mixed $id, array<string> $columns = ['*'])
 * @method static static findOrFail(mixed $id, array<string> $columns = ['*'])
 */
class Subscriber extends Model
{
    /**
     * @var string
     */
    protected $table = 'plugin_newsletter_subscribers';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'status',
        'token',
        'source',
        'ip_address',
        'confirmed_at',
        'unsubscribed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    /**
     * Scope confirmed subscribers.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope pending confirmation subscribers.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope unsubscribed recipients.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUnsubscribed(Builder $query): Builder
    {
        return $query->where('status', 'unsubscribed');
    }

    /**
     * Check if subscriber is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if subscriber is pending confirmation.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if subscriber is unsubscribed.
     */
    public function isUnsubscribed(): bool
    {
        return $this->status === 'unsubscribed';
    }

    /**
     * Mark subscriber as confirmed.
     */
    public function confirm(): bool
    {
        $this->status = 'confirmed';
        $this->confirmed_at = now();

        return $this->save();
    }

    /**
     * Mark subscriber as unsubscribed.
     */
    public function unsubscribe(): bool
    {
        $this->status = 'unsubscribed';
        $this->unsubscribed_at = now();

        return $this->save();
    }

    /**
     * Generate a unique security token for double opt-in / unsubscribe.
     */
    public static function generateToken(): string
    {
        return hash('sha256', Str::random(40).microtime(true));
    }
}
