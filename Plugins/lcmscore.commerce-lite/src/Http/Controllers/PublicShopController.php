<?php

declare(strict_types=1);

namespace Plugins\CommerceLite\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Plugins\CommerceLite\Models\Product;

class PublicShopController extends Controller
{
    /**
     * Display public product catalog.
     */
    public function index(Request $request): View
    {
        $rawSearch = $request->query('search');
        $search = is_string($rawSearch) ? trim($rawSearch) : '';

        $query = Product::query()->where('is_active', true)->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        $products = $query->paginate(12)->withQueryString();

        return view('commerce::public.index', compact('products', 'search'));
    }

    /**
     * Display single product detail page.
     */
    public function show(string $slug): View
    {
        /** @var Product|null $product */
        $product = Product::query()->where('slug', $slug)->where('is_active', true)->first();

        if ($product === null) {
            abort(404, 'Produit introuvable.');
        }

        return view('commerce::public.show', compact('product'));
    }
}
