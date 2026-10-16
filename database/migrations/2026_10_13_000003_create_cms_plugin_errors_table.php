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
        Schema::create('cms_plugin_errors', function (Blueprint $table): void {
            $table->id();
            $table->string('plugin_id', 100)->index();
            $table->string('provider', 255)->nullable();
            $table->text('error_message');
            $table->longText('stack_trace')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_plugin_errors');
    }
};
