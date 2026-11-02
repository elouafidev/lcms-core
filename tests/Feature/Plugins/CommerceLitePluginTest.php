<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use Core\Admin\NavigationRegistry;
use Core\Lifecycle\PluginState;
use Core\Models\User;
use Core\Permissions\PermissionRegistry;
use Core\PluginManager\PluginManager;
use Core\PluginRegistry\PluginRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Plugins\CommerceLite\Models\Order;
use Plugins\CommerceLite\Models\Product;
use Plugins\CommerceLite\Services\CartService;
use Tests\TestCase;

class CommerceLitePluginTest extends TestCase
{
    use RefreshDatabase;

    private PluginManager $pluginManager;

    private PluginRegistry $pluginRegistry;

    private NavigationRegistry $navigationRegistry;

    private PermissionRegistry $permissionRegistry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginManager = $this->app->make(PluginManager::class);
        $this->pluginRegistry = $this->app->make(PluginRegistry::class);
        $this->navigationRegistry = $this->app->make(NavigationRegistry::class);
        $this->permissionRegistry = $this->app->make(PermissionRegistry::class);

        config(['cms.plugins.path' => base_path('Plugins')]);
    }

    public function test_plugin_is_discoverable_and_inactive_initially(): void
    {
        $discovered = $this->pluginRegistry->getDiscovered('lcmscore.commerce-lite');

        $this->assertNotNull($discovered);
        $this->assertSame(PluginState::AVAILABLE->value, $discovered->getState());
        $this->assertFalse(Schema::hasTable('plugin_commerce_products'));
        $this->assertFalse(Schema::hasTable('plugin_commerce_orders'));
        $this->assertFalse(Schema::hasTable('plugin_commerce_order_items'));
        $this->assertFalse($this->navigationRegistry->has('commerce-management'));

        // Public shop route should return 404 before activation
        $this->get('/shop')->assertNotFound();
    }

    public function test_install_and_activation_creates_tables_and_navigation(): void
    {
        $this->pluginManager->install('lcmscore.commerce-lite');
        $this->pluginManager->activate('lcmscore.commerce-lite');

        $this->assertTrue(Schema::hasTable('plugin_commerce_products'));
        $this->assertTrue(Schema::hasTable('plugin_commerce_orders'));
        $this->assertTrue(Schema::hasTable('plugin_commerce_order_items'));

        // Navigation check
        $this->assertTrue($this->navigationRegistry->has('commerce-management'));
        $item = $this->navigationRegistry->get('commerce-management');
        $this->assertNotNull($item);
        $this->assertSame('Boutique & Commandes', $item->getLabel());
        $this->assertSame('/admin/commerce/orders', $item->getUrl());

        // Permissions check
        $this->assertTrue($this->permissionRegistry->has('commerce.manage'));
    }

    public function test_public_shop_catalogue_and_product_details(): void
    {
        $this->pluginManager->install('lcmscore.commerce-lite');
        $this->pluginManager->activate('lcmscore.commerce-lite');

        $product1 = Product::create([
            'name' => 'T-shirt Éco-Responsable',
            'slug' => 't-shirt-eco-responsable',
            'sku' => 'TSH-ECO-01',
            'price' => 2990, // 29.90 €
            'stock' => 15,
            'description' => 'T-shirt de qualité premium en coton recyclé.',
            'is_active' => true,
        ]);

        $product2 = Product::create([
            'name' => 'Produit Désactivé',
            'slug' => 'produit-desactive',
            'sku' => 'INACT-01',
            'price' => 990,
            'stock' => 5,
            'is_active' => false,
        ]);

        // Catalog page
        $response = $this->get('/shop');
        $response->assertOk();
        $response->assertSee('T-shirt Éco-Responsable');
        $response->assertSee('29,90 €');
        $response->assertDontSee('Produit Désactivé');

        // Product detail page
        $detailResponse = $this->get('/shop/t-shirt-eco-responsable');
        $detailResponse->assertOk();
        $detailResponse->assertSee('T-shirt Éco-Responsable');
        $detailResponse->assertSee('TSH-ECO-01');
        $detailResponse->assertSee('29,90 €');
        $detailResponse->assertSee('Ajouter au panier');

        // Inactive product should return 404
        $this->get('/shop/produit-desactive')->assertNotFound();
    }

    public function test_cart_service_and_public_cart_actions(): void
    {
        $this->pluginManager->install('lcmscore.commerce-lite');
        $this->pluginManager->activate('lcmscore.commerce-lite');

        $product = Product::create([
            'name' => 'Casquette Minimaliste',
            'slug' => 'casquette-minimaliste',
            'sku' => 'CAP-MIN-01',
            'price' => 1950, // 19.50 €
            'stock' => 10,
            'is_active' => true,
        ]);

        // 1. Add to cart via HTTP (from /shop)
        $addResponse = $this->from('/shop')->post('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $addResponse->assertRedirect('/shop');
        $addResponse->assertSessionHas('commerce_success');

        // 2. View cart page
        $cartView = $this->get('/cart');
        $cartView->assertOk();
        $cartView->assertSee('Casquette Minimaliste');
        $cartView->assertSee('39,00 €'); // 2 * 19.50

        // 3. Update cart quantity
        $updateResponse = $this->from('/cart')->post('/cart/update', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
        $updateResponse->assertRedirect('/cart');

        $cartService = $this->app->make(CartService::class);
        $this->assertSame(3, $cartService->count());
        $this->assertSame(5850, $cartService->total()); // 3 * 19.50 = 58.50 €

        // 4. Remove item
        $removeResponse = $this->from('/cart')->post('/cart/remove/'.$product->id);
        $removeResponse->assertRedirect('/cart');
        $this->assertSame(0, $cartService->count());

        // 5. Test CartService directly (add multiple and clear)
        $cartService->add($product, 4);
        $this->assertSame(4, $cartService->count());
        $cartService->clear();
        $this->assertSame(0, $cartService->count());
        $this->assertEmpty($cartService->items());
    }

    public function test_checkout_flow_and_order_creation(): void
    {
        $this->pluginManager->install('lcmscore.commerce-lite');
        $this->pluginManager->activate('lcmscore.commerce-lite');

        $product = Product::create([
            'name' => 'Mug Céramique LCMS',
            'slug' => 'mug-ceramique-lcms',
            'sku' => 'MUG-LCMS-01',
            'price' => 1500, // 15.00 €
            'stock' => 20,
            'is_active' => true,
        ]);

        // Populate cart
        $cartService = $this->app->make(CartService::class);
        $cartService->add($product, 2);

        // Checkout page
        $checkoutView = $this->get('/checkout');
        $checkoutView->assertOk();
        $checkoutView->assertSee('Mug Céramique LCMS');
        $checkoutView->assertSee('30,00 €');

        // Process checkout
        $postResponse = $this->post('/checkout', [
            'customer_name' => 'Alice Martin',
            'customer_email' => 'alice@example.com',
            'customer_phone' => '0601020304',
            'shipping_address' => '12 rue de la Paix, 75001 Paris, France',
            'payment_method' => 'card',
        ]);

        /** @var Order|null $order */
        $order = Order::query()->where('customer_email', 'alice@example.com')->first();
        $this->assertNotNull($order);
        $this->assertSame('Alice Martin', $order->customer_name);
        $this->assertSame(3000, $order->total_amount);
        $this->assertSame('paid', $order->status);
        $this->assertSame('stripe_checkout', $order->payment_method);
        $this->assertCount(1, $order->items);

        $orderItem = $order->items->first();
        $this->assertNotNull($orderItem);
        $this->assertSame($product->id, $orderItem->product_id);
        $this->assertSame('Mug Céramique LCMS', $orderItem->product_name);
        $this->assertSame(2, $orderItem->quantity);
        $this->assertSame(1500, $orderItem->price);
        $this->assertSame(3000, $orderItem->total);

        // Verify stock was reduced from 20 to 18
        $freshProduct = $product->fresh();
        $this->assertNotNull($freshProduct);
        $this->assertSame(18, $freshProduct->stock);

        // Verify cart was cleared
        $this->assertSame(0, $cartService->count());

        // Redirected to success page
        $postResponse->assertRedirect('/checkout/success/'.$order->order_number);

        $successPage = $this->get('/checkout/success/'.$order->order_number);
        $successPage->assertOk();
        $successPage->assertSee('Merci pour votre commande !');
        $successPage->assertSee($order->order_number);
        $successPage->assertSee('30,00 €');
    }

    public function test_checkout_fails_if_cart_is_empty(): void
    {
        $this->pluginManager->install('lcmscore.commerce-lite');
        $this->pluginManager->activate('lcmscore.commerce-lite');

        $response = $this->post('/checkout', [
            'customer_name' => 'Alice Martin',
            'customer_email' => 'alice@example.com',
            'shipping_address' => '12 rue de la Paix, 75001 Paris, France',
            'payment_method' => 'card',
        ]);

        $response->assertRedirect('/cart');
        $response->assertSessionHas('commerce_error');
        $this->assertDatabaseCount('plugin_commerce_orders', 0);
    }

    public function test_admin_orders_management_and_status_updates(): void
    {
        $this->pluginManager->install('lcmscore.commerce-lite');
        $this->pluginManager->activate('lcmscore.commerce-lite');

        /** @var User $admin */
        $admin = User::factory()->create();

        /** @var Product $product */
        $product = Product::create([
            'name' => 'Sac Cabas',
            'slug' => 'sac-cabas',
            'sku' => 'BAG-01',
            'price' => 2500,
            'stock' => 10,
            'is_active' => true,
        ]);

        /** @var Order $order */
        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'customer_name' => 'Marc Durand',
            'customer_email' => 'marc@example.com',
            'shipping_address' => '5 boulevard Saint-Germain, 75005 Paris, France',
            'payment_method' => 'card',
            'status' => 'pending',
            'total_amount' => 5000,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 2,
            'price' => 2500,
            'total' => 5000,
        ]);

        // Unauthenticated access redirects to login
        $this->get('/admin/commerce/orders')->assertRedirect('/login');

        // Authenticated admin view
        $response = $this->actingAs($admin)->get('/admin/commerce/orders');
        $response->assertOk();
        $response->assertSee('Marc Durand');
        $response->assertSee('50,00 €');

        // Detail view
        $detailResponse = $this->actingAs($admin)->get('/admin/commerce/orders/'.$order->id);
        $detailResponse->assertOk();
        $detailResponse->assertSee($order->order_number);
        $detailResponse->assertSee('marc@example.com');
        $detailResponse->assertSee('Sac Cabas');

        // Update status to 'paid'
        $statusResponse = $this->actingAs($admin)->post('/admin/commerce/orders/'.$order->id.'/status', [
            'status' => 'paid',
        ]);
        $statusResponse->assertRedirect('/admin/commerce/orders/'.$order->id);
        $freshOrder = $order->fresh();
        $this->assertNotNull($freshOrder);
        $this->assertSame('paid', $freshOrder->status);
        $this->assertNotNull($freshOrder->paid_at);

        // Delete order
        $deleteResponse = $this->actingAs($admin)->delete('/admin/commerce/orders/'.$order->id);
        $deleteResponse->assertRedirect('/admin/commerce/orders');
        $this->assertDatabaseMissing('plugin_commerce_orders', ['id' => $order->id]);
        $this->assertDatabaseCount('plugin_commerce_order_items', 0);
    }

    public function test_admin_product_crud_and_status_toggle(): void
    {
        $this->pluginManager->install('lcmscore.commerce-lite');
        $this->pluginManager->activate('lcmscore.commerce-lite');

        /** @var User $admin */
        $admin = User::factory()->create();

        // 1. Create product
        $createResponse = $this->actingAs($admin)->post('/admin/commerce/products', [
            'name' => 'Stylo Bille Bambou',
            'sku' => 'PEN-BAMBOO-01',
            'slug' => 'stylo-bille-bambou',
            'price' => '4.50',
            'stock' => 100,
            'description' => 'Stylo écologique rechargeable',
            'is_active' => '1',
        ]);
        $createResponse->assertRedirect('/admin/commerce/products');

        /** @var Product|null $product */
        $product = Product::query()->where('sku', 'PEN-BAMBOO-01')->first();
        $this->assertNotNull($product);
        $this->assertSame('Stylo Bille Bambou', $product->name);
        $this->assertSame(450, $product->price);
        $this->assertSame(100, $product->stock);
        $this->assertTrue($product->is_active);

        // 2. Edit product
        $editResponse = $this->actingAs($admin)->get('/admin/commerce/products/'.$product->id.'/edit');
        $editResponse->assertOk();
        $editResponse->assertSee('Stylo Bille Bambou');

        $updateResponse = $this->actingAs($admin)->put('/admin/commerce/products/'.$product->id, [
            'name' => 'Stylo Bille Bambou Pro',
            'sku' => 'PEN-BAMBOO-01',
            'slug' => 'stylo-bille-bambou-pro',
            'price' => '5.90',
            'stock' => 80,
            'description' => 'Version améliorée',
            'is_active' => '1',
        ]);
        $updateResponse->assertRedirect('/admin/commerce/products');
        $freshProduct = $product->fresh();
        $this->assertNotNull($freshProduct);
        $this->assertSame('Stylo Bille Bambou Pro', $freshProduct->name);
        $this->assertSame(590, $freshProduct->price);
        $this->assertSame(80, $freshProduct->stock);

        // 3. Toggle product status
        $toggleResponse = $this->actingAs($admin)->post('/admin/commerce/products/'.$product->id.'/toggle');
        $toggleResponse->assertRedirect('/admin/commerce/products');
        $freshProductToggled = $product->fresh();
        $this->assertNotNull($freshProductToggled);
        $this->assertFalse($freshProductToggled->is_active);

        // 4. Delete product
        $deleteResponse = $this->actingAs($admin)->delete('/admin/commerce/products/'.$product->id);
        $deleteResponse->assertRedirect('/admin/commerce/products');
        $this->assertDatabaseMissing('plugin_commerce_products', ['id' => $product->id]);
    }

    public function test_blade_directives(): void
    {
        $this->pluginManager->install('lcmscore.commerce-lite');
        $this->pluginManager->activate('lcmscore.commerce-lite');

        // Test @price directive
        $renderedPrice = Blade::render('@price(4999)');
        $this->assertSame('49,99 €', trim($renderedPrice));

        // Test @cartButton directive
        $renderedButton = Blade::render('@cartButton');
        $this->assertStringContainsString('Panier', $renderedButton);
        $this->assertStringContainsString('/cart', $renderedButton);
    }

    public function test_deactivation_and_uninstall_with_purge(): void
    {
        $this->pluginManager->install('lcmscore.commerce-lite');
        $this->pluginManager->activate('lcmscore.commerce-lite');

        $this->assertTrue(Schema::hasTable('plugin_commerce_products'));

        // Deactivate
        $this->pluginManager->deactivate('lcmscore.commerce-lite');
        $this->assertFalse($this->navigationRegistry->has('commerce-management'));

        // Uninstall with purge
        $this->pluginManager->uninstall('lcmscore.commerce-lite', true);
        $this->assertFalse(Schema::hasTable('plugin_commerce_products'));
        $this->assertFalse(Schema::hasTable('plugin_commerce_orders'));
        $this->assertFalse(Schema::hasTable('plugin_commerce_order_items'));
    }
}
