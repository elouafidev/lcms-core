# Plan de Travail Opérationnel — Laravel Plugin-First CMS

**Version :** 1.0.0
**Date de révision :** 2026-10-12
**Statut :** Plan Directeur

---

## 1. Tableau de Suivi Global des Tâches

| ID | Intitulé de la Tâche | Phase | Dépendances | État |
|---|---|---|---|---|
| **P0-T1** | Inspection de l'environnement et de l'arborescence | Phase 0 | Aucune | **Terminé** |
| **P0-T2** | Rédaction des instructions permanentes d'ingénierie (`AGENTS.md`) | Phase 0 | P0-T1 | **Terminé** |
| **P0-T3** | Rédaction du guide de référence et documentation générale (`README.md`) | Phase 0 | P0-T2 | **Terminé** |
| **P0-T4** | Formalisation de l'état du projet (`docs/CONTEXT.md`) | Phase 0 | P0-T3 | **Terminé** |
| **P0-T5** | Spécification de l'architecture cible (`docs/ARCHITECTURE.md`) | Phase 0 | P0-T4 | **Terminé** |
| **P0-T6** | Spécification du système de plugins et manifestes (`docs/PLUGIN_SYSTEM.md`) | Phase 0 | P0-T5 | **Terminé** |
| **P0-T7** | Formalisation de la sécurité et modèle de menaces STRIDE (`SECURITY.md`, `THREAT_MODEL.md`) | Phase 0 | P0-T6 | **Terminé** |
| **P0-T8** | Définition de la stratégie de tests automatisés (`docs/TESTING.md`) | Phase 0 | P0-T7 | **Terminé** |
| **P0-T9** | Élaboration de la feuille de route et du plan (`docs/ROADMAP.md`, `docs/PLAN.md`) | Phase 0 | P0-T8 | **Terminé** |
| **P0-T10** | Rédaction des premiers ADR (ADR-0001, ADR-0002) | Phase 0 | P0-T9 | **Terminé** |
| **P0-T11** | Consolidation et rapport de validation de la Phase 0 | Phase 0 | P0-T10 | **Terminé** |
|---|---|---|---|---|
| **P1-T1** | Initialisation de la structure du noyau (`app/Core/`) et configuration | Phase 1 (US-001) | P0-T11 | **Terminé** |
| **P1-T2** | Implémentation des contrats publics (`app/Core/Contracts/`) | Phase 1 (US-002) | P1-T1 | **Terminé** |
| **P1-T3** | Validateur de manifeste JSON (`ManifestValidator`) et schéma | Phase 1 (US-003) | P1-T2 | **Terminé** |
| **P1-T4** | Découvreur de plugins locaux (`PluginDiscovery`) | Phase 1 (US-004) | P1-T3 | **Terminé** |
| **P1-T5** | Registre et orchestrateur du cycle de vie (`PluginRegistry`, `PluginManager`) | Phase 1 (US-005) | P1-T4 | **Terminé** |
| **P1-T6** | Gestionnaire de migrations isolées par plugin (`PluginMigrationManager`) | Phase 1 (US-006) | P1-T5 | **Terminé** |
| **P1-T7** | Authentification de base et Panneau d'administration minimal | Phase 1 (US-007) | P1-T6 | **Terminé** |
| **P1-T8** | Création du plugin de démonstration (`platform/plugins/demo-hello`) | Phase 1 (US-008) | P1-T7 | **Terminé** |
| **P1-T9** | Suite de tests automatisés du MVP (Unit, Feature, Architecture) | Phase 1 (US-009) | P1-T8 | **Terminé** |
| **P1-T10** | Validation des critères d'acceptation du MVP | Phase 1 (US-009) | P1-T9 | **Terminé** |
|---|---|---|---|---|
| **P2-T1** | Tri topologique et détection des cycles (`DependencyResolver`) | Phase 2 (US-010) | P1-T10 | **Terminé** |
| **P2-T2** | Bus de hooks extensible (`HookManager`, actions & filtres) | Phase 2 (US-011) | P2-T1 | **Terminé** |
| **P2-T3** | Tolérance aux crashs et Safe Mode (`SafeModeManager`, `PluginQuarantineHandler`) | Phase 2 (US-012) | P2-T2 | **Terminé** |
| **P2-T4** | Navigation dynamique et registre de permissions (`NavigationRegistry`, `PermissionRegistry`) | Phase 2 (US-013) | P2-T3 | **Terminé** |
|---|---|---|---|---|
| **P3-T1** | Gestionnaire d'actifs statiques isolés (`PluginAssetManager`) | Phase 3 (US-014) | P2-T4 | **Terminé** |
| **P3-T2** | Contrat de stockage et plugin médiathèque (`lcmscore.media-library`) | Phase 3 (US-015) | P3-T1 | **Terminé** |
| **P3-T3** | Plugin de gestion des pages statiques (`lcmscore.pages`) | Phase 3 (US-016) | P3-T2 | **Terminé** |
| **P3-T4** | Plugin de menus arborescents et hook de rendu (`lcmscore.menus`) | Phase 3 (US-017) | P3-T3 | **Terminé** |
| **P3-T5** | Plugin SEO et métadonnées via bus de hooks (`lcmscore.seo`) | Phase 3 (US-018) | P3-T4 | **Terminé** |
|---|---|---|---|---|
| **P4-T1** | Contrats Publics de Thèmes et Découverte (`ThemeDiscovery`, `ThemeManifest`) | Phase 4 (US-019) | P3-T5 | **Terminé** |
| **P4-T2** | Gestionnaire de Thèmes et Surcharge de Vues (`ThemeManager`, `cms_themes`) | Phase 4 (US-020) | P4-T1 | **Terminé** |
| **P4-T3** | Gestionnaire d'Assets de Thème (`ThemeAssetManager`, `cms:theme:publish-assets`) | Phase 4 (US-021) | P4-T2 | **Terminé** |
| **P4-T4** | Thème de Référence Découplé (`platform/themes/default-modern`) | Phase 4 (US-022) | P4-T3 | **Terminé** |
| **P4-T5** | Commandes CLI et Interface d'Administration des Thèmes (`AdminThemeController`) | Phase 4 (US-023) | P4-T4 | **Terminé** |
|---|---|---|---|---|
| **P5-T1** | Renommage officiel du projet en LCMS Core (CORR-01) | Phase 5 | P4-T5 | **Terminé** |
| **P5-T2** | Layout unifié admin et persistance de l'admin-sidebar (CORR-04) | Phase 5 | P5-T1 | **Terminé** |
| **P5-T3** | Page détaillée de plugin et gardes de dépendances inverses (CORR-02) | Phase 5 | P5-T2 | **Terminé** |
| **P5-T4** | Pipeline sécurisé d'importation et installation de plugins ZIP (CORR-03) | Phase 5 | P5-T3 | **Terminé** |
| **P5-T5** | Bouton de suppression et purge physique des extensions désinstallées | Phase 5 | P5-T4 | **Terminé** |
| **P5-T6** | Importation de thème ZIP et suppression sécurisée des thèmes inactifs | Phase 5 | P5-T5 | **Terminé** |
| **P5-T7** | Intégration du thème AdminLTE version 4 sur l'ensemble de l'administration | Phase 5 | P5-T6 | **Terminé** |

