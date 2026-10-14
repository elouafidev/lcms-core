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
        Schema::create('cms_plugin_migrations', function (Blueprint $table): void {
            $table->id();
            $table->string('plugin_id')->index();
            $table->string('migration');
            $table->integer('batch');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['plugin_id', 'migration']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_plugin_migrations');
    }
};
