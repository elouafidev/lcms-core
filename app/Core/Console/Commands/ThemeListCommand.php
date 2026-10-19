<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Themes\ThemeDiscovery;
use App\Core\Themes\ThemeManager;
use Illuminate\Console\Command;

class ThemeListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:theme:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all discovered CMS themes and their active status';

    public function handle(ThemeDiscovery $discovery, ThemeManager $themeManager): int
    {
        $discovery->discover();
        $themes = $discovery->all();
        $activeThemeId = $themeManager->getActiveThemeId();

        if (empty($themes)) {
            $this->info('No themes discovered.');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($themes as $theme) {
            $manifest = $theme->getManifest();
            $isActive = $theme->getId() === $activeThemeId;
            $status = $isActive ? '<info>Actif</info>' : 'Disponible';
            $author = $manifest->getAuthor()['name'];

            $rows[] = [
                $theme->getId(),
                $theme->getName(),
                $manifest->getVersion(),
                $status,
                $author,
                $theme->getPath(),
            ];
        }

        $this->table(['ID', 'Nom', 'Version', 'Statut', 'Auteur', 'Chemin'], $rows);

        return self::SUCCESS;
    }
}