---

## 2. Fiches Détail des Tâches de la Phase 1 (MVP du Noyau)

### Tâche P1-T1 : Initialisation de la structure du noyau
- **Objectif :** Poser l'arborescence native et configurer le ServiceProvider principal du noyau.
- **Fichiers concernés :** `app/Core/CoreServiceProvider.php`, `config/cms.php`.
- **Dépendances :** Validation Phase 0.
- **Tests nécessaires :** Test de démarrage du CoreServiceProvider sans plugin.
- **Critère d'acceptation :** Le conteneur IoC enregistre les composants du noyau sans erreur.

### Tâche P1-T2 : Implémentation des Contrats Publics
- **Objectif :** Écrire les interfaces strictes typées sous `app/Core/Contracts`.
- **Fichiers concernés :**
  - `app/Core/Contracts/PluginInterface.php`
  - `app/Core/Contracts/PluginManifestInterface.php`
  - `app/Core/Contracts/PluginLifecycleListenerInterface.php`
- **Dépendances :** P1-T1.
- **Tests nécessaires :** Tests d'architecture garantissant qu'aucune classe concrète du noyau ne fuite dans les contrats.
- **Critère d'acceptation :** Interfaces validées par PHPStan niveau 8 sans avertissement.

### Tâche P1-T3 : Validateur de Manifeste (`ManifestValidator`)
- **Objectif :** Parser et valider le fichier `plugin.json` contre un schéma de validation strict.
- **Fichiers concernés :** `app/Core/PluginDiscovery/ManifestValidator.php`, `app/Core/PluginDiscovery/Exceptions/InvalidManifestException.php`.
- **Dépendances :** P1-T2.
- **Tests nécessaires :** Tests unitaires sur JSON malformé, champs obligatoires absents, ID invalide, tentative de path traversal.
- **Critère d'acceptation :** Tout manifeste non conforme est rejeté avec un message d'erreur clair.

