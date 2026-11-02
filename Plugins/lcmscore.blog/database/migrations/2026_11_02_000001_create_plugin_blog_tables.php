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
        Schema::create('plugin_blog_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color', 20)->default('#0d6efd');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('plugin_blog_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('plugin_blog_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('plugin_blog_categories')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('featured_image')->nullable();
            $table->integer('reading_time')->default(1);
            $table->string('status', 20)->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
        });

        Schema::create('plugin_blog_post_tag', function (Blueprint $table): void {
            $table->foreignId('post_id')->constrained('plugin_blog_posts')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('plugin_blog_tags')->cascadeOnDelete();
            $table->primary(['post_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugin_blog_post_tag');
        Schema::dropIfExists('plugin_blog_posts');
        Schema::dropIfExists('plugin_blog_tags');
        Schema::dropIfExists('plugin_blog_categories');
    }
};
