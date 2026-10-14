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
        Schema::create('cms_plugins', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('version', 32);
            $table->string('state', 20)->default('INSTALLED')->index();
            $table->boolean('is_active')->default(false)->index();
            $table->json('manifest');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_plugins');
    }
};
