<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Themes\ThemeManager;
use Illuminate\Console\Command;
use Throwable;

class ThemeActivateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:theme:activate 
                            {id : The theme identifier to activate} 
                            {--clean-previous : Clean published assets of previously active theme}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate a CMS theme and publish its static assets';

    public function handle(ThemeManager $themeManager): int
    {
        $id = $this->argument('id');
        if (! is_string($id) || $id === '') {
            $this->error('The theme identifier argument is required.');

            return self::FAILURE;
        }

        $cleanPrevious = (bool) $this->option('clean-previous');

        try {
            $theme = $themeManager->activate($id, cleanPreviousAssets: $cleanPrevious);

            $this->info("Theme '{$theme->getId()}' ({$theme->getName()}) activated successfully.");
            $this->line("Published assets to <info>public/vendor/themes/{$theme->getId()}</info>");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Failed to activate theme '{$id}': ".$e->getMessage());

            return self::FAILURE;
        }
    }
}
