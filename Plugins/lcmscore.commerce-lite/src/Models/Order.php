<?php

declare(strict_types=1);

namespace Plugins\CommerceLite\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $order_number
 * @property string $customer_name
 * @property string $customer_email
 * @property ?string $customer_phone
 * @property string $shipping_address
 * @property int $total_amount
 * @property string $status
 * @property string $payment_method
 * @property ?string $payment_id
 * @property ?CarbonInterface $paid_at
 * @property ?CarbonInterface $created_at
 * @property ?CarbonInterface $updated_at
 * @property Collection<int, OrderItem> $items
 *
 * @method static Builder<static> where(string|array<string, mixed>|\Closure $column, mixed $operator = null, mixed $value = null)
 * @method static static create(array<string, mixed> $attributes = [])
 * @method static Builder<static> latest(string $column = 'created_at')
 * @method static static|null find(mixed $id, array<string> $columns = ['*'])
 * @method static static findOrFail(mixed $id, array<string> $columns = ['*'])
 */
class Order extends Model
{
    /**
     * @var string
     */
    protected $table = 'plugin_commerce_orders';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'total_amount',
        'status',
        'payment_method',
        'payment_id',
        'paid_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Format total order amount to currency string.
     */
    public function formattedTotal(): string
    {
        return Product::formatPrice($this->total_amount);
    }

    /**
     * Check if order has been paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid' || $this->paid_at !== null;
    }

    /**
     * Mark order as paid.
     */
    public function markAsPaid(?string $paymentId = null): void
    {
        $this->update([
            'status' => 'paid',
            'payment_id' => $paymentId ?? $this->payment_id,
            'paid_at' => now(),
        ]);
    }

    /**
     * Generate unique friendly order identifier.
     */
    public static function generateOrderNumber(): string
    {
        return 'CMD-'.now()->format('Ym').'-'.str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
    }
}
