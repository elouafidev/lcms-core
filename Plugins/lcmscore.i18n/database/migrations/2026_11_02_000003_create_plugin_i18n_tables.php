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
        Schema::create('plugin_i18n_languages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->string('locale', 20)->default('fr_FR');
            $table->string('flag', 20)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_rtl')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('plugin_i18n_translations', function (Blueprint $table): void {
            $table->id();
            $table->string('translatable_type', 150);
            $table->unsignedBigInteger('translatable_id')->nullable();
            $table->string('locale', 10);
            $table->string('field', 100);
            $table->longText('value');
            $table->timestamps();

            $table->index(['translatable_type', 'translatable_id', 'locale']);
            $table->unique(['translatable_type', 'translatable_id', 'locale', 'field'], 'plugin_i18n_trans_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugin_i18n_translations');
        Schema::dropIfExists('plugin_i18n_languages');
    }
};
