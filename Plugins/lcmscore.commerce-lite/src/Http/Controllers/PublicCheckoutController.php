<?php

declare(strict_types=1);

namespace Plugins\CommerceLite\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Plugins\CommerceLite\Models\Order;
use Plugins\CommerceLite\Models\OrderItem;
use Plugins\CommerceLite\Services\CartService;

class PublicCheckoutController extends Controller
{
    /**
     * Display checkout order form.
     */
    public function index(CartService $cart): View|RedirectResponse
    {
        if ($cart->isEmpty()) {
            return redirect('/cart')->with('commerce_error', 'Votre panier est vide.');
        }

        $items = $cart->items();
        $total = $cart->total();

        return view('commerce::public.checkout', compact('items', 'total'));
    }

    /**
     * Process checkout and create confirmed order.
     */
    public function process(Request $request, CartService $cart): RedirectResponse
    {
        if ($cart->isEmpty()) {
            return redirect('/cart')->with('commerce_error', 'Votre panier est vide.');
        }

        $request->validate([
            'customer_name' => 'required|string|max:100',
            'customer_email' => 'required|email|max:150',
            'customer_phone' => 'nullable|string|max:30',
            'shipping_address' => 'required|string|max:1000',
        ]);

        $order = DB::transaction(function () use ($request, $cart): Order {
            $rawName = $request->input('customer_name');
            $customerName = is_string($rawName) ? trim($rawName) : '';

            $rawEmail = $request->input('customer_email');
            $customerEmail = is_string($rawEmail) ? mb_strtolower(trim($rawEmail)) : '';

            $rawPhone = $request->input('customer_phone');
            $customerPhone = is_string($rawPhone) && trim($rawPhone) !== '' ? trim($rawPhone) : null;

            $rawAddress = $request->input('shipping_address');
            $shippingAddress = is_string($rawAddress) ? trim($rawAddress) : '';

            /** @var Order $order */
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
                'shipping_address' => $shippingAddress,
                'total_amount' => $cart->total(),
                'status' => 'paid',
                'payment_method' => 'stripe_checkout',
                'paid_at' => now(),
            ]);

            foreach ($cart->items() as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'price' => $item['product']->price,
                    'quantity' => $item['quantity'],
                    'total' => $item['subtotal'],
                ]);

                // Reduce stock
                $item['product']->reduceStock($item['quantity']);
            }

            return $order;
        });

        // Clear cart
        $cart->clear();

        return redirect('/checkout/success/'.$order->order_number);
    }

    /**
     * Display order success confirmation page.
     */
    public function success(string $orderNumber): View
    {
        /** @var Order|null $order */
        $order = Order::query()->with('items')->where('order_number', $orderNumber)->first();

        if ($order === null) {
            abort(404, 'Commande introuvable.');
        }

        return view('commerce::public.success', compact('order'));
    }
}
