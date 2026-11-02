<?php

declare(strict_types=1);

namespace Plugins\CommerceLite\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Plugins\CommerceLite\Models\Order;

class AdminOrderController extends Controller
{
    /**
     * Display orders list and commerce KPIs.
     */
    public function index(Request $request): View
    {
        $rawStatus = $request->query('status');
        $status = is_string($rawStatus) && in_array($rawStatus, ['all', 'paid', 'pending', 'shipped', 'cancelled'], true)
            ? $rawStatus
            : 'all';

        $rawSearch = $request->query('search');
        $search = is_string($rawSearch) ? trim($rawSearch) : '';

        // KPIs
        $totalOrders = Order::query()->count();
        $paidOrdersCount = Order::query()->where('status', 'paid')->count();
        $totalRevenue = (int) Order::query()->whereIn('status', ['paid', 'shipped'])->sum('total_amount');
        $averageBasket = $paidOrdersCount > 0 ? (int) round($totalRevenue / $paidOrdersCount) : 0;

        $query = Order::query()->latest('id');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('order_number', 'like', '%'.$search.'%')
                    ->orWhere('customer_name', 'like', '%'.$search.'%')
                    ->orWhere('customer_email', 'like', '%'.$search.'%');
            });
        }

        $orders = $query->paginate(20)->withQueryString();

        return view('commerce::admin.orders.index', compact(
            'orders',
            'status',
            'search',
            'totalOrders',
            'paidOrdersCount',
            'totalRevenue',
            'averageBasket'
        ));
    }

    /**
     * Display order detail page.
     */
    public function show(int $id): View
    {
        /** @var Order $order */
        $order = Order::query()->with('items.product')->findOrFail($id);

        return view('commerce::admin.orders.show', compact('order'));
    }

    /**
     * Update order status.
     */
    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        /** @var Order $order */
        $order = Order::query()->findOrFail($id);

        $request->validate([
            'status' => 'required|in:pending,paid,shipped,cancelled',
        ]);

        $newStatus = (string) $request->input('status');
        $order->status = $newStatus;

        if ($newStatus === 'paid' && $order->paid_at === null) {
            $order->paid_at = now();
        }

        $order->save();

        return redirect('/admin/commerce/orders/'.$order->id)->with('success', 'Statut de la commande mis à jour.');
    }

    /**
     * Delete order.
     */
    public function destroy(int $id): RedirectResponse
    {
        /** @var Order $order */
        $order = Order::query()->findOrFail($id);
        $order->delete();

        return redirect('/admin/commerce/orders')->with('success', 'Commande supprimée.');
    }
}
