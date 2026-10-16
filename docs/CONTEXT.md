# Contexte Courant du Projet — Laravel Plugin-First CMS

**Dernière mise à jour :** 2026-10-12 (Phase 0)
**Responsable d'architecture :** Équipe d'ingénierie logicielle & Architecture CMS

---

## 1. Vision et Orientation Stratégique
Le projet conçoit et implémente un CMS modulaire fondé sur Laravel où le noyau est strictement minimal (Lean Core) et où l'ensemble des fonctionnalités éditoriales et métier (pages, blogs, médias, formulaires, e-commerce, SEO) sont portées par des plugins autonomes respectant des contrats publics précis.
Priorité absolue : robustesse architecturale, isolation logique, gestion rigoureuse des dépendances et résilience (tolérance aux erreurs d'extensions).

---

## 2. Phase Actuelle
- **Phase active :** **PHASE 2 TERMINÉE (100% DES 4 STORIES DE PHASE 2 LIVRÉES ET VALIDÉES)**
- **Objectif de phase :** Résilience logicielle, ordonnancement déterministe des dépendances (Kahn), extensibilité via bus de hooks (actions & filtres), tolérance aux crashs avec Safe Mode & quarantaine, registre de navigation dynamique et permissions.
- **Statut :** Phase 2 intégralement validée avec 87 tests au vert (624 assertions), analyse statique PHPStan niveau 8 (0 erreurs) et Laravel Pint (0 erreurs).

---

## 3. Statut d'Avancement des Fonctionnalités

### 3.1. Fonctionnalités Terminées (Phase 0, Phase 1 & Phase 2)
- [x] **Cadre architectural & Directives :** `AGENTS.md`, ADR-0001 (Monolithe modulaire), ADR-0002 (Manifestes & Contrats).
- [x] **Spécifications techniques :** `docs/ARCHITECTURE.md`, `docs/PLUGIN_SYSTEM.md`.
- [x] **Sécurité & Menaces :** `docs/SECURITY.md`, `docs/THREAT_MODEL.md` (STRIDE).
- [x] **Assurance qualité :** `docs/TESTING.md`, `docs/ROADMAP.md`.
- [x] **Spécification des User Stories :** `docs/USER_STORIES.md` (13 User Stories avec messages de commit conventionnels).
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

### 3.2. Prochaine Étape (Phase 3 — Moteur de Contenu & Thèmes / Assets)
- [ ] **Phase 3 :** Gestionnaire d'actifs statiques (`PluginAssetManager`), système de thèmes interchangeables et contrat de publication d'assets.

---

## 4. Architecture Actuellement Retenue
- **Paradigme :** Monolithe modulaire avec séparation stricte en trois couches :
  1. **Noyau (`app/Core`) :** Infrastructure technique pure, injection de dépendances, registre, contrats.
  2. **Panneau d'administration système (`platform/core`) :** Authentification sécurisée et écran de contrôle des extensions.
  3. **Extensions (`platform/plugins/*`) :** Modules autonomes dotés de leur propre espace de noms (`Plugins\{PluginId}\`), routes, contrôleurs, vues et migrations.
- **Principe d'isolation :** Aucun appel direct d'implémentation privée entre plugins. Communication exclusive via contrats publics (`app/Core/Contracts`), événements Laravel typés et bus de hooks (`app/Core/Hooks`).

---

## 5. Versions des Technologies

| Composant | Version Retenue / Cible | Rôle |
|---|---|---|
| **Système d'exploitation** | Debian 12 (Bookworm) | Environnement conteneurisé d'exécution |
| **PHP** | 8.2+ | Moteur d'exécution backend avec typage strict |
| **Laravel** | 11.x / 12.x | Framework de base pour routage, IoC et ORM Eloquent |
| **Composer** | 2.x | Gestionnaire de dépendances PHP |
| **Base de données** | SQLite (Dev/Test) / PostgreSQL (Prod) | Persistance des données du noyau et des plugins |
| **Node.js** | 22.x | Console interactive d'exploration architecturale |

---

## 6. Problèmes Connus et Risques Identifiés

1. **Environnement conteneurisé PHP :** Le conteneur initial fourni dispose de Node.js préinstallé. Les binaires PHP et extensions requises (mbstring, xml, sqlite, curl) sont installés via le gestionnaire de paquets du système hôte (`apt-get`).
2. **Risque de corruption du cycle de vie lors d'une migration interrompue :** Un plugin échouant à mi-migration ne doit pas laisser la base dans un état incohérent. *Mitigation :* Transactions de migration avec table de journalisation dédiée `cms_plugin_migrations`.
3. **Risque de crash global induit par un plugin tiers défectueux :** Si un `ServiceProvider` d'un plugin déclenche une exception fatale au boot. *Mitigation :* `PluginManager` isole l'enregistrement via un boot sous surveillance et propose un mécanisme de *Safe Mode* par variable d'environnement ou drapeau en cache.

---

## 7. Prochaine Tâche Recommandée
1. Présenter le bilan de validation de la **PHASE 2 (Stabilisation, Dépendances & Résilience)** à l'utilisateur (100% complétée).
2. Engager la **PHASE 3 (Médiathèque, Thèmes & Gestionnaire d'Assets)** :
   - Publication d'assets statiques isolés par extension (`PluginAssetManager`).
   - Contrat de stockage de médias (`MediaStorageInterface`).
   - Moteur de thèmes interchangeables sans couplage noyau.

---

## 8. Points Critiques à ne pas Modifier sans Justification
- Ne jamais ajouter de contrôleur de blog ou de gestionnaire de contenu directement dans `app/Http/Controllers` ou `app/Core`.
- Ne jamais charger un plugin dont le manifeste échoue à la validation de schéma.
- Conserver la séparation physique entre `platform/plugins/` et le code de base de l'application Laravel.