### Tâche P1-T4 : Découvreur de Plugins (`PluginDiscovery`)
- **Objectif :** Scanner le répertoire `platform/plugins/` et instancier les objets manifestes.
- **Fichiers concernés :** `app/Core/PluginDiscovery/PluginDiscovery.php`.
- **Dépendances :** P1-T3.
- **Tests nécessaires :** Découverte dans un dossier contenant des plugins valides, invalides et vides.
- **Critère d'acceptation :** Seuls les plugins conformes sont retournés, les répertoires invalides sont ignorés ou journalisés.

### Tâche P1-T5 : Registre et Gestionnaire de Cycle de Vie
- **Objectif :** Implémenter la machine à états (`AVAILABLE`, `INSTALLED`, `ACTIVE`, `DISABLED`, `ERROR`).
- **Fichiers concernés :** `app/Core/PluginRegistry/PluginRegistry.php`, `app/Core/PluginManager/PluginManager.php`.
- **Dépendances :** P1-T4.
- **Tests nécessaires :** Transitions d'états et vérification de la persistance en base / cache.
- **Critère d'acceptation :** L'état d'un plugin persiste entre deux requêtes HTTP.

### Tâche P1-T6 : Gestionnaire des Migrations de Plugins
- **Objectif :** Exécuter de manière isolée les fichiers de migration appartenant au plugin.
- **Fichiers concernés :** `app/Core/Lifecycle/PluginMigrationManager.php`, migration `cms_plugin_migrations`.
- **Dépendances :** P1-T5.
- **Tests nécessaires :** Exécution des migrations à l'installation, aucune suppression lors de la désactivation.
- **Critère d'acceptation :** Les tables du plugin sont créées à l'installation et préservées à la désactivation.

### Tâche P1-T7 & P1-T8 : Plugin de Démonstration & Validation des Routes
- **Objectif :** Construire `demo-hello` et vérifier l'isolation du routage.
- **Fichiers concernés :** `platform/plugins/demo-hello/*`.
- **Dépendances :** P1-T6.
- **Tests nécessaires :** Test HTTP GET `/demo/hello` (200 si actif, 404 si inactif).
- **Critère d'acceptation :** La route n'existe que lorsque le plugin est dans l'état `ACTIVE`.

---

## 3. Fiches Détail des Tâches de la Phase 2 (Stabilisation & Résilience)

