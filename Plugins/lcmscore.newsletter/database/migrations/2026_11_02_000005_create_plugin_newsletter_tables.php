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
        if (! Schema::hasTable('plugin_newsletter_subscribers')) {
            Schema::create('plugin_newsletter_subscribers', function (Blueprint $table): void {
                $table->id();
                $table->string('email')->unique();
                $table->string('status', 20)->default('pending')->index();
                $table->string('token', 64)->unique()->index();
                $table->string('source', 50)->default('web');
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamp('unsubscribed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugin_newsletter_subscribers');
    }
};
