<?php

declare(strict_types=1);

namespace Plugins\DemoHello\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoHelloDatabaseSeeder extends Seeder
{
    /**
     * Seed the demo hello plugin's database.
     */
    public function run(): void
    {
        if (DB::getSchemaBuilder()->hasTable('plugin_demo_messages')) {
            DB::table('plugin_demo_messages')->insertOrIgnore([
                [
                    'id' => 1,
                    'content' => 'Hello from LCMS Core Demo Plugin!',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => 2,
                    'content' => 'Welcome to the modular Laravel Plugin-First CMS!',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