### Tâche P2-T1 : Tri Topologique et Détection de Cycles (US-010)
- **Objectif :** Déterminer un ordre de boot déterministe via l'algorithme de Kahn et rejeter les cycles.
- **Fichiers :** `app/Core/DependencyResolver/DependencyResolver.php`.
- **Statut :** **Terminé**.

### Tâche P2-T2 : Bus de Hooks Extensible (US-011)
- **Objectif :** Mécanique d'actions et de filtres priorisés et interface `HookSubscriberInterface`.
- **Fichiers :** `app/Core/Hooks/HookManager.php`, `app/Core/Hooks/Facades/Hook.php`.
- **Statut :** **Terminé**.

### Tâche P2-T3 : Tolérance aux Crashs et Safe Mode (US-012)
- **Objectif :** Quarantaine automatique en cas d'exception et contournement d'urgence.
- **Fichiers :** `app/Core/Recovery/SafeModeManager.php`, `app/Core/Recovery/PluginQuarantineHandler.php`.
- **Statut :** **Terminé**.

### Tâche P2-T4 : Navigation Dynamique et Permissions (US-013)
- **Objectif :** Menus d'administration injectés et gestion des permissions déclarées.
- **Fichiers :** `app/Core/Admin/NavigationRegistry.php`, `app/Core/Permissions/PermissionRegistry.php`.
- **Statut :** **Terminé**.

---

## 4. Fiches Détail des Tâches de la Phase 3 (Plugins Fondamentaux & Assets)

### Tâche P3-T1 : Gestionnaire d'Actifs Statiques Isolés (US-014)
- **Objectif :** Servir et publier les assets statiques sans compilation globale.
- **Fichiers :** `app/Core/Assets/PluginAssetManager.php`, `app/Core/Console/Commands/PluginPublishAssetsCommand.php`, `tests/Feature/PluginAssetManagerTest.php`.
- **Dépendances :** P2-T4.
- **Critère d'acceptation :** Commande de publication fonctionnelle et suppression propre à la désinstallation.
- **Statut :** **Terminé**.

### Tâche P3-T2 : Contrat de Stockage et Plugin Médiathèque (US-015)
- **Objectif :** Bibliothèque de médias autonome avec contrat `MediaStorageInterface`.
- **Fichiers :** `app/Core/Contracts/MediaStorageInterface.php`, `platform/plugins/lcmscore.media-library/*`, `tests/Feature/Plugins/MediaLibraryPluginTest.php`.
- **Dépendances :** P3-T1.
- **Critère d'acceptation :** Upload sécurisé de fichiers et persistance des métadonnées.
- **Statut :** **Terminé**.

### Tâche P3-T3 : Plugin de Pages Statiques (US-016)
- **Objectif :** Création et affichage de pages web avec URLs personnalisées.
- **Fichiers :** `platform/plugins/lcmscore.pages/*`.
- **Dépendances :** P3-T2.
- **Critère d'acceptation :** Route `/page/{slug}` active uniquement si la page est publiée et le plugin actif.
- **Statut :** **Terminé**.

### Tâche P3-T4 : Plugin de Menus Arborescents (US-017)
- **Objectif :** Gestion de menus hiérarchiques et helper de rendu HTML.
- **Fichiers :** `platform/plugins/lcmscore.menus/*`.
- **Dépendances :** P3-T3.
- **Critère d'acceptation :** Rendu HTML propre de navigation hiérarchique.
- **Statut :** **Terminé**.

### Tâche P3-T5 : Plugin SEO et Métadonnées via Hooks (US-018)
- **Objectif :** Injection de balises OpenGraph et canonical via le filtre `cms.head`.
- **Fichiers :** `platform/plugins/lcmscore.seo/*`.
- **Dépendances :** P3-T4.
- **Critère d'acceptation :** Métadonnées injectées dynamiquement sans couplage dur.
- **Statut :** **Terminé**.

---

## 5. Fiches Détail des Tâches de la Phase 4 (Moteur de Thèmes & Rendu Découplé)

