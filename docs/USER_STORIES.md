# Spécification des User Stories — Laravel Plugin-First CMS

**Version :** 1.0.0  
**Date de révision :** 2026-10-13  
**Statut :** Document de Référence Opérationnelle  
**Convention de Commit :** [Conventional Commits v1.0.0](https://www.conventionalcommits.org/en/v1.0.0/)

---

## 1. Cadre et Règles d'Exécution

Pour garantir la traçabilité, la stabilité des contrats et le principe de **Lean Core**, chaque User Story suit un processus strict :

1. **Règle 1 Story = 1 Commit Atomique :** Chaque User Story correspond exactement à un commit de code fonctionnel, testé et validé.
2. **Aucune régression autorisée :** Avant chaque commit, la suite de tests (`composer test` ou `php artisan test`), l'analyse statique (`phpstan analyse --level=8`) et le formatage (`pint --test`) doivent être au vert.
3. **Format du message de commit :**
   ```text
   <type>(<scope>): <description concise en anglais ou français>

   [Corps optionnel expliquant le rationnel et les décisions non évidentes]

   Refs: US-XXX
   ```

---

## 2. Typologie des Personas

| Persona | Rôle | Attentes Principales |
|---|---|---|
| **Super-Administrateur (`SuperAdmin`)** | Propriétaire et gestionnaire de la plateforme | Activer/désactiver des fonctionnalités sans risque de crash, disposer d'un Safe Mode. |
| **Développeur de Plugin (`PluginDev`)** | Auteur d'extensions tierces | Contrats publics clairs, documentation des hooks, pas de couplage caché avec le Core. |
| **Visiteur / Utilisateur (`EndUser`)** | Utilisateur des pages publiques ou de l'API | Temps de réponse rapide, navigation fluide, sécurité des données. |
| **Ingénieur Système / DevOps (`SysAdmin`)** | Exploitant d'infrastructure | Commandes CLI robustes, configuration simple via `.env`, logs d'audit précis. |

---

## 3. Epic 0 : Phase 0 — Cadrage, Architecture & Spécifications

---

### US-P0-01 : Initialisation de l'Environnement de Versioning et Exclusions

- **Identifiant :** `US-P0-01` (Plan : `P0-T1`)
- **Persona :** `SysAdmin` / `DevOps`
- **Énoncé :**
  > En tant qu'**administrateur système ou développeur**,  
  > Je souhaite **initialiser le dépôt Git et configurer les règles d'exclusion de fichiers `.gitignore` adaptées à l'écosystème Laravel et PHP**,  
  > Afin d'**éviter de versionner accidentellement des dépendances volumineuses, des caches ou des secrets d'environnement**.

#### Critères d'Acceptation
```gherkin
Given le dossier racine du projet
When git status est exécuté
Then le dépôt Git est correctement initialisé sur la branche main
And le fichier .gitignore exclut node_modules, vendor, .env et les caches de test
```

#### Fichiers Impactés
- `.gitignore`

#### Commit de Code Associé
```text
chore(project): initialize git repository with standard ignore rules

- Configure .gitignore for Laravel, Composer, Node and testing caches
- Prevent accidental commit of secrets and vendor artifacts

Refs: US-P0-01
```

---

### US-P0-02 : Directives Permanentes d'Ingénierie et Gouvernance IA

- **Identifiant :** `US-P0-02` (Plan : `P0-T2`)
- **Persona :** `SysAdmin` / `PluginDev`
- **Énoncé :**
  > En tant qu'**architecte logiciel**,  
  > Je souhaite **formaliser des règles de développement strictes et intangibles dans `AGENTS.md`**,  
  > Afin de **garantir qu'aucun agent IA ou contributeur ne déroge aux principes de Lean Core, de typage strict et de couverture de tests**.

#### Critères d'Acceptation
```gherkin
Given le fichier AGENTS.md
When un agent ou un développeur intervient sur le projet
Then les 5 règles fondamentales (Architecture prioritaire, Lean Core, zéro modification du noyau, propriété des données, gestion du contexte) sont formellement imposées
And les seuils de qualité (PHPStan niveau 8, Pint, tests automatisés) sont clairement exigés
```

#### Fichiers Impactés
- `AGENTS.md`

#### Commit de Code Associé
```text
docs(agents): define engineering rules and agent directives for plugin-first cms

- Specify Lean Core gold rules and zero-coupling constraints
- Require PHP 8.2+ strict typing, Pint linting and PHPStan level 8
- Establish inter-session context update protocols

Refs: US-P0-02
```

---

### US-P0-03 : Architecture Cible et Choix Structurants (ADR)

- **Identifiant :** `US-P0-03` (Plan : `P0-T5`, `P0-T10`)
- **Persona :** `PluginDev` / `SuperAdmin`
- **Énoncé :**
  > En tant qu'**architecte logiciel**,  
  > Je souhaite **décrire l'architecture globale en monolithe modulaire et consigner les décisions dans des Architecture Decision Records (ADR-0001, ADR-0002)**,  
  > Afin de **justifier le rejet des microservices et d'imposer le découplage strict par contrats publics**.

#### Critères d'Acceptation
```gherkin
Given les documents docs/ARCHITECTURE.md et docs/decisions/
When l'équipe consulte les spécifications d'architecture
Then le flux de démarrage Laravel (register puis boot) est précisément documenté
And ADR-0001 justifie le choix du monolithe modulaire face aux microservices
And ADR-0002 impose la séparation par manifestes et contrats publics
```

#### Fichiers Impactés
- `docs/ARCHITECTURE.md`
- `docs/decisions/README.md`
- `docs/decisions/ADR-0001-modular-monolith.md`
- `docs/decisions/ADR-0002-plugin-manifest-and-contracts.md`

#### Commit de Code Associé
```text
docs(architecture): define modular monolith architecture and initial adrs

- Specify Laravel boot sequence and core boundary constraints
- Document ADR-0001 (Modular Monolith) and ADR-0002 (Plugin Manifests & Contracts)
- Map public event bus and hook pipelines

Refs: US-P0-03
```

---

### US-P0-04 : Spécification du Système de Plugins et Machine à 5 États

- **Identifiant :** `US-P0-04` (Plan : `P0-T6`)
- **Persona :** `PluginDev` / `SuperAdmin`
- **Énoncé :**
  > En tant que **développeur de plugin**,  
  > Je souhaite **disposer de la spécification détaillée de l'arborescence d'un plugin, du schéma `plugin.json` et des 5 états du cycle de vie**,  
  > Afin de **concevoir des extensions conformes et interopérables dès la Phase 1**.

#### Critères d'Acceptation
```gherkin
Given le document docs/PLUGIN_SYSTEM.md
When un développeur rédige une extension
Then la structure type sous platform/plugins/ est normalisée
And le schéma JSON du manifeste définit les champs obligatoires (id, version, providers, autoload)
And les transitions d'états (AVAILABLE, INSTALLED, ACTIVE, DISABLED, ERROR) sont rigoureusement spécifiées
```

#### Fichiers Impactés
- `docs/PLUGIN_SYSTEM.md`

#### Commit de Code Associé
```text
docs(plugins): specify plugin system structure and 5-state lifecycle

- Standardize plugin directory layout and JSON schema requirements
- Formalize 5-state lifecycle machine and state transition rules
- Outline Kahn topological sorting for deterministic boot order

Refs: US-P0-04
```

---

### US-P0-05 : Matrice de Sécurité et Modèle de Menaces STRIDE

- **Identifiant :** `US-P0-05` (Plan : `P0-T7`)
- **Persona :** `SysAdmin` / `SuperAdmin`
- **Énoncé :**
  > En tant que **responsable sécurité**,  
  > Je souhaite **analyser les menaces selon la méthode STRIDE et formaliser la politique de sécurité applicative**,  
  > Afin d'**anticiper les risques de traversée de répertoire, d'usurpation de privilèges et de déni de service**.

#### Critères d'Acceptation
```gherkin
Given les documents docs/SECURITY.md et docs/THREAT_MODEL.md
When une révision de sécurité est menée
Then les 6 catégories STRIDE sont associées à des contre-mesures techniques concrètes
And les limites réelles de l'isolation mémoire en pur PHP sont explicitement reconnues
And les règles de protection contre le path traversal (realpath) et le CSRF sont énoncées
```

#### Fichiers Impactés
- `docs/SECURITY.md`
- `docs/THREAT_MODEL.md`

#### Commit de Code Associé
```text
docs(security): formalize application security policy and stride threat model

- Establish authentication, session, and OWASP mitigation policies
- Map threats using STRIDE methodology across trust boundaries
- Highlight PHP runtime execution limits and path traversal mitigations

Refs: US-P0-05
```

---

### US-P0-06 : Définition de la Pyramide de Tests et Qualité

- **Identifiant :** `US-P0-06` (Plan : `P0-T8`)
- **Persona :** `SysAdmin` / `PluginDev`
- **Énoncé :**
  > En tant qu'**ingénieur qualité**,  
  > Je souhaite **définir la stratégie de tests automatisés (Architecture, Unitaire, Fonctionnel, Sécurité)**,  
  > Afin de **garantir que tout incrément futur soit systématiquement vérifié et validé sans régression**.

#### Critères d'Acceptation
```gherkin
Given le document docs/TESTING.md
When l'équipe met en place l'outillage de test
Then la pyramide de test précise les responsabilités de chaque niveau (Pest Arch, Unit, Feature, Security)
And les commandes de vérification locale et CI sont documentées (test, phpstan, pint)
```

#### Fichiers Impactés
- `docs/TESTING.md`

#### Commit de Code Associé
```text
docs(testing): define multi-tier testing strategy and architecture guardrails

- Define testing pyramid including Pest Architecture boundaries
- Specify test suites for manifest validation, dependency cycles, and lifecycle
- Detail CI quality gate commands (PHPUnit, PHPStan, Pint)

Refs: US-P0-06
```

---

### US-P0-07 : Feuille de Route Stratégique, Plan Opérationnel et Synthèse

- **Identifiant :** `US-P0-07` (Plan : `P0-T3`, `P0-T4`, `P0-T9`, `P0-T11`)
- **Persona :** `SuperAdmin` / `SysAdmin` / `PluginDev`
- **Énoncé :**
  > En tant que **chef de projet et architecte**,  
  > Je souhaite **publier la documentation générale (`README.md`), la feuille de route 7 phases (`ROADMAP.md`), le plan opérationnel (`PLAN.md`), le contexte (`CONTEXT.md`) et les User Stories (`USER_STORIES.md`)**,  
  > Afin de **disposer d'un pilotage transparent et d'un alignement parfait avant tout développement de code**.

#### Critères d'Acceptation
```gherkin
Given les documents README.md, docs/ROADMAP.md, docs/PLAN.md, docs/CONTEXT.md et docs/USER_STORIES.md
When l'équipe planifie les itérations
Then les 7 phases de la roadmap sont ordonnées avec leurs critères de passage
And le plan opérationnel lie chaque tâche technique à sa User Story correspondante
And chaque User Story est reliée à un commit de code atomique conventionnel
```

#### Fichiers Impactés
- `README.md`
- `docs/ROADMAP.md`
- `docs/PLAN.md`
- `docs/CONTEXT.md`
- `docs/USER_STORIES.md`

#### Commit de Code Associé
```text
docs(roadmap): establish roadmap, operational plan, context and user stories

- Outline 7-phase strategic roadmap from MVP to Cloud
- Provide operational plan mapping tasks P0-T1 through P2-T4
- Map every user story to atomic conventional commits

Refs: US-P0-07
```

---

## 4. Epic 1 : Phase 1 — MVP du Noyau & Démonstration

---

### US-001 : Initialisation du Squelette Minimal et Configuration du Core

- **Identifiant :** `US-001` (Plan : `P1-T1`)
- **Persona :** `SysAdmin` / `PluginDev`
- **Énoncé :**
  > En tant que **développeur du système**,  
  > Je souhaite **initialiser un squelette Laravel minimal avec la configuration du Core CMS (`config/cms.php`) et son ServiceProvider principal**,  
  > Afin de **disposer d'un socle d'exécution propre, strictement débarrassé de toute logique métier prédéfinie**.

#### Critères d'Acceptation (Given / When / Then)
```gherkin
Given un environnement PHP 8.2+ propre avec Composer
When l'application Laravel démarre
Then CoreServiceProvider est enregistré dans bootstrap/app.php
And la configuration config/cms.php est accessible via config('cms')
And aucune route, modèle ou vue métier n'est présente dans app/
```

#### Fichiers Impactés
- `composer.json`
- `bootstrap/app.php`
- `config/cms.php`
- `app/Core/CoreServiceProvider.php`
- `tests/Unit/CoreServiceProviderTest.php`

#### Commit de Code Associé
```text
chore(core): initialize minimal laravel skeleton and core service provider

- Add minimal config/cms.php for paths and discovery flags
- Register CoreServiceProvider in bootstrap lifecycle
- Ensure empty application boots without any business domain logic

Refs: US-001
```

---

### US-002 : Définition des Contrats Publics d'Extension

- **Identifiant :** `US-002` (Plan : `P1-T2`)
- **Persona :** `PluginDev`
- **Énoncé :**
  > En tant que **développeur de plugin**,  
  > Je souhaite **m'appuyer sur des interfaces PHP strictes et typées dans `app/Core/Contracts`**,  
  > Afin de **garantir la pérennité et la compatibilité de mes extensions sans dépendre des détails internes du noyau**.

#### Critères d'Acceptation
```gherkin
Given le namespace app/Core/Contracts/
When j'examine les interfaces PluginInterface, PluginManifestInterface et PluginLifecycleListenerInterface
Then chaque méthode dispose d'un typage strict de ses paramètres et de son retour
And aucune classe concrète du noyau n'est importée dans ces interfaces
And PHPStan niveau 8 valide l'ensemble des contrats sans erreur
```

#### Fichiers Impactés
- `app/Core/Contracts/PluginInterface.php`
- `app/Core/Contracts/PluginManifestInterface.php`
- `app/Core/Contracts/PluginLifecycleListenerInterface.php`
- `tests/Architecture/ContractsArchitectureTest.php`

#### Commit de Code Associé
```text
feat(contracts): implement strict public interfaces for plugins

- Add PluginInterface, PluginManifestInterface, PluginLifecycleListenerInterface
- Enforce strict typing and zero coupling to concrete core implementations
- Add architecture tests verifying contract purity

Refs: US-002
```

---

### US-003 : Validation Stricte et Sécurisée des Manifestes `plugin.json`

- **Identifiant :** `US-003` (Plan : `P1-T3`)
- **Persona :** `SuperAdmin` / `SysAdmin`
- **Énoncé :**
  > En tant que **gestionnaire du CMS**,  
  > Je souhaite **valider rigoureusement le fichier `plugin.json` de chaque extension via un schéma strict et un contrôle anti-traversée de répertoire**,  
  > Afin d'**empêcher tout chargement de manifeste corrompu ou tentative d'exécution de chemin malveillant (`../`)**.

#### Critères d'Acceptation
```gherkin
Given un fichier plugin.json contenant un JSON syntaxiquement invalide ou sans identifiant requis
When ManifestValidator::validate() est appelé
Then une exception InvalidManifestException typée est levée avec un message explicite

Given un manifeste contenant un chemin d'autoloading ou de route avec "../"
When ManifestValidator::validate() est exécuté
Then une tentative de Path Traversal est détectée et l'enregistrement est refusé

Given un manifeste valide respectant l'identifiant regex "^[a-z0-9_-]+\.[a-z0-9_-]+$" et SemVer
When ManifestValidator::validate() est exécuté
Then une instance immuable PluginManifest est retournée avec succès
```

#### Fichiers Impactés
- `app/Core/PluginDiscovery/ManifestValidator.php`
- `app/Core/PluginDiscovery/DTO/PluginManifest.php`
- `app/Core/PluginDiscovery/Exceptions/InvalidManifestException.php`
- `tests/Unit/ManifestValidatorTest.php`

#### Commit de Code Associé
```text
feat(manifest): add ManifestValidator with schema checks and path traversal protection

- Validate JSON manifest structure against required SemVer and ID conventions
- Block directory traversal sequences (../) in providers and route paths
- Provide immutable PluginManifest DTO and dedicated unit test suite

Refs: US-003
```

---

### US-004 : Découverte Automatique des Extensions sur le Disque

- **Identifiant :** `US-004` (Plan : `P1-T4`)
- **Persona :** `SuperAdmin` / `PluginDev`
- **Énoncé :**
  > En tant que **développeur ou administrateur**,  
  > Je souhaite **déposer un dossier de plugin dans `platform/plugins/` et que le noyau le découvre automatiquement tout en injectant son autoloading PSR-4**,  
  > Afin de **rendre l'extension disponible sans modifier manuellement le `composer.json` racine**.

#### Critères d'Acceptation
```gherkin
Given un ou plusieurs plugins conformes déposés dans platform/plugins/
When PluginDiscovery::discover() est invoqué
Then tous les manifestes valides sont recensés sous forme de collection
And les préfixes d'autoloading PSR-4 déclarés sont dynamiquement enregistrés auprès de Composer
And les répertoires sans manifeste ou invalides sont ignorés et journalisés
```

#### Fichiers Impactés
- `app/Core/PluginDiscovery/PluginDiscovery.php`
- `app/Core/PluginDiscovery/Autoload/PluginClassLoader.php`
- `tests/Unit/PluginDiscoveryTest.php`

#### Commit de Code Associé
```text
feat(discovery): implement filesystem plugin discovery and dynamic psr-4 autoloading

- Scan platform/plugins/ directory for valid manifests
- Register plugin PSR-4 autoload mappings dynamically at runtime
- Safely log and skip unreadable or non-compliant directories

Refs: US-004
```

---

### US-005 : Registre Persistant et Machine à 5 États du Cycle de Vie

- **Identifiant :** `US-005` (Plan : `P1-T5`)
- **Persona :** `SuperAdmin`
- **Énoncé :**
  > En tant que **super-administrateur**,  
  > Je souhaite **gérer l'état de chaque plugin (`AVAILABLE`, `INSTALLED`, `ACTIVE`, `DISABLED`, `ERROR`) de manière persistante**,  
  > Afin de **contrôler exactement quel code est exécuté lors du boot de l'application**.

#### Critères d'Acceptation
```gherkin
Given un plugin à l'état AVAILABLE
When l'administrateur demande son installation
Then son état passe à INSTALLED et est persisté en base dans cms_plugins

Given un plugin à l'état INSTALLED
When l'administrateur demande son activation
Then ses ServiceProviders sont instanciés et son état devient ACTIVE

Given un plugin à l'état ACTIVE
When l'administrateur demande sa désactivation
Then son état passe à DISABLED et ses routes cessent d'être chargées au prochain boot

Given un plugin qui lève une exception non interceptée au boot
Then son état bascule automatiquement à ERROR et il est mis en quarantaine
```

#### Fichiers Impactés
- `database/migrations/2026_10_13_000001_create_cms_plugins_table.php`
- `app/Core/PluginRegistry/Models/PluginRecord.php`
- `app/Core/PluginRegistry/PluginRegistry.php`
- `app/Core/PluginManager/PluginManager.php`
- `app/Core/Lifecycle/PluginState.php`
- `tests/Unit/PluginManagerLifecycleTest.php`

#### Commit de Code Associé
```text
feat(lifecycle): implement persistent PluginRegistry and 5-state lifecycle machine

- Create cms_plugins table for persisting extension states
- Implement PluginManager coordinating transitions (AVAILABLE -> INSTALLED -> ACTIVE -> DISABLED -> ERROR)
- Add quarantine isolation upon unhandled boot exception

Refs: US-005
```

---

### US-006 : Gestionnaire de Migrations Isolées par Plugin

- **Identifiant :** `US-006` (Plan : `P1-T6`)
- **Persona :** `PluginDev` / `SuperAdmin`
- **Énoncé :**
  > En tant que **développeur de plugin**,  
  > Je souhaite **embarquer des migrations SQL dédiées dans `database/migrations/` de mon extension et qu'elles soient tracées séparément dans `cms_plugin_migrations`**,  
  > Afin que **les tables de mon plugin soient créées à l'installation sans jamais être supprimées par erreur lors d'une simple désactivation**.

#### Critères d'Acceptation
```gherkin
Given un plugin possédant une migration créant la table plugin_demo_messages
When le plugin passe à l'état INSTALLED
Then la migration est exécutée et enregistrée dans cms_plugin_migrations
And la table plugin_demo_messages existe dans la base

Given un plugin actif
When le plugin est désactivé (état DISABLED)
Then aucun rollback n'est exécuté et la table plugin_demo_messages est intégralement conservée
```

#### Fichiers Impactés
- `database/migrations/2026_10_13_000002_create_cms_plugin_migrations_table.php`
- `app/Core/Lifecycle/PluginMigrationManager.php`
- `tests/Feature/PluginMigrationManagerTest.php`

#### Commit de Code Associé
```text
feat(migrations): add isolated PluginMigrationManager and cms_plugin_migrations tracking

- Maintain separated tracking table distinct from Laravel core migrations
- Run plugin migrations upon installation within transactional boundaries
- Ensure non-destructive preservation of database tables on plugin deactivation

Refs: US-006
```

---

### US-007 : Panneau d'Administration Minimal et Commandes Artisan

- **Identifiant :** `US-007` (Plan : `P1-T7`)
- **Persona :** `SuperAdmin` / `SysAdmin`
- **Énoncé :**
  > En tant que **super-administrateur ou exploitant**,  
  > Je souhaite **lister, activer et désactiver les plugins via un tableau de bord web sécurisé (`/admin`) et via la console Artisan**,  
  > Afin de **piloter la plateforme facilement et de pouvoir intervenir en ligne de commande en cas d'urgence**.

#### Critères d'Acceptation
```gherkin
Given un compte administrateur authentifié
When j'accède à l'URL /admin/plugins
Then la liste des plugins découverts avec leur statut exact est affichée
And chaque action d'activation/désactivation est protégée par un token CSRF et une autorisation serveur

Given le terminal serveur
When j'exécute "php artisan cms:plugin:list"
Then un tableau CLI présente l'identifiant, la version et l'état de chaque plugin

When j'exécute "php artisan cms:recovery:safe-mode --on"
Then tous les plugins tiers sont immédiatement bypassés au boot
```

#### Fichiers Impactés
- `platform/core/Http/Controllers/Admin/PluginController.php`
- `platform/core/resources/views/admin/plugins/index.blade.php`
- `app/Core/Console/Commands/PluginListCommand.php`
- `app/Core/Console/Commands/PluginEnableCommand.php`
- `app/Core/Console/Commands/PluginDisableCommand.php`
- `app/Core/Console/Commands/SafeModeCommand.php`
- `tests/Feature/AdminPluginManagementHttpTest.php`
- `tests/Feature/ArtisanPluginCommandsTest.php`

#### Commit de Code Associé
```text
feat(admin): provide minimal blade admin panel and artisan management commands

- Add protected /admin/plugins GUI with CSRF validation
- Add artisan commands: cms:plugin:list, cms:plugin:enable, cms:plugin:disable
- Add cms:recovery:safe-mode CLI bypass for system administrators

Refs: US-007
```

---

### US-008 : Plugin de Démonstration Minimal (`demo-hello`)

- **Identifiant :** `US-008` (Plan : `P1-T8`)
- **Persona :** `PluginDev` / `SuperAdmin`
- **Énoncé :**
  > En tant que **développeur**,  
  > Je souhaite **créer une extension de référence `platform/plugins/demo-hello/` démontrant le montage de routes, de vues et d'une table SQL**,  
  > Afin de **prouver le fonctionnement end-to-end de la machine d'extension du CMS**.

#### Critères d'Acceptation
```gherkin
Given le plugin "acme.demo-hello" dans platform/plugins/demo-hello/
When le plugin est à l'état ACTIVE
Then la requête GET /demo/hello retourne un code HTTP 200 avec le message de démonstration
And les données proviennent de la table "plugin_demo_messages"

When le plugin est désactivé (DISABLED)
Then la requête GET /demo/hello retourne un code HTTP 404 Not Found
And les tables restent intactes en base
```

#### Fichiers Impactés
- `platform/plugins/demo-hello/plugin.json`
- `platform/plugins/demo-hello/src/Providers/DemoHelloServiceProvider.php`
- `platform/plugins/demo-hello/src/Http/Controllers/HelloController.php`
- `platform/plugins/demo-hello/database/migrations/2026_10_13_000010_create_plugin_demo_messages_table.php`
- `platform/plugins/demo-hello/resources/views/hello.blade.php`
- `platform/plugins/demo-hello/routes/web.php`

#### Commit de Code Associé
```text
feat(demo): introduce demo-hello reference plugin with isolated routes and migration

- Implement demo-hello plugin according to public core contracts
- Add isolated routes/web.php mounted exclusively when plugin is ACTIVE
- Add dedicated migration and Blade view under plugin namespace

Refs: US-008
```

---

### US-009 : Garde-Fous d'Architecture et Suite Complète de Tests MVP

- **Identifiant :** `US-009` (Plan : `P1-T9` & `P1-T10`)
- **Persona :** `SysAdmin` / `PluginDev`
- **Énoncé :**
  > En tant qu'**architecte logiciel**,  
  > Je souhaite **exécuter une suite de tests unitaires, fonctionnels et d'architecture automatisés**,  
  > Afin de **prouver mathématiquement qu'aucune fuite de dépendance n'existe et valider officiellement la fin de la Phase 1**.

#### Critères d'Acceptation
```gherkin
Given l'ensemble du code source de la Phase 1
When la suite de tests est lancée via "php artisan test"
Then 100% des tests unitaires et fonctionnels passent au vert
And les tests d'architecture vérifient qu'app/Core n'importe aucune classe de platform/plugins/
And l'analyse statique PHPStan niveau 8 ne renvoie aucune erreur
And Laravel Pint ne relève aucun écart de formatage
```

#### Fichiers Impactés
- `tests/Architecture/CoreBoundariesTest.php`
- `tests/Feature/CompleteMvpLifecycleTest.php`
- `phpstan.neon`
- `pint.json`

#### Commit de Code Associé
```text
test(mvp): add architecture boundaries tests and end-to-end lifecycle verification

- Implement Pest / PHPUnit architecture tests preventing core-to-plugin coupling
- Add comprehensive end-to-end lifecycle integration test
- Configure PHPStan level 8 and Pint rules for continuous validation

Refs: US-009
```

---

## 4. Epic 2 : Phase 2 — Stabilisation, Dépendances & Résilience

---

### US-010 : Tri Topologique et Détection des Dépendances Circulaires

- **Identifiant :** `US-010` (Plan : `P2-T1`)
- **Persona :** `PluginDev` / `SuperAdmin`
- **Énoncé :**
  > En tant que **gestionnaire du CMS**,  
  > Je souhaite **ordonner automatiquement le chargement des plugins via un tri topologique (Algorithme de Kahn) et détecter toute dépendance cyclique ou manquante**,  
  > Afin d'**éviter les blocages de démarrage et les ordres d'enregistrement instables**.

#### Critères d'Acceptation
```gherkin
Given un plugin A dépendant de B, et B dépendant de C
When le résolveur de dépendances calcule l'ordre de chargement
Then les plugins sont enregistrés dans l'ordre strict [C, B, A]

Given deux plugins A et B ayant une dépendance mutuelle circulaire (A -> B et B -> A)
When l'activation est demandée
Then une CircularDependencyException est levée et l'activation est refusée
And un message clair liste la boucle de dépendance identifiée
```

#### Fichiers Impactés
- `app/Core/DependencyResolver/DependencyResolver.php`
- `app/Core/DependencyResolver/Exceptions/CircularDependencyException.php`
- `app/Core/DependencyResolver/Exceptions/MissingDependencyException.php`
- `tests/Unit/DependencyResolverTest.php`

#### Commit de Code Associé
```text
feat(resolver): implement Kahn topological dependency resolver and cycle detection

- Build directed acyclic graph (DAG) of active plugins
- Determine deterministic boot order using Kahn's algorithm
- Reject circular dependencies with clear diagnostics

Refs: US-010
```

---

### US-011 : Bus de Hooks Extensible (Actions et Filtres Priorisés)

- **Identifiant :** `US-011` (Plan : `P2-T2`)
- **Persona :** `PluginDev`
- **Énoncé :**
  > En tant que **développeur de plugin**,  
  > Je souhaite **m'abonner à des actions (`doAction`) ou filtrer des flux de données (`applyFilter`) avec un système de priorités numériques**,  
  > Afin d'**enrichir les comportements du CMS sans jamais modifier le code source du noyau ni des autres plugins**.

#### Critères d'Acceptation
```gherkin
Given une action enregistrée avec priorité 20 et une autre avec priorité 10
When doAction('nom.du.hook', $payload) est exécuté
Then le hook de priorité 10 est exécuté avant celui de priorité 20

Given un filtre qui modifie une chaîne de caractères
When applyFilter('content.title', $title) est appelé
Then la valeur retournée est celle issue de la chaîne ordonnée des filtres enregistrés
```

#### Fichiers Impactés
- `app/Core/Contracts/HookSubscriberInterface.php`
- `app/Core/Hooks/HookManager.php`
- `app/Core/Hooks/Facades/Hook.php`
- `tests/Unit/HookManagerTest.php`

#### Commit de Code Associé
```text
feat(hooks): implement prioritized Action and Filter HookManager

- Add doAction and applyFilter execution mechanics with numeric priorities
- Provide HookSubscriberInterface for clean dependency injection in plugins
- Add comprehensive unit test suite for execution order and mutation flow

Refs: US-011
```

---

### US-012 : Tolérance aux Crashs et Mode de Récupération (*Emergency Safe Mode*)

- **Identifiant :** `US-012` (Plan : `P2-T3`)
- **Persona :** `SysAdmin` / `SuperAdmin`
- **Énoncé :**
  > En tant qu'**administrateur système**,  
  > Je souhaite **qu'un plugin défaillant levant une exception critique au démarrage soit immédiatement mis en quarantaine sans bloquer l'accès au panneau de contrôle**,  
  > Afin de **garantir la disponibilité continue du service et de pouvoir désactiver le module fautif sans modifier la base manuellement**.

#### Critères d'Acceptation
```gherkin
Given un plugin actif dont le ServiceProvider lève une exception fatale dans boot()
When une requête HTTP survient
Then l'exception est interceptée par le noyau
And le plugin coupable passe à l'état ERROR avec enregistrement de la stack trace
And la requête aboutit normalement sur le panneau d'administration avec un avertissement visible

Given le Safe Mode activé via variable d'environnement ou drapeau cache
When n'importe quelle requête HTTP est exécutée
Then aucun plugin tiers n'est chargé et seul le noyau est opérationnel
```

#### Fichiers Impactés
- `app/Core/Recovery/SafeModeManager.php`
- `app/Core/Recovery/PluginQuarantineHandler.php`
- `database/migrations/2026_10_13_000003_create_cms_plugin_errors_table.php`
- `tests/Feature/SafeModeAndQuarantineTest.php`

#### Commit de Code Associé
```text
feat(recovery): introduce crash tolerance quarantine and emergency safe mode

- Catch fatal exceptions occurring during plugin registration and boot
- Transition offending plugin to ERROR state and log details to cms_plugin_errors
- Ensure admin dashboard remains accessible even during third-party plugin failures

Refs: US-012
```

---

### US-013 : Registre Dynamique des Permissions et Entrées de Menu Admin

- **Identifiant :** `US-013` (Plan : `P2-T4`)
- **Persona :** `PluginDev` / `SuperAdmin`
- **Énoncé :**
  > En tant que **développeur de plugin**,  
  > Je souhaite **déclarer des éléments de navigation pour l'administration et des permissions granulaires via mon manifeste ou un contrat**,  
  > Afin que **mon interface d'administration soit harmonieusement intégrée au tableau de bord général**.

#### Critères d'Acceptation
```gherkin
Given un plugin actif déclarant une navigation vers sa page de configuration
When le super-administrateur visite /admin
Then le menu latéral affiche le lien du plugin avec son icône et son intitulé

Given un plugin désactivé
When le menu admin est affiché
Then ses entrées de navigation sont automatiquement masquées
```

#### Fichiers Impactés
- `app/Core/Contracts/NavigationItemContract.php`
- `app/Core/Permissions/PermissionRegistry.php`
- `app/Core/Admin/NavigationRegistry.php`
- `platform/core/resources/views/admin/layouts/sidebar.blade.php`
- `tests/Feature/AdminNavigationRegistryTest.php`

#### Commit de Code Associé
```text
feat(navigation): add dynamic admin navigation and permission registry

- Implement NavigationRegistry collecting menu items from active plugins
- Register custom plugin permissions declared in manifests
- Ensure navigation items are automatically purged upon plugin deactivation

Refs: US-013
```

---

## 4. Spécification Détaillée des User Stories — Phase 3 (Plugins Fondamentaux & Assets)

### US-014 : Gestionnaire d'Actifs Statiques de Plugins (`PluginAssetManager`)

- **Identifiant :** `US-014` (Plan : `P3-T1`)
- **Persona :** `PluginDev` / `SysAdmin`
- **Énoncé :**
  > En tant que **développeur de plugin**,  
  > Je souhaite **publier ou servir des assets statiques (CSS, JS, SVG, images) depuis le répertoire de mon plugin vers `public/vendor/plugins/{plugin_id}` de manière isolée**,  
  > Afin que **mon extension fournisse ses propres styles et scripts sans nécessiter de compilation globale Node/Vite dans le noyau**.

#### Critères d'Acceptation
```gherkin
Given un plugin avec un dossier resources/assets/ contenant du CSS ou JS
When la commande cms:plugin:publish-assets {plugin_id} ou le cycle d'activation est exécuté
Then les assets sont copiés ou liés dans public/vendor/plugins/{plugin_id}
And le helper PluginAssetManager::url($pluginId, $path) génère l'URL publique absolue valide
And lors de la désinstallation avec purge, le dossier d'assets publié est automatiquement nettoyé
```

#### Fichiers Impactés
- `app/Core/Assets/PluginAssetManager.php`
- `app/Core/Console/Commands/PluginPublishAssetsCommand.php`
- `app/Core/CoreServiceProvider.php`
- `tests/Feature/PluginAssetManagerTest.php`

#### Commit de Code Associé
```text
feat(assets): implement isolated PluginAssetManager and asset publishing command

- Add PluginAssetManager copying plugin assets to public/vendor/plugins/{id}
- Provide cms:plugin:publish-assets console command with symlink and copy support
- Clean up published assets automatically upon plugin uninstallation

Refs: US-014
```

---

### US-015 : Contrat MediaStorageInterface et Plugin Médiathèque (`acme.media-library`)

- **Identifiant :** `US-015` (Plan : `P3-T2`)
- **Persona :** `PluginDev` / `ContentEditor`
- **Énoncé :**
  > En tant que **développeur ou éditeur de contenu**,  
  > Je souhaite **téléverser, inspecter et référencer des fichiers multimédias via un contrat abstrait `MediaStorageInterface` et le plugin autonome `acme.media-library`**,  
  > Afin de **gérer une bibliothèque de médias centralisée sans qu'aucun code média ne soit codé en dur dans le noyau**.

#### Critères d'Acceptation
```gherkin
Given le contrat App\Core\Contracts\MediaStorageInterface
When le plugin acme.media-library est activé
Then sa migration crée la table isolée plugin_media_items
And le contrôleur permet d'uploader un fichier sous contrainte MIME sécurisée
And les métadonnées (nom, type MIME, taille, URL) sont persistées et accessibles
And une entrée de navigation "Médiathèque" est visible dans le panneau d'administration
```

#### Fichiers Impactés
- `app/Core/Contracts/MediaStorageInterface.php`
- `platform/plugins/acme.media-library/plugin.json`
- `platform/plugins/acme.media-library/src/Providers/MediaLibraryServiceProvider.php`
- `platform/plugins/acme.media-library/src/Models/MediaItem.php`
- `platform/plugins/acme.media-library/src/Services/MediaStorageService.php`
- `platform/plugins/acme.media-library/src/Http/Controllers/MediaController.php`
- `platform/plugins/acme.media-library/database/migrations/2026_10_14_000001_create_plugin_media_items_table.php`
- `platform/plugins/acme.media-library/routes/web.php`
- `tests/Feature/Plugins/MediaLibraryPluginTest.php`

#### Commit de Code Associé
```text
feat(media): implement MediaStorageInterface and acme.media-library plugin

- Introduce MediaStorageInterface contract in core contracts
- Implement acme.media-library with isolated plugin_media_items table
- Provide secure upload, metadata inspection, and admin media gallery

Refs: US-015
```

---

### US-016 : Plugin Fondamental de Pages Statiques (`acme.pages`)

- **Identifiant :** `US-016` (Plan : `P3-T3`)
- **Persona :** `ContentEditor` / `SiteVisitor`
- **Énoncé :**
  > En tant qu'**éditeur de contenu**,  
  > Je souhaite **créer et publier des pages web avec un titre, un slug, un contenu HTML/Markdown et un statut (brouillon/publié)**,  
  > Afin d'**afficher des pages publiques sur le CMS via des URLs personnalisées**.

#### Critères d'Acceptation
```gherkin
Given le plugin acme.pages actif
When une page est créée avec le slug about-us et le statut published
Then la requête HTTP GET /page/about-us renvoie 200 avec le contenu de la page
When une page est en état draft
Then la requête publique renvoie 404
When le plugin acme.pages est désactivé
Then les routes de pages renvoient 404 sans perte de données dans la table plugin_pages
```

#### Fichiers Impactés
- `platform/plugins/acme.pages/plugin.json`
- `platform/plugins/acme.pages/src/Providers/PagesServiceProvider.php`
- `platform/plugins/acme.pages/src/Models/Page.php`
- `platform/plugins/acme.pages/src/Http/Controllers/PageController.php`
- `platform/plugins/acme.pages/src/Http/Controllers/AdminPageController.php`
- `platform/plugins/acme.pages/database/migrations/2026_10_14_000002_create_plugin_pages_table.php`
- `platform/plugins/acme.pages/resources/views/page.blade.php`
- `platform/plugins/acme.pages/routes/web.php`
- `tests/Feature/Plugins/PagesPluginTest.php`

#### Commit de Code Associé
```text
feat(pages): implement acme.pages plugin with dynamic routing and front template

- Add Page model with slug, published status, and content
- Provide front /page/{slug} controller and Blade template
- Integrate admin management routes and preserve data upon deactivation

Refs: US-016
```

---

### US-017 : Plugin Fondamental de Menus de Navigation (`acme.menus`)

- **Identifiant :** `US-017` (Plan : `P3-T4`)
- **Persona :** `SiteVisitor` / `ContentEditor`
- **Énoncé :**
  > En tant qu'**administrateur et visiteur du site**,  
  > Je souhaite **configurer des menus de navigation personnalisés et les injecter dans les vues via un helper ou hook de rendu**,  
  > Afin d'**organiser la navigation du site de manière dynamique et hiérarchique**.

#### Critères d'Acceptation
```gherkin
Given le plugin acme.menus actif
When un menu main-nav est créé avec des liens ordonnés
Then le service MenuRenderer::render('main-nav') génère le markup HTML du menu
And les entrées sont stockées dans les tables isolées plugin_menus et plugin_menu_items
And les menus sont automatiquement masqués si le plugin est désactivé
```

#### Fichiers Impactés
- `platform/plugins/acme.menus/plugin.json`
- `platform/plugins/acme.menus/src/Providers/MenusServiceProvider.php`
- `platform/plugins/acme.menus/src/Models/Menu.php`
- `platform/plugins/acme.menus/src/Models/MenuItem.php`
- `platform/plugins/acme.menus/src/Services/MenuRenderer.php`
- `platform/plugins/acme.menus/database/migrations/2026_10_14_000003_create_plugin_menus_table.php`
- `platform/plugins/acme.menus/routes/web.php`
- `tests/Feature/Plugins/MenusPluginTest.php`

#### Commit de Code Associé
```text
feat(menus): implement acme.menus hierarchical navigation plugin and render hook

- Provide Menu and MenuItem models with ordering and nesting
- Implement MenuRenderer service generating clean HTML navigation markup
- Register admin menu editor and ensure route isolation

Refs: US-017
```

---

### US-018 : Plugin SEO et Métadonnées via Bus de Filtres Hook (`acme.seo`)

- **Identifiant :** `US-018` (Plan : `P3-T5`)
- **Persona :** `PluginDev` / `ContentEditor`
- **Énoncé :**
  > En tant qu'**expert SEO**,  
  > Je souhaite **injecter automatiquement des balises meta (title, description, canonical, OpenGraph) dans le `<head>` des pages via le filtre Hook `cms.head`**,  
  > Afin d'**optimiser le référencement naturel du CMS sans jamais modifier les vues du noyau ni forcer une dépendance dure sur les autres plugins**.

#### Critères d'Acceptation
```gherkin
Given le plugin acme.seo actif s'abonnant au filtre cms.head
When une page publique est rendue
Then le bus de hooks injecte meta description, og:title et canonical link
And les paramètres par défaut du site sont configurables
When le plugin acme.seo est désactivé
Then le filtre est retiré et le <head> est rendu sans les balises SEO injectées
```

#### Fichiers Impactés
- `platform/plugins/acme.seo/plugin.json`
- `platform/plugins/acme.seo/src/Providers/SeoServiceProvider.php`
- `platform/plugins/acme.seo/src/Subscribers/SeoHookSubscriber.php`
- `platform/plugins/acme.seo/src/Services/SeoMetaManager.php`
- `platform/plugins/acme.seo/database/migrations/2026_10_14_000004_create_plugin_seo_settings_table.php`
- `tests/Feature/Plugins/SeoPluginTest.php`

#### Commit de Code Associé
```text
feat(seo): implement acme.seo plugin leveraging hook filter bus for head metadata

- Provide SeoMetaManager generating meta tags, canonical, and OpenGraph markup
- Register SeoHookSubscriber filtering cms.head hook dynamically
- Maintain zero hard dependency between SEO and other content plugins

Refs: US-018
```

---

## 5. Epic 4 : Phase 4 — Moteur de Thèmes & Rendu Découplé

---

### US-019 : Contrats Publics de Thèmes, Manifeste `theme.json` et Découverte

- **Identifiant :** `US-019` (Plan : `P4-T1`)
- **Persona :** `ThemeDev` / `SysAdmin`
- **Énoncé :**
  > En tant que **développeur de thème**,  
  > Je souhaite **déclarer un thème via un manifeste strict `theme.json` et des contrats d'interface publics (`ThemeInterface`, `ThemeManifestInterface`)**,  
  > Afin que **le CMS découvre automatiquement les thèmes installés dans `platform/themes/` avec validation de schéma et protection anti-traversal**.

#### Critères d'Acceptation
```gherkin
Given un dossier de thème dans platform/themes/{theme_id} avec theme.json
When le service ThemeDiscovery analyse le répertoire des thèmes
Then le manifeste est validé contre toute tentative de traversée de répertoire (path traversal)
And une instance de ThemeManifest est instanciée avec nom, version, description et auteur
And les thèmes découverts sont accessibles via ThemeManager
```

#### Fichiers Impactés
- `app/Core/Contracts/ThemeInterface.php`
- `app/Core/Contracts/ThemeManifestInterface.php`
- `app/Core/Themes/DTO/ThemeManifest.php`
- `app/Core/Themes/ThemeDiscovery.php`
- `tests/Unit/Themes/ThemeDiscoveryTest.php`

#### Commit de Code Associé
```text
feat(themes): implement ThemeInterface, ThemeManifest DTO, and ThemeDiscovery service

- Define ThemeInterface and ThemeManifestInterface in Core contracts
- Introduce immutable ThemeManifest DTO with strict path traversal checks
- Implement ThemeDiscovery scanning platform/themes/ directory safely

Refs: US-019
```

---

### US-020 : Gestionnaire de Thèmes, Persistance d'Activation et Surcharge de Vues (View Overriding)

- **Identifiant :** `US-020` (Plan : `P4-T2`)
- **Persona :** `SuperAdmin` / `ThemeDev`
- **Énoncé :**
  > En tant qu'**administrateur et développeur de thème**,  
  > Je souhaite **activer un thème unique enregistré dans la table `cms_themes` qui surcharge prioritairement les vues Blade des plugins sans jamais altérer leurs fichiers sources**,  
  > Afin de **personnaliser le rendu visuel du CMS de manière totalement modulaire et réversible**.

#### Critères d'Acceptation
```gherkin
Given un thème actif et un plugin acme.pages actif
When le thème fournit un template views/plugins/pages/page.blade.php
Then l'appel view('pages::page') rend la vue personnalisée du thème prioritaire
When aucun template de surcharge n'est fourni par le thème
Then le moteur de vue bascule automatiquement (fallback) sur la vue d'origine du plugin
When le thème est désactivé
Then le rendu standard du plugin est immédiatement restauré
```

#### Fichiers Impactés
- `database/migrations/2026_10_15_000001_create_cms_themes_table.php`
- `app/Core/Themes/ThemeManager.php`
- `tests/Feature/Themes/ThemeManagerTest.php`

#### Commit de Code Associé
```text
feat(themes): implement ThemeManager with view overriding hierarchy and persistence

- Add cms_themes migration tracking active theme state
- Implement ThemeManager with unique active theme constraint
- Register view override path prepending theme templates over plugin views

Refs: US-020
```

---

### US-021 : Gestionnaire d'Assets de Thème et Commande de Publication

- **Identifiant :** `US-021` (Plan : `P4-T3`)
- **Persona :** `SysAdmin` / `ThemeDev`
- **Énoncé :**
  > En tant que **développeur de thème et administrateur système**,  
  > Je souhaite **publier les ressources statiques (CSS, JS, images, polices) du thème actif vers le dossier public sans compilation globale**,  
  > Afin de **servir les fichiers statiques de présentation de manière performante**.

#### Critères d'Acceptation
```gherkin
Given un thème avec un dossier resources/assets/ contenant des styles ou scripts
When la commande cms:theme:publish-assets {theme_id?} ou l'activation d'un thème est exécutée
Then les assets sont copiés ou liés dans public/vendor/themes/{theme_id}
And le helper ThemeAssetManager::url($themeId, $path) génère l'URL publique absolue valide
And lors du changement de thème, les anciens assets peuvent être nettoyés
```

#### Fichiers Impactés
- `app/Core/Themes/ThemeAssetManager.php`
- `app/Core/Console/Commands/ThemePublishAssetsCommand.php`
- `tests/Feature/Themes/ThemeAssetManagerTest.php`

#### Commit de Code Associé
```text
feat(themes): introduce ThemeAssetManager and theme assets publishing command

- Implement ThemeAssetManager publishing assets to public/vendor/themes/{id}
- Add cms:theme:publish-assets console command with symlink and force support
- Provide asset url resolution helper for active theme

Refs: US-021
```

---

### US-022 : Thème de Référence Découplé `platform/themes/default-modern`

- **Identifiant :** `US-022` (Plan : `P4-T4`)
- **Persona :** `SiteVisitor` / `ContentEditor`
- **Énoncé :**
  > En tant que **visiteur et éditeur du site**,  
  > Je souhaite **bénéficier d'un thème par défaut moderne, responsive et accessible intégrant la navigation hiérarchique, le rendu des pages et les métadonnées SEO**,  
  > Afin de **disposer immédiatement d'un front-office complet et élégant sans configuration complexe**.

#### Critères d'Acceptation
```gherkin
Given le thème default-modern actif ainsi que les plugins pages, menus et seo
When un utilisateur visite une page publique /page/{slug}
Then le layout principal du thème est rendu avec header, footer et navigation
And le bus de hooks injecte les métadonnées SEO dans le <head>
And le menu de navigation est généré dynamiquement
And les styles CSS du thème default-modern sont appliqués
```

#### Fichiers Impactés
- `platform/themes/default-modern/theme.json`
- `platform/themes/default-modern/resources/views/layouts/app.blade.php`
- `platform/themes/default-modern/resources/views/plugins/pages/page.blade.php`
- `platform/themes/default-modern/resources/assets/css/theme.css`
- `tests/Feature/Themes/DefaultModernThemeTest.php`

#### Commit de Code Associé
```text
feat(themes): provide default-modern reference theme with responsive layout and plugin overrides

- Create default-modern theme with theme.json manifest and css assets
- Add clean Blade app layout integrating cms.head and dynamic menus
- Provide page template override with modern responsive styling

Refs: US-022
```

---

### US-023 : Commandes CLI et Interface d'Administration des Thèmes

- **Identifiant :** `US-023` (Plan : `P4-T5`)
- **Persona :** `SuperAdmin` / `SysAdmin`
- **Énoncé :**
  > En tant qu'**administrateur du CMS**,  
  > Je souhaite **lister, prévisualiser et activer les thèmes via des commandes Artisan et un écran d'administration dédié `/admin/themes`**,  
  > Afin de **contrôler l'apparence du site en toute sécurité avec validation CSRF et contrôle d'accès**.

#### Critères d'Acceptation
```gherkin
Given un compte administrateur authentifié
When l'administrateur accède à /admin/themes
Then la liste des thèmes disponibles et actifs est affichée avec leurs métadonnées
And un bouton permet d'activer un thème instantanément
When la commande cms:theme:list est exécutée en CLI
Then la table des thèmes s'affiche avec le statut Actif/Disponible
When la commande cms:theme:activate {id} est exécutée
Then le thème est activé et ses assets sont publiés automatiquement
```

#### Fichiers Impactés
- `app/Core/Console/Commands/ThemeListCommand.php`
- `app/Core/Console/Commands/ThemeActivateCommand.php`
- `app/Core/Themes/Http/Controllers/AdminThemeController.php`
- `platform/core/resources/views/themes/index.blade.php`
- `tests/Feature/Themes/AdminThemeTest.php`

#### Commit de Code Associé
```text
feat(themes): provide artisan theme management commands and admin themes panel

- Add cms:theme:list and cms:theme:activate CLI commands
- Provide admin themes controller, route, and Blade management panel
- Register themes-management entry in administration navigation registry

Refs: US-023
```

---

## 6. Matrice de Traçabilité Synthétique

| Identifiant | Intitulé | Phase | Dépendances | Message Commit Associé |
|---|---|---|---|---|
| **US-P0-01** | Environnement Git & Règles .gitignore | Phase 0 | Aucune | `chore(project): initialize git repository with standard ignore rules` |
| **US-P0-02** | Directives Permanentes & Gouvernance IA | Phase 0 | US-P0-01 | `docs(agents): define engineering rules and agent directives for plugin-first cms` |
| **US-P0-03** | Architecture Cible & ADRs | Phase 0 | US-P0-02 | `docs(architecture): define modular monolith architecture and initial adrs` |
| **US-P0-04** | Système de Plugins & Machine à 5 États | Phase 0 | US-P0-03 | `docs(plugins): specify plugin system structure and 5-state lifecycle` |
| **US-P0-05** | Politique de Sécurité & STRIDE | Phase 0 | US-P0-04 | `docs(security): formalize application security policy and stride threat model` |
| **US-P0-06** | Pyramide de Tests & Assurance Qualité | Phase 0 | US-P0-05 | `docs(testing): define multi-tier testing strategy and architecture guardrails` |
| **US-P0-07** | Feuille de Route, Plan & User Stories | Phase 0 | US-P0-06 | `docs(roadmap): establish roadmap, operational plan, context and user stories` |
|---|---|---|---|---|
| **US-001** | Initialisation Squelette Minimal & Core Config | Phase 1 | US-P0-07 | `chore(core): initialize minimal laravel skeleton and core service provider` |
| **US-002** | Contrats Publics d'Extension | Phase 1 | US-001 | `feat(contracts): implement strict public interfaces for plugins` |
| **US-003** | Validateur Stricte des Manifestes | Phase 1 | US-002 | `feat(manifest): add ManifestValidator with schema checks and path traversal protection` |
| **US-004** | Découvreur de Plugins & Autoload PSR-4 | Phase 1 | US-003 | `feat(discovery): implement filesystem plugin discovery and dynamic psr-4 autoloading` |
| **US-005** | Registre Persistant & Machine à 5 États | Phase 1 | US-004 | `feat(lifecycle): implement persistent PluginRegistry and 5-state lifecycle machine` |
| **US-006** | Gestionnaire de Migrations Isolées | Phase 1 | US-005 | `feat(migrations): add isolated PluginMigrationManager and cms_plugin_migrations tracking` |
| **US-007** | Panneau Admin Minimal & Commandes Artisan | Phase 1 | US-006 | `feat(admin): provide minimal blade admin panel and artisan management commands` |
| **US-008** | Plugin de Démonstration `demo-hello` | Phase 1 | US-007 | `feat(demo): introduce demo-hello reference plugin with isolated routes and migration` |
| **US-009** | Garde-fous d'Architecture & Tests MVP | Phase 1 | US-008 | `test(mvp): add architecture boundaries tests and end-to-end lifecycle verification` |
|---|---|---|---|---|
| **US-010** | Tri Topologique & Détection de Cycles (Kahn) | Phase 2 | US-009 | `feat(resolver): implement Kahn topological dependency resolver and cycle detection` |
| **US-011** | Bus de Hooks (Actions & Filtres) | Phase 2 | US-010 | `feat(hooks): implement prioritized Action and Filter HookManager` |
| **US-012** | Tolérance aux Crashs & Safe Mode | Phase 2 | US-010 | `feat(recovery): introduce crash tolerance quarantine and emergency safe mode` |
| **US-013** | Navigation Dynamique & Registre Permissions | Phase 2 | US-011 | `feat(navigation): add dynamic admin navigation and permission registry` |
|---|---|---|---|---|
| **US-014** | Gestionnaire d'Assets Statiques Isolés | Phase 3 | US-013 | `feat(assets): implement isolated PluginAssetManager and asset publishing command` |
| **US-015** | Contrat MediaStorage & Plugin Médiathèque | Phase 3 | US-014 | `feat(media): implement MediaStorageInterface and acme.media-library plugin` |
| **US-016** | Plugin de Pages Statiques Dynamiques | Phase 3 | US-015 | `feat(pages): implement acme.pages plugin with dynamic routing and front template` |
| **US-017** | Plugin de Menus Hiérarchiques | Phase 3 | US-016 | `feat(menus): implement acme.menus hierarchical navigation plugin and render hook` |
| **US-018** | Plugin SEO & Métadonnées via Hooks | Phase 3 | US-011, US-016 | `feat(seo): implement acme.seo plugin leveraging hook filter bus for head metadata` |
|---|---|---|---|---|
| **US-019** | Contrats Publics de Thèmes & Découverte | Phase 4 | US-018 | `feat(themes): implement ThemeInterface, ThemeManifest DTO, and ThemeDiscovery service` |
| **US-020** | Gestionnaire de Thèmes & Surcharge de Vues | Phase 4 | US-019 | `feat(themes): implement ThemeManager with view overriding hierarchy and persistence` |
| **US-021** | Gestionnaire d'Assets de Thème & Publication | Phase 4 | US-020 | `feat(themes): introduce ThemeAssetManager and theme assets publishing command` |
| **US-022** | Thème de Référence Découplé `default-modern` | Phase 4 | US-021 | `feat(themes): provide default-modern reference theme with responsive layout and plugin overrides` |
| **US-023** | Commandes CLI & Panneau Admin des Thèmes | Phase 4 | US-022 | `feat(themes): provide artisan theme management commands and admin themes panel` |

