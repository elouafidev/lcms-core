<?php

declare(strict_types=1);

namespace App\Core\Themes\Exceptions;

use RuntimeException;

class InvalidThemeArchiveException extends RuntimeException
{
    public static function zipSlipDetected(string $entry): self
    {
        return new self("Chemin non sécurisé détecté dans l'archive (Zip Slip) : '{$entry}'.");
    }

    public static function forbiddenExtension(string $entry): self
    {
        return new self("Fichier potentiellement dangereux ou exécutable interdit dans l'archive : '{$entry}'.");
    }

    public static function missingManifest(): self
    {
        return new self("L'archive ZIP ne contient pas de fichier 'theme.json' valide à la racine du thème.");
    }

    public static function unreadableArchive(): self
    {
        return new self("Le fichier d'archive ZIP est introuvable ou illisible.");
    }

    public static function openFailed(int|string $code): self
    {
        return new self("Impossible d'ouvrir l'archive ZIP (Code erreur : {$code}).");
    }

    public static function alreadyExists(string $id): self
    {
        return new self("Le thème '{$id}' est déjà présent sur le système. Veuillez le supprimer avant de réimporter.");
    }

    public static function cannotOverwriteActive(string $id): self
    {
        return new self("Impossible d'écraser le thème '{$id}' car il est actuellement actif. Veuillez désactiver le thème avant de le remplacer.");
    }
}
