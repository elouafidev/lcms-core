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
        Schema::table('plugin_pages', function (Blueprint $table): void {
            $table->unsignedBigInteger('parent_id')->nullable()->after('id')->index();
            $table->string('template')->default('default')->after('content');
            $table->string('featured_image')->nullable()->after('template');
            $table->timestamp('published_at')->nullable()->after('is_published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plugin_pages', function (Blueprint $table): void {
            $table->dropColumn(['parent_id', 'template', 'featured_image', 'published_at']);
        });
    }
};
