<?php

declare(strict_types=1);

namespace Plugins\CommerceLite\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Plugins\CommerceLite\Models\Product;

class AdminProductController extends Controller
{
    /**
     * Display product catalog in admin.
     */
    public function index(Request $request): View
    {
        $rawSearch = $request->query('search');
        $search = is_string($rawSearch) ? trim($rawSearch) : '';

        $query = Product::query()->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%');
            });
        }

        $totalProducts = Product::query()->count();
        $inStockCount = Product::query()->where('stock', '>', 0)->count();
        $outOfStockCount = Product::query()->where('stock', '<=', 0)->count();

        $products = $query->paginate(20)->withQueryString();

        return view('commerce::admin.products.index', compact(
            'products',
            'search',
            'totalProducts',
            'inStockCount',
            'outOfStockCount'
        ));
    }

    /**
     * Show product creation form.
     */
    public function create(): View
    {
        return view('commerce::admin.products.create');
    }

    /**
     * Store new product.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'sku' => 'required|string|max:50|unique:plugin_commerce_products,sku',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:500',
        ]);

        $rawName = $request->input('name');
        $name = is_string($rawName) ? trim($rawName) : '';

        $rawSlug = $request->input('slug');
        $slug = is_string($rawSlug) && trim($rawSlug) !== ''
            ? Str::slug($rawSlug)
            : Str::slug($name);

        // Ensure unique slug
        $originalSlug = $slug;
        $counter = 1;
        while (Product::query()->where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        $rawPrice = $request->input('price');
        $priceCents = (int) round((float) $rawPrice * 100);

        Product::create([
            'name' => $name,
            'slug' => $slug,
            'sku' => mb_strtoupper(trim((string) $request->input('sku'))),
            'price' => $priceCents,
            'stock' => (int) $request->input('stock', 0),
            'description' => $request->input('description'),
            'image' => $request->input('image'),
            'is_active' => (bool) $request->boolean('is_active', true),
        ]);

        return redirect('/admin/commerce/products')->with('success', 'Produit ajouté avec succès au catalogue.');
    }

    /**
     * Show product edit form.
     */
    public function edit(int $id): View
    {
        /** @var Product $product */
        $product = Product::query()->findOrFail($id);

        return view('commerce::admin.products.edit', compact('product'));
    }

    /**
     * Update existing product.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        /** @var Product $product */
        $product = Product::query()->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:200',
            'sku' => 'required|string|max:50|unique:plugin_commerce_products,sku,'.$product->id,
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:500',
        ]);

        $rawName = $request->input('name');
        $name = is_string($rawName) ? trim($rawName) : '';

        $rawSlug = $request->input('slug');
        $slug = is_string($rawSlug) && trim($rawSlug) !== ''
            ? Str::slug($rawSlug)
            : Str::slug($name);

        $rawPrice = $request->input('price');
        $priceCents = (int) round((float) $rawPrice * 100);

        $product->update([
            'name' => $name,
            'slug' => $slug,
            'sku' => mb_strtoupper(trim((string) $request->input('sku'))),
            'price' => $priceCents,
            'stock' => (int) $request->input('stock', 0),
            'description' => $request->input('description'),
            'image' => $request->input('image'),
            'is_active' => (bool) $request->boolean('is_active', true),
        ]);

        return redirect('/admin/commerce/products')->with('success', 'Produit mis à jour avec succès.');
    }

    /**
     * Toggle product active status.
     */
    public function toggle(int $id): RedirectResponse
    {
        /** @var Product $product */
        $product = Product::query()->findOrFail($id);
        $product->is_active = ! $product->is_active;
        $product->save();

        return redirect('/admin/commerce/products')->with('success', 'Statut du produit modifié.');
    }

    /**
     * Delete product.
     */
    public function destroy(int $id): RedirectResponse
    {
        /** @var Product $product */
        $product = Product::query()->findOrFail($id);
        $product->delete();

        return redirect('/admin/commerce/products')->with('success', 'Produit supprimé du catalogue.');
    }
}
