<?php

declare(strict_types=1);

use Database\Seeders\LuminaDemoSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('lcms:seed-demo', function () {
    $this->info('Seeding comprehensive demo content for Lumina Lifestyle theme...');
    $this->call(LuminaDemoSeeder::class);
    $this->info('Lumina Lifestyle demo content seeded successfully!');
})->purpose('Seed comprehensive demonstration content for plugins and themes');
