<?php

declare(strict_types=1);

namespace Plugins\CommerceLite\Services;

use Illuminate\Support\Facades\Session;
use Plugins\CommerceLite\Models\Product;

class CartService
{
    private const SESSION_KEY = 'lcms_commerce_cart';

    /**
     * Add product to cart.
     */
    public function add(Product $product, int $quantity = 1): void
    {
        $cart = $this->getRawCart();
        $id = $product->id;

        if (isset($cart[$id])) {
            $cart[$id] += $quantity;
        } else {
            $cart[$id] = $quantity;
        }

        Session::put(self::SESSION_KEY, $cart);
    }

    /**
     * Update quantity of a product in cart.
     */
    public function update(int $productId, int $quantity): void
    {
        $cart = $this->getRawCart();

        if ($quantity <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = $quantity;
        }

        Session::put(self::SESSION_KEY, $cart);
    }

    /**
     * Remove product from cart.
     */
    public function remove(int $productId): void
    {
        $cart = $this->getRawCart();
        unset($cart[$productId]);
        Session::put(self::SESSION_KEY, $cart);
    }

    /**
     * Clear all items from cart.
     */
    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Return list of hydrated cart items.
     *
     * @return list<array{product: Product, quantity: int, subtotal: int}>
     */
    public function items(): array
    {
        $raw = $this->getRawCart();
        if (empty($raw)) {
            return [];
        }

        $productIds = array_keys($raw);
        $products = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');

        $items = [];
        foreach ($raw as $productId => $qty) {
            /** @var Product|null $product */
            $product = $products->get($productId);
            if ($product !== null) {
                $items[] = [
                    'product' => $product,
                    'quantity' => $qty,
                    'subtotal' => $product->price * $qty,
                ];
            }
        }

        return $items;
    }

    /**
     * Calculate total cart amount in cents.
     */
    public function total(): int
    {
        $total = 0;
        foreach ($this->items() as $item) {
            $total += $item['subtotal'];
        }

        return $total;
    }

    /**
     * Return total number of items in cart.
     */
    public function count(): int
    {
        return array_sum($this->getRawCart());
    }

    /**
     * Check if cart is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->getRawCart());
    }

    /**
     * @return array<int, int>
     */
    private function getRawCart(): array
    {
        $val = Session::get(self::SESSION_KEY, []);

        return is_array($val) ? $val : [];
    }
}
