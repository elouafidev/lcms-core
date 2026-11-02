<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('plugin_commerce_products')) {
            Schema::create('plugin_commerce_products', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('sku')->unique();
                $table->unsignedInteger('price')->comment('Prix en centimes');
                $table->integer('stock')->default(0);
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('plugin_commerce_orders')) {
            Schema::create('plugin_commerce_orders', function (Blueprint $table): void {
                $table->id();
                $table->string('order_number', 50)->unique();
                $table->string('customer_name');
                $table->string('customer_email')->index();
                $table->string('customer_phone', 50)->nullable();
                $table->text('shipping_address');
                $table->unsignedInteger('total_amount')->comment('Montant total en centimes');
                $table->string('status', 30)->default('pending')->index();
                $table->string('payment_method', 50)->default('stripe');
                $table->string('payment_id', 100)->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('plugin_commerce_order_items')) {
            Schema::create('plugin_commerce_order_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('order_id')->constrained('plugin_commerce_orders')->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('plugin_commerce_products')->nullOnDelete();
                $table->string('product_name');
                $table->unsignedInteger('price')->comment('Prix unitaire en centimes');
                $table->unsignedInteger('quantity')->default(1);
                $table->unsignedInteger('total')->comment('Total en centimes');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugin_commerce_order_items');
        Schema::dropIfExists('plugin_commerce_orders');
        Schema::dropIfExists('plugin_commerce_products');
    }
};
