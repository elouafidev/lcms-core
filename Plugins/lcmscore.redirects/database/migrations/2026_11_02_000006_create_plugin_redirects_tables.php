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
        if (! Schema::hasTable('plugin_redirects')) {
            Schema::create('plugin_redirects', function (Blueprint $table): void {
                $table->id();
                $table->string('source_path', 500)->index();
                $table->text('target_url');
                $table->unsignedSmallInteger('status_code')->default(301);
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedInteger('hit_count')->default(0);
                $table->timestamp('last_used_at')->nullable();
                $table->string('description', 255)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('plugin_redirects_404_logs')) {
            Schema::create('plugin_redirects_404_logs', function (Blueprint $table): void {
                $table->id();
                $table->string('path', 500)->index();
                $table->unsignedInteger('hits')->default(1);
                $table->text('referrer')->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('last_hit_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugin_redirects_404_logs');
        Schema::dropIfExists('plugin_redirects');
    }
};