### Tâche P4-T1 : Contrats Publics de Thèmes et Découverte (US-019)
- **Objectif :** Contrats `ThemeInterface` et `ThemeManifestInterface`, DTO `ThemeManifest`, validation de `theme.json` et service `ThemeDiscovery`.
- **Fichiers :** `app/Core/Contracts/ThemeInterface.php`, `app/Core/Contracts/ThemeManifestInterface.php`, `app/Core/Themes/DTO/ThemeManifest.php`, `app/Core/Themes/ThemeDiscovery.php`, `tests/Unit/Themes/ThemeDiscoveryTest.php`.
- **Dépendances :** P3-T5.
- **Critère d'acceptation :** Découverte récursive sécurisée de `platform/themes/` avec contrôle anti-traversal.
- **Statut :** **Terminé**.

### Tâche P4-T2 : Gestionnaire de Thèmes et Surcharge de Vues (US-020)
- **Objectif :** Service `ThemeManager`, persistance de l'activation via table `cms_themes` et hiérarchie de surcharge des templates Blade de plugins.
- **Fichiers :** `database/migrations/2026_10_15_000001_create_cms_themes_table.php`, `app/Core/Themes/ThemeManager.php`, `tests/Feature/Themes/ThemeManagerTest.php`.
- **Dépendances :** P4-T1.
- **Critère d'acceptation :** Surcharge dynamique des vues de plugins sans altérer les sources originales.
- **Statut :** **Terminé**.

### Tâche P4-T3 : Gestionnaire d'Assets de Thème (US-021)
- **Objectif :** Publication isolée des ressources statiques des thèmes et commande CLI.
- **Fichiers :** `app/Core/Themes/ThemeAssetManager.php`, `app/Core/Console/Commands/ThemePublishAssetsCommand.php`, `tests/Feature/Themes/ThemeAssetManagerTest.php`.
- **Dépendances :** P4-T2.
- **Critère d'acceptation :** Assets publiés dans `public/vendor/themes/{id}` avec support symlink et nettoyage.
- **Statut :** **Terminé**.

### Tâche P4-T4 : Thème de Référence Découplé default-modern (US-022)
- **Objectif :** Thème responsive complet intégrant layouts, navigation, slot content et hooks.
- **Fichiers :** `platform/themes/default-modern/*`, `tests/Feature/Themes/DefaultModernThemeTest.php`.
- **Dépendances :** P4-T3.
- **Critère d'acceptation :** Rendu front fluide combinant pages, menus et SEO.
- **Statut :** **Terminé**.

### Tâche P4-T5 : Commandes CLI et Interface d'Administration des Thèmes (US-023)
- **Objectif :** Écran d'administration `/admin/themes` et commandes `cms:theme:list`, `cms:theme:activate`.
- **Fichiers :** `app/Core/Console/Commands/ThemeListCommand.php`, `app/Core/Console/Commands/ThemeActivateCommand.php`, `platform/core/Http/Controllers/Admin/AdminThemeController.php`, `platform/core/resources/views/admin/themes/index.blade.php`, `tests/Feature/Themes/AdminThemeTest.php`.
- **Dépendances :** P4-T4.
- **Critère d'acceptation :** Changement de thème instantané depuis l'admin et en CLI.
- **Statut :** **Terminé**.

---

## 3. Fiches Détail des Tâches de la Phase 5 (Correctifs et Améliorations LCMS Core)

### Tâche P5-T1 : Renommage officiel du projet en LCMS Core (CORR-01)
- **Objectif :** Harmoniser le nom commercial officiel **LCMS Core** dans la barre latérale, le layout du thème par défaut et les fichiers d'environnement (`.env.example`, `.env`), sans altérer les namespaces ou identifiants techniques.
- **Fichiers :** `platform/core/resources/views/admin/layouts/sidebar.blade.php`, `platform/themes/default-modern/resources/views/layouts/app.blade.php`, `.env.example`, `.env`, `tests/Feature/AdminNavigationRegistryTest.php`.
- **Dépendances :** P4-T5.
- **Critère d'acceptation :** Nom « LCMS Core » présent et vérifié dans tous les tests de navigation.
- **Statut :** **Terminé**.

