# ADR-0003 : Layout d'Administration Unifié, Gestion Détaillée et Import Sécurisé ZIP

**Statut :** Accepté
**Date :** 2026-10-19
**Auteurs :** Équipe d'Architecture LCMS Core

---

## 1. Contexte et Problématique
1. **Rupture de navigation (admin-sidebar manquante) :** Certaines vues d'administration des plugins (`acme.menus`, `acme.pages`, `acme.seo`, `acme.media-library`) utilisaient des layouts partiels ou ad hoc, faisant disparaître l'`admin-sidebar` et brisant la continuité de l'expérience d'administration.
2. **Gestion opaque des extensions :** Dans la liste `/admin/plugins`, l'administrateur n'avait accès qu'à des boutons basiques sans pouvoir inspecter les métadonnées déclarées, les dépendances directes, ni l'impact potentiel sur les extensions dépendantes (dépendances inverses).
3. **Absence d'importation ZIP standardisée :** L'installation de plugins nécessitait un accès direct en écriture sur le système de fichiers (`platform/plugins/`), augmentant la friction et les risques d'injections malveillantes (Zip Slip, exécutables non filtrés).

## 2. Décisions Retenues
1. **Layout Maître Canonique :** Mise en place de `core::admin.layouts.app` (`platform/core/resources/views/admin/layouts/app.blade.php`), incluant systématiquement `admin-sidebar`, la structure CSS partagée et les blocs d'alertes de session (`success`, `error`, `warning`). Toutes les vues d'administration du noyau et des plugins étendent obligatoirement ce layout maître via `@extends('core::admin.layouts.app')`.
2. **Page Détaillée et Garde-fous de Dépendances Inverses :**
   - Le nom de chaque extension dans `/admin/plugins` pointe vers sa vue dédiée `/admin/plugins/{id}`.
   - La vue détaille les métadonnées (nom, version, auteur, description, chemin, providers, permissions, routes, statut).
   - Un diagnostic de résolution des dépendances directes classe chaque prérequis (`satisfied`, `disabled`, `available`, `missing`).
   - Un mécanisme d'analyse des dépendances inverses (`PluginManager::getDependents()`) empêche la désactivation ou désinstallation accidentelle d'un plugin si des extensions actives en dépendent, sauf confirmation explicite (`force=true`).
3. **Pipeline Sécurisé d'Installation ZIP (`PluginZipInstaller`) :**
   - Validation stricte de l'archive (taille max 20 Mo, type MIME ZIP).
   - Détection systématique et rejet des attaques par traversée de répertoires (*Zip Slip*) sur chaque entrée d'archive.
   - Rejet de tout fichier à extension exécutable interdite (`.exe`, `.sh`, `.bat`, `.cmd`, `.phar`, `.phtml`, `.bin`, `.msi`, `.dll`, `.so`).
   - Décompression dans un bac à sable temporaire (`sys_get_temp_dir() . '/lcms_zip_' . UUID`), nettoyage automatique dans un bloc `finally`.
   - Validation stricte du fichier `plugin.json` via `ManifestValidator::validateFile()`.
   - Contrôle d'écrasement (rejet de réinstallation sans écrasement explicite).
   - Déplacement vers `platform/plugins/{id}` et redécouverte automatique via `PluginDiscovery`.

## 3. Justifications et Bénéfices
- **Cohérence visuelle et ergonomique garantie :** La barre latérale d'administration est omniprésente sur l'ensemble des modules d'administration.
- **Résilience opérationnelle :** Impossible de désactiver par inadvertance un socle comme `acme.media-library` ou un plugin requis sans être immédiatement averti de l'impact en cascade.
- **Sécurité de premier ordre :** L'importation de plugins depuis l'interface est protégée contre l'exécution arbitraire et les débordements de chemins.

## 4. Conséquences
- Toute nouvelle vue d'administration créée par un plugin doit étendre `@extends('core::admin.layouts.app')` pour respecter les conventions d'affichage.
