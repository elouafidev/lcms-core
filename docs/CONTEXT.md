# Contexte Courant du Projet — Laravel Plugin-First CMS

**Dernière mise à jour :** 2026-10-19 (Phase 5 - Correctifs LCMS Core)
**Responsable d'architecture :** Équipe d'ingénierie logicielle & Architecture CMS

---

## 1. Vision et Orientation Stratégique
Le projet conçoit et implémente un CMS modulaire fondé sur Laravel dont le nom officiel est désormais **LCMS Core**. Le noyau est strictement minimal (Lean Core) et l'ensemble des fonctionnalités éditoriales et métier (pages, menus, médias, formulaires, thèmes, SEO) sont portées par des plugins autonomes respectant des contrats publics précis.
Priorité absolue : robustesse architecturale, isolation logique, gestion rigoureuse des dépendances et résilience (tolérance aux erreurs d'extensions).

---

## 2. Phase Actuelle
- **Phase active :** **PHASE 5 TERMINÉE À 100% (CORRECTIFS LCMS CORE VALIDÉS : CORR-01 À CORR-04)**
- **Objectif de phase :**
  1. **CORR-01 :** Harmonisation du nom officiel **LCMS Core** sur l'ensemble de l'interface et des thèmes.
  2. **CORR-02 :** Page de configuration détaillée d'un plugin (`/admin/plugins/{id}`) et contrôle préventif des dépendances inverses.
  3. **CORR-03 :** Pipeline sécurisé d'importation et d'installation d'extensions au format ZIP (`PluginZipInstaller`, protection Zip Slip).
  4. **CORR-04 :** Layout d'administration unifié (`core::admin.layouts.app`) assurant l'affichage permanent et cohérent de l'`admin-sidebar`.
- **Statut :** Phase 5 achevée avec succès. 230 tests au vert (1368 assertions), analyse statique PHPStan niveau 8 (0 erreurs) et Laravel Pint (0 erreurs).

---

## 3. Statut d'Avancement des Fonctionnalités

### 3.1. Fonctionnalités Terminées (Phase 0, Phase 1, Phase 2, Phase 3 & Phase 4)
- [x] **Cadre architectural & Directives :** `AGENTS.md`, ADR-0001 (Monolithe modulaire), ADR-0002 (Manifestes & Contrats).
- [x] **Spécifications techniques :** `docs/ARCHITECTURE.md`, `docs/PLUGIN_SYSTEM.md`.
- [x] **Sécurité & Menaces :** `docs/SECURITY.md`, `docs/THREAT_MODEL.md` (STRIDE).
- [x] **Assurance qualité :** `docs/TESTING.md`, `docs/ROADMAP.md`.
- [x] **Spécification des User Stories :** `docs/USER_STORIES.md` (23 User Stories avec messages de commit conventionnels).
- [x] **US-001 (P1-T1) :** Squelette Laravel minimal initialisé, `config/cms.php` configuré, `CoreServiceProvider` enregistré dans `bootstrap/app.php`, tests unitaires au vert.
- [x] **US-002 (P1-T2) :** Contrats publics stricts (`PluginInterface`, `PluginManifestInterface`, `PluginLifecycleListenerInterface`, `HookSubscriberInterface`, `NavigationItemContract`) et tests d'architecture de pureté au vert.
- [x] **US-003 (P1-T3) :** Validateur de manifeste JSON (`ManifestValidator`), DTO immuable `PluginManifest`, protection anti-traversal et 9 tests unitaires au vert.
- [x] **US-004 (P1-T4) :** Découvreur de plugins locaux (`PluginDiscovery`), DTO `DiscoveredPlugin`, `PluginClassLoader` avec injection dynamique PSR-4 dans Composer.
- [x] **US-005 (P1-T5) :** Table `cms_plugins`, Enum `PluginState` (5 états), modèle `PluginRecord`, `PluginRegistry` et `PluginManager` avec isolation et Safe Mode.
- [x] **US-006 (P1-T6) :** Gestionnaire de migrations isolées (`PluginMigrationManager`, table `cms_plugin_migrations`), exécution transactionnelle à l'installation, préservation des données à la désactivation, rollback sélectif à la désinstallation complète.
- [x] **US-007 (P1-T7) :** Panneau d'administration minimal Blade (`/admin/plugins`), validation CSRF, et commandes CLI (`cms:plugin:list`, `cms:plugin:enable`, `cms:plugin:disable`, `cms:recovery:safe-mode`).
- [x] **US-008 (P1-T8) :** Plugin de référence `platform/plugins/demo-hello/` avec table `plugin_demo_messages`, contrôleur, vue Blade et route `/demo/hello` isolée montée uniquement à l'état `ACTIVE`.
- [x] **US-009 (P1-T9/T10) :** Garde-fous d'architecture (`CoreBoundariesTest`), test d'intégration end-to-end du cycle de vie complet (`CompleteMvpLifecycleTest`), configuration `pint.json` et `phpstan.neon` au niveau 8 sans avertissement.
- [x] **US-010 (P2-T1) :** Tri topologique et détection des dépendances circulaires (Algorithme de Kahn, `DependencyResolver`, `CircularDependencyException`, `MissingDependencyException`), ordonnancement déterministe au boot et contrôle préventif à l'activation.
- [x] **US-011 (P2-T2) :** Bus de hooks et système d'interception (`HookManager`, actions & filtres priorisés, `HookSubscriberInterface`, façade `Hook`).
- [x] **US-012 (P2-T3) :** Tolérance aux crashs, mode de secours (*Emergency Safe Mode*) et mise en quarantaine (`SafeModeManager`, `PluginQuarantineHandler`, table `cms_plugin_errors`).
- [x] **US-013 (P2-T4) :** Registre dynamique des permissions et entrées de menu admin (`NavigationRegistry`, `PermissionRegistry`, `NavigationItemContract`, barre latérale Blade `sidebar.blade.php`).
- [x] **US-014 (P3-T1) :** Gestionnaire d'actifs statiques isolés (`PluginAssetManager`), commande CLI `cms:plugin:publish-assets`, publication automatique à l'activation et nettoyage à la désinstallation purgée.
- [x] **US-015 (P3-T2) :** Contrat public `MediaStorageInterface` et plugin autonome de médiathèque (`acme.media-library`) avec upload sécurisé, table `plugin_media_items` et menu admin.
- [x] **US-016 (P3-T3) :** Plugin fondamental de pages statiques (`acme.pages`) avec modèle `Page`, table `plugin_pages`, routage dynamique `/page/{slug}` (200 si publié, 404 si brouillon/inactif), contrôleur d'administration et vue Blade front.
- [x] **US-017 (P3-T4) :** Plugin fondamental de menus de navigation (`acme.menus`) avec modèles `Menu` et `MenuItem`, tables isolées `plugin_menus` et `plugin_menu_items`, service de rendu récursif `MenuRenderer::render('main-nav')`, filtre Hook `cms.menu` et directive Blade `@menu`.
- [x] **US-018 (P3-T5) :** Plugin SEO et métadonnées via bus de filtres Hook (`acme.seo`) avec filtre `cms.head` (titre, méta-description, liens canoniques, balises OpenGraph, configuration par défaut persistée via `plugin_seo_settings` et écran d'administration).
- [x] **US-019 (P4-T1) :** Contrats publics de thèmes (`ThemeInterface`, `ThemeManifestInterface`), DTO `ThemeManifest` avec protection anti-traversal et service `ThemeDiscovery` pour `platform/themes/`.
- [x] **US-020 (P4-T2) :** Gestionnaire de thèmes `ThemeManager`, persistance de l'activation via table `cms_themes` et hiérarchie de surcharge des templates Blade de plugins (View Overriding).
- [x] **US-021 (P4-T3) :** Gestionnaire d'assets de thème `ThemeAssetManager` et commande CLI de publication `cms:theme:publish-assets`.
- [x] **US-022 (P4-T4) :** Thème de référence complet et découplé `platform/themes/default-modern` (layouts, navigation, pages statiques, hooks SEO et assets stylisés).
- [x] **US-023 (P4-T5) :** Commandes CLI et interface d'administration des thèmes (`ThemeListCommand`, `ThemeActivateCommand`, `AdminThemeController`, vue `/admin/themes` et entrée `themes-management` dans `NavigationRegistry`).

### 3.2. Phase 5 — Correctifs et Améliorations LCMS Core (Terminée)
- [x] **CORR-01 :** Renommage commercial et harmonisation du nom officiel **LCMS Core** dans l'interface d'administration (`sidebar.blade.php`), le thème de référence `default-modern`, `.env.example` et `.env`, avec préservation stricte des namespaces et identifiants techniques.
- [x] **CORR-02 :** Vue détaillée d'une extension (`/admin/plugins/{id}`), liens cliquables dans `/admin/plugins`, inspection des métadonnées, diagnostic de résolution des dépendances directes et gardes de dépendances inverses (`PluginManager::getDependents()`) empêchant les désactivations / désinstallations en cascade non confirmées.
- [x] **CORR-03 :** Pipeline sécurisé d'importation et installation de plugins via archive ZIP (`PluginZipInstaller`), protection contre les attaques Zip Slip, rejet d'exécutables interdits, validation de `plugin.json` via `ManifestValidator`, bac à sable temporaire nettoyé systématiquement et redécouverte automatique.
- [x] **CORR-04 :** Layout d'administration unifié (`core::admin.layouts.app`), persistance inaltérable de l'`admin-sidebar` et des alertes de session sur toutes les vues de plugins (`acme.menus`, `acme.pages`, `acme.seo`, `acme.media-library`, `core::admin.plugins.*`, `core::admin.themes.*`).
- [x] **Suppression de plugin :** Bouton de suppression définitive sur la gestion des extensions (`/admin/plugins` et `/admin/plugins/{id}`) lorsque l'extension est désinstallée (`AVAILABLE`), purge sécurisée du dossier physique avec vérification de confinement via `PluginManager::delete()`, nettoyage d'assets et redécouverte automatique.
- [x] **Gestion avancée des thèmes (Importation ZIP & Suppression) :** Formulaire et pipeline sécurisé d'importation de thèmes via archive ZIP (`ThemeZipInstaller`, protections Zip Slip et fichiers interdits, extraction isolée et redécouverte), bouton de suppression sécurisée sur `/admin/themes` pour tout thème inactif (`ThemeManager::delete()`, vérification de confinement de répertoire, interdiction de suppression du thème actif, purge du dossier, nettoyage des assets publiés et du registre DB).
- [x] **Intégration Thème AdminLTE Version 4 :** Modernisation intégrale des pages d'administration sous AdminLTE 4 (Bootstrap 5.3, Bootstrap Icons, Overlayscrollbars), structuration modulaire (`app.blade.php`, `navbar.blade.php`, `sidebar.blade.php`, `footer.blade.php`), boutons d'action unifiés, cartes et tables interactives, maintien de la classe de compatibilité `admin-sidebar` et 100% de réussite aux 230 tests automatisés (1368 assertions).

---

## 4. Architecture Actuellement Retenue
- **Paradigme :** Monolithe modulaire avec séparation stricte en trois couches :
  1. **Noyau (`app/Core`) :** Infrastructure technique pure, injection de dépendances, registre, contrats.
  2. **Panneau d'administration système (`platform/core`) :** Authentification sécurisée, écran de contrôle des extensions et layout unifié.
  3. **Extensions (`platform/plugins/*`) :** Modules autonomes dotés de leur propre espace de noms (`Plugins\{PluginId}\`), routes, contrôleurs, vues et migrations.
- **Principe d'isolation :** Aucun appel direct d'implémentation privée entre plugins. Communication exclusive via contrats publics (`app/Core/Contracts`), événements Laravel typés et bus de hooks (`app/Core/Hooks`).

---

## 5. Versions des Technologies

| Composant | Version Retenue / Cible | Rôle |
|---|---|---|
| **Système d'exploitation** | Windows (Hôte) / Debian 12 (Conteneur) | Environnement d'exécution |
| **PHP** | 8.2+ | Moteur d'exécution backend avec typage strict |
| **Laravel** | 11.x / 12.x | Framework de base pour routage, IoC et ORM Eloquent |
| **Composer** | 2.x | Gestionnaire de dépendances PHP |
| **Base de données** | SQLite (Dev/Test) / PostgreSQL (Prod) | Persistance des données du noyau et des plugins |

---

## 6. Problèmes Connus et Risques Identifiés

1. **Dépendances inverses critiques :** Si une extension comme `acme.menus` ou `acme.media-library` est désactivée alors que le thème ou des pages en dépendent, le site public peut dégrader son rendu. *Mitigation :* `PluginManager::getDependents()` bloque la désactivation/désinstallation sans `force=true` et affiche un avertissement clair sur `/admin/plugins/{id}`.
2. **Archives ZIP corrompues ou malicieuses :** Risque de traversée d'arborescence (Zip Slip) ou d'injection d'exécutables (`.exe`, `.sh`, `.phar`). *Mitigation :* `PluginZipInstaller` filtre chaque entrée d'archive, valide `plugin.json` dans un bac à sable temporaire UUID avant déplacement final et assure le nettoyage dans un bloc `finally`.

---

## 7. Prochaine Tâche Recommandée
1. Présenter le bilan complet de la **PHASE 5 (Correctifs LCMS Core)** à l'utilisateur :
   - 213 tests au vert (1286 assertions).
   - Analyse statique PHPStan niveau 8 sans erreur.
   - Laravel Pint 100% conforme.
   - Les 4 correctifs (CORR-01, CORR-02, CORR-03, CORR-04) sont pleinement opérationnels et validés par des tests unitaires et d'intégration.
   - Les règles Git ont été strictement respectées (`LCMS_CORE_CORRECTIFS.md` à la racine, `/docs/correctifs/` exclu).

---

## 8. Points Critiques à ne pas Modifier sans Justification
- Ne jamais ajouter de contrôleur de blog ou de gestionnaire de contenu directement dans `app/Http/Controllers` ou `app/Core`.
- Ne jamais charger un plugin dont le manifeste échoue à la validation de schéma.
- Conserver la séparation physique entre `platform/plugins/` et le code de base de l'application Laravel.
- Toujours étendre `@extends('core::admin.layouts.app')` pour toutes les vues d'administration afin de conserver la barre latérale `admin-sidebar`.
