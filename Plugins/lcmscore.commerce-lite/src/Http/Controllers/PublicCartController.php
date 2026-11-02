<?php

declare(strict_types=1);

namespace Plugins\CommerceLite\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Plugins\CommerceLite\Models\Product;
use Plugins\CommerceLite\Services\CartService;

class PublicCartController extends Controller
{
    /**
     * Display shopping cart view.
     */
    public function index(CartService $cart): View
    {
        $items = $cart->items();
        $total = $cart->total();
        $count = $cart->count();

        return view('commerce::public.cart', compact('items', 'total', 'count'));
    }

    /**
     * Add product to shopping cart.
     */
    public function add(Request $request, CartService $cart): RedirectResponse|JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity' => 'nullable|integer|min:1',
        ]);

        /** @var Product $product */
        $product = Product::query()->where('is_active', true)->findOrFail((int) $request->input('product_id'));
        $quantity = (int) $request->input('quantity', 1);

        $cart->add($product, $quantity);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Produit ajouté au panier.',
                'cart_count' => $cart->count(),
                'cart_total' => $cart->total(),
            ]);
        }

        return back()->with('commerce_success', '« '.$product->name.' » a été ajouté à votre panier.');
    }

    /**
     * Update product quantity in cart.
     */
    public function update(Request $request, CartService $cart): RedirectResponse
    {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:0',
        ]);

        $productId = (int) $request->input('product_id');
        $quantity = (int) $request->input('quantity');

        $cart->update($productId, $quantity);

        return back()->with('commerce_success', 'Panier mis à jour.');
    }

    /**
     * Remove product from cart.
     */
    public function remove(int $id, CartService $cart): RedirectResponse
    {
        $cart->remove($id);

        return back()->with('commerce_success', 'Article retiré du panier.');
    }

    /**
     * Clear all items from cart.
     */
    public function clear(CartService $cart): RedirectResponse
    {
        $cart->clear();

        return back()->with('commerce_success', 'Votre panier a été vidé.');
    }
}
