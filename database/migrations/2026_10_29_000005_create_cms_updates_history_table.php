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
        Schema::create('cms_updates_history', function (Blueprint $table): void {
            $table->id();
            $table->string('from_version', 30);
            $table->string('to_version', 30);
            $table->string('status', 30)->default('PENDING'); // PENDING, SUCCESS, FAILED, ROLLED_BACK
            $table->longText('log')->nullable();
            $table->string('backup_path')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_updates_history');
    }
};
