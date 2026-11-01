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
        Schema::create('plugin_forms', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('fields');
            $table->string('submit_label')->default('Envoyer');
            $table->text('success_message')->nullable();
            $table->string('recipient_email')->nullable();
            $table->boolean('send_notification')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('honeypot_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('plugin_form_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('form_id')->constrained('plugin_forms')->onDelete('cascade');
            $table->json('data');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugin_form_submissions');
        Schema::dropIfExists('plugin_forms');
    }
};