### Tâche P5-T2 : Layout unifié admin et persistance de l'admin-sidebar (CORR-04)
- **Objectif :** Éliminer les ruptures d'affichage de la barre latérale sur les pages de configuration des plugins via un layout maître canonique `core::admin.layouts.app`.
- **Fichiers :** `platform/core/resources/views/admin/layouts/app.blade.php`, refactorisation des vues `lcmscore.menus`, `lcmscore.pages`, `lcmscore.seo`, `lcmscore.media-library`, `tests/Feature/AdminUnifiedLayoutTest.php`.
- **Dépendances :** P5-T1.
- **Critère d'acceptation :** L'élément `.admin-sidebar` est présent et testé avec succès sur 100% des routes d'administration.
- **Statut :** **Terminé**.

### Tâche P5-T3 : Page détaillée de plugin et gardes de dépendances inverses (CORR-02)
- **Objectif :** Rendre les noms de plugins cliquables vers `/admin/plugins/{id}`, afficher la cartographie des métadonnées et prérequis, et bloquer la désactivation / désinstallation non confirmée si des plugins actifs en dépendent (`PluginManager::getDependents()`).
- **Fichiers :** `app/Core/PluginManager/PluginManager.php`, `platform/core/routes/admin.php`, `platform/core/Http/Controllers/Admin/PluginController.php`, `platform/core/resources/views/admin/plugins/show.blade.php`, `tests/Feature/AdminPluginDetailTest.php`.
- **Dépendances :** P5-T2.
- **Critère d'acceptation :** Page de configuration détaillée opérationnelle, blocage des désactivations en cascade sauf paramètre force.
- **Statut :** **Terminé**.

### Tâche P5-T4 : Pipeline sécurisé d'importation et installation de plugins ZIP (CORR-03)
- **Objectif :** Permettre l'import et l'installation d'extensions au format ZIP avec protections anti-traversal (Zip Slip), détection des exécutables interdits, décompression en bac à sable et redécouverte automatique.
- **Fichiers :** `app/Core/PluginDiscovery/PluginZipInstaller.php`, `app/Core/PluginDiscovery/Exceptions/InvalidPluginArchiveException.php`, `app/Core/CoreServiceProvider.php`, `platform/core/Http/Controllers/Admin/PluginController.php`, `platform/core/resources/views/admin/plugins/index.blade.php`, `tests/Feature/AdminPluginZipUploadTest.php`.
- **Dépendances :** P5-T3.
- **Critère d'acceptation :** Upload ZIP validé avec redirection vers la fiche détaillée, rejets stricts des attaques Zip Slip et exécutables.
- **Statut :** **Terminé**.

### Tâche P5-T5 : Bouton de suppression et purge physique des extensions désinstallées
- **Objectif :** Fournir un bouton de suppression d'extension sur les vues d'administration (`/admin/plugins` et `/admin/plugins/{id}`) actif uniquement lorsque le plugin est désinstallé (`AVAILABLE`), avec confirmation explicite, purge sécurisée du répertoire avec contrôle anti-traversal via `PluginManager::delete()` et nettoyage d'assets.
- **Fichiers :** `app/Core/PluginManager/PluginManager.php`, `platform/core/routes/admin.php`, `platform/core/Http/Controllers/Admin/PluginController.php`, `platform/core/resources/views/admin/plugins/index.blade.php`, `platform/core/resources/views/admin/plugins/show.blade.php`, `tests/Feature/AdminPluginManagementHttpTest.php`.
- **Dépendances :** P5-T4.
- **Critère d'acceptation :** Boutons visibles et fonctionnels à l'état désinstallé, rejet des suppressions si le plugin est actif/installé, suppression physique vérifiée par tests.
- **Statut :** **Terminé**.



