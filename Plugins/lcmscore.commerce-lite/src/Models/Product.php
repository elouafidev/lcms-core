<?php

declare(strict_types=1);

namespace Plugins\CommerceLite\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $sku
 * @property int $price
 * @property int $stock
 * @property ?string $description
 * @property ?string $image
 * @property bool $is_active
 * @property ?CarbonInterface $created_at
 * @property ?CarbonInterface $updated_at
 *
 * @method static Builder<static> where(string|array<string, mixed>|\Closure $column, mixed $operator = null, mixed $value = null)
 * @method static static create(array<string, mixed> $attributes = [])
 * @method static Builder<static> latest(string $column = 'created_at')
 * @method static static|null find(mixed $id, array<string> $columns = ['*'])
 * @method static static findOrFail(mixed $id, array<string> $columns = ['*'])
 */
class Product extends Model
{
    /**
     * @var string
     */
    protected $table = 'plugin_commerce_products';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'sku',
        'price',
        'stock',
        'description',
        'image',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Format price from cents to display string with currency.
     */
    public function formattedPrice(): string
    {
        return static::formatPrice($this->price);
    }

    /**
     * Static helper to format cents amount to currency string.
     */
    public static function formatPrice(int $cents): string
    {
        return number_format($cents / 100, 2, ',', ' ').' €';
    }

    /**
     * Check if product is in stock.
     */
    public function inStock(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Decrement stock upon successful order.
     */
    public function reduceStock(int $quantity): void
    {
        $this->decrement('stock', $quantity);
    }
}
