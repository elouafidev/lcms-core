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
        if (! Schema::hasTable('plugin_analytics_pageviews')) {
            Schema::create('plugin_analytics_pageviews', function (Blueprint $table): void {
                $table->id();
                $table->string('path', 500)->index();
                $table->string('visitor_hash', 64)->index();
                $table->text('referrer')->nullable();
                $table->string('referrer_host', 255)->nullable()->index();
                $table->string('device', 20)->default('desktop')->index();
                $table->string('browser', 50)->default('Other')->index();
                $table->unsignedSmallInteger('status_code')->default(200);
                $table->timestamp('created_at')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugin_analytics_pageviews');
    }
};
