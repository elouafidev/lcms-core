# Architecture Technique — Laravel Plugin-First CMS

**Version du document :** 1.0.0
**Date de révision :** 2026-10-12
**Statut :** Spécification de référence — Phase 0

---

## 1. Vue d'Ensemble du Système

Le CMS adopte une architecture en **Monolithe Modulaire Hybride**. Son objectif est de fournir la simplicité de déploiement et de persistance d'un projet Laravel unique, tout en garantissant une indépendance absolue entre les fonctionnalités métier grâce à un système de plugins stricts.

```text
+-----------------------------------------------------------------------------------+
|                                  HTTP REQUEST                                     |
+-----------------------------------------------------------------------------------+
                                         │
                                         ▼
+-----------------------------------------------------------------------------------+
|                               LARAVEL KERNEL & BOOT                               |
|                  (bootstrap/app.php -> CoreServiceProvider)                       |
+-----------------------------------------------------------------------------------+
                                         │
                                         ▼
+-----------------------------------------------------------------------------------+
|                              CMS CORE (app/Core)                                  |
|   ┌────────────────────────┬───────────────────────────┬──────────────────────┐   |
|   │     PluginRegistry     │     PluginDiscovery       │  DependencyResolver  │   |
|   ├────────────────────────┼───────────────────────────┼──────────────────────┤   |
|   │     LifecycleManager   │     Hooks & Events        │  PermissionRegistry  │   |
|   ├────────────────────────┼───────────────────────────┼──────────────────────┤   |
|   │     Recovery/SafeMode  │     Diagnostics & Audit   │  Public Contracts    │   |
|   └────────────────────────┴───────────────────────────┴──────────────────────┘   |
+-----------------------------------------------------------------------------------+
                                         │
                    ┌────────────────────┴────────────────────┐
                    ▼                                         ▼
+---------------------------------------+ +---------------------------------------+
|          ADMIN PANEL (CORE)           | |         ACTIVE PLUGINS LAYER          |
|        (platform/core/AdminPanel)     | |         (platform/plugins/*)          |
|  - Authentification racine            | |  - Plugins isolés (ex: Blog, Pages)   |
|  - Gestionnaire de plugins (GUI)      | |  - Routes, Modèles, Vues dédiés       |
|  - Journaux & Diagnostics système     | |  - Migrations indépendantes           |
+---------------------------------------+ +---------------------------------------+
```

---

## 2. Frontières Architecturales et Règles de Couplage

### 2.1. Règles Inviolables
1. **Interdiction de couplage horizontal direct :** Le plugin `A` ne peut jamais importer directement une classe interne concrète du plugin `B` (ex: `use Plugins\Blog\Models\Post;` dans `Plugins\Shop`).
2. **Couplage par Contrats Publics :** Si deux extensions doivent interagir, l'échange doit passer par :
   - Une interface définie dans `app/Core/Contracts/`.
   - Le système d'événements typés de Laravel dispatché par le noyau (`app/Core/Events/`).
   - Le gestionnaire de filtres et actions (`app/Core/Hooks/HookManager`).
3. **Imperméabilité du Noyau :** Le noyau ne contient aucune table métier (`posts`, `products`, `media_files`). Si le dossier `platform/plugins/` est vidé, le noyau démarre sans aucune erreur et affiche un tableau de bord administratif vide.

---

## 3. Cycle de Démarrage et Enregistrement des Plugins

Le chargement d'un plugin s'effectue en deux phases séquentielles alignées sur le cycle de vie natif de Laravel : `register()` puis `boot()`.

### 3.1. Séquence de Démarrage (Boot Flow)

```text
1. Laravel démarre les Service Providers du framework de base.
2. CoreServiceProvider est instancié :
   a. Enregistre le Singleton `PluginManager`.
   b. Charge l'état des plugins depuis le cache ou la base de données (`cms_plugins`).
   c. Si le mode de secours (Safe Mode) est actif, interrompt le chargement des extensions tierces.
3. Découverte et Résolution :
   a. PluginDiscovery analyse `platform/plugins/*/plugin.json`.
   b. DependencyResolver ordonne les plugins actifs via un tri topologique (Kahn algorithm).
   c. En cas de cycle ou de dépendance manquante, le plugin concerné passe à l'état `ERROR` et n'est pas chargé.
4. Phase Register :
   a. Pour chaque plugin actif dans l'ordre de résolution :
      - Instancie son ServiceProvider principal déclaré dans le manifeste.
      - Appelle `$provider->register()`.
5. Phase Boot :
   a. Pour chaque plugin actif :
      - Appelle `$provider->boot()`.
      - Enregistre les routes isolées (`routes/web.php`, `routes/api.php`).
      - Charge les vues sous le namespace du plugin (`view()->addNamespace('blog', ...)`).
      - Enregistre les traductions et politiques d'accès (`Policies`).
```

---

## 4. Système d'Événements et de Hooks

Le CMS offre deux mécanismes d'extensibilité distincts :

### 4.1. Événements du Cycle de Vie (Event Bus)
Dispatchés par le noyau pour notifier les modules de changements d'état :
- `PluginDiscovered(PluginManifest $manifest)`
- `PluginInstalling(string $pluginId)` / `PluginInstalled(string $pluginId)`
- `PluginActivating(string $pluginId)` / `PluginActivated(string $pluginId)`
- `PluginDeactivating(string $pluginId)` / `PluginDeactivated(string $pluginId)`
- `PluginFailed(string $pluginId, Throwable $exception)`

### 4.2. Hooks (Actions et Filtres)
Permettent aux plugins d'altérer des données ou d'injecter des éléments d'interface :
- **Actions (`doAction(string $hook, ...$args)`) :** Points d'ancrage d'exécution (ex: `admin.menu.render`, `content.saved`).
- **Filtres (`applyFilter(string $hook, $value, ...$args)`) :** Transformation de flux (ex: `content.body.render`, `seo.meta.title`).

---

## 5. Gestion des Migrations et Persistance

Chaque plugin conserve ses migrations dans son propre sous-dossier `database/migrations/`.

1. **Table de suivi dédiée :** Le noyau utilise une table `cms_plugin_migrations` distincte de la table `migrations` de Laravel.
2. **Exécution isolée :** L'activation d'un plugin déclenche l'exécution ordonnée de ses migrations non appliquées.
3. **Politique de désactivation :** La désactivation d'un plugin **n'exécute pas de rollback**. Les tables et données restent intactes.
4. **Politique de désinstallation :** L'administrateur peut choisir explicitement lors de la désinstallation :
   - Conserver les données (défaut sécurisé).
   - Supprimer définitivement les tables du plugin (action destructrice nécessitant confirmation forte).

---

## 6. Mécanisme de Résilience et Mode de Secours (Safe Mode)

Lorsqu'un plugin déclenche une exception non interceptée qui compromettrait l'accès au panneau de contrôle :
1. **Interception d'erreur critique :** Le gestionnaire de crash du noyau intercepte l'erreur fatale durant l'enregistrement ou le boot du plugin.
2. **Mise en quarantaine automatique :** L'état du plugin coupable passe immédiatement à `ERROR` dans le registre, avec enregistrement de la stack trace dans `cms_plugin_errors`.
3. **Bypass CLI (Artisan) :** L'administrateur système dispose toujours de commandes en ligne de commande indépendantes des plugins :
   ```bash
   php artisan cms:plugin:disable <plugin-id>
   php artisan cms:recovery:safe-mode --on
   ```
4. **Safe Mode activé :** Seul le noyau et le panneau de gestion des plugins sont démarrés, permettant la résolution de l'incident sans risque d'écran blanc global.
