<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plugin_demo_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('content');
            $table->timestamps();
        });

        // Insert initial reference seed message
        DB::table('plugin_demo_messages')->insert([
            'content' => 'Hello from ACME Demo Plugin!',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugin_demo_messages');
    }
};
