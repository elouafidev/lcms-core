# Contexte Courant du Projet — Laravel Plugin-First CMS

**Dernière mise à jour :** 2026-10-12 (Phase 0)
**Responsable d'architecture :** Équipe d'ingénierie logicielle & Architecture CMS

---

## 1. Vision et Orientation Stratégique
Le projet conçoit et implémente un CMS modulaire fondé sur Laravel où le noyau est strictement minimal (Lean Core) et où l'ensemble des fonctionnalités éditoriales et métier (pages, blogs, médias, formulaires, e-commerce, SEO) sont portées par des plugins autonomes respectant des contrats publics précis.
Priorité absolue : robustesse architecturale, isolation logique, gestion rigoureuse des dépendances et résilience (tolérance aux erreurs d'extensions).

---

## 2. Phase Actuelle
- **Phase active :** **PHASE 1 EN COURS (MVP DU NOYAU — US-001 TERMINÉE)**
- **Objectif de phase :** Initialiser le socle Laravel 11/12/13 et implémenter les User Stories US-001 à US-009 (1 Story = 1 Commit) pour disposer d'un CMS minimal capable de découvrir, installer, activer et désactiver le plugin `acme.demo-hello` sous isolation stricte.
- **Statut :** US-001 validée avec tests au vert, analyse statique PHPStan niveau 8 (0 erreurs) et Laravel Pint (0 erreurs).

---

## 3. Statut d'Avancement des Fonctionnalités

### 3.1. Fonctionnalités Terminées (Phase 0 & Début Phase 1)
- [x] **Cadre architectural & Directives :** `AGENTS.md`, ADR-0001 (Monolithe modulaire), ADR-0002 (Manifestes & Contrats).
- [x] **Spécifications techniques :** `docs/ARCHITECTURE.md`, `docs/PLUGIN_SYSTEM.md`.
- [x] **Sécurité & Menaces :** `docs/SECURITY.md`, `docs/THREAT_MODEL.md` (STRIDE).
- [x] **Assurance qualité :** `docs/TESTING.md`, `docs/ROADMAP.md`.
- [x] **Spécification des User Stories :** `docs/USER_STORIES.md` (13 User Stories avec messages de commit conventionnels).
- [x] **US-001 (P1-T1) :** Squelette Laravel minimal initialisé, `config/cms.php` configuré, `CoreServiceProvider` enregistré dans `bootstrap/app.php`, tests unitaires au vert.
- [x] **US-002 (P1-T2) :** Contrats publics stricts (`PluginInterface`, `PluginManifestInterface`, `PluginLifecycleListenerInterface`) et tests d'architecture de pureté au vert.
- [x] **US-003 (P1-T3) :** Validateur de manifeste JSON (`ManifestValidator`), DTO immuable `PluginManifest`, protection anti-traversal et 9 tests unitaires au vert.
- [x] **US-004 (P1-T4) :** Découvreur de plugins locaux (`PluginDiscovery`), DTO `DiscoveredPlugin`, `PluginClassLoader` avec injection dynamique PSR-4 dans Composer.
- [x] **US-005 (P1-T5) :** Table `cms_plugins`, Enum `PluginState` (5 états), modèle `PluginRecord`, `PluginRegistry` et `PluginManager` avec isolation et Safe Mode.
- [x] **US-006 (P1-T6) :** Gestionnaire de migrations isolées (`PluginMigrationManager`, table `cms_plugin_migrations`), exécution transactionnelle à l'installation, préservation des données à la désactivation, rollback sélectif à la désinstallation complète.
- [x] **US-007 (P1-T7) :** Panneau d'administration minimal Blade (`/admin/plugins`), validation CSRF, et commandes CLI (`cms:plugin:list`, `cms:plugin:enable`, `cms:plugin:disable`, `cms:recovery:safe-mode`).

### 3.2. Prochaine Étape (Phase 1 — MVP du Noyau)
- [ ] **US-008 (P1-T8) :** Plugin de démonstration minimal `platform/plugins/demo-hello/` avec route `/demo/hello` et migration isolée.
- [ ] **US-009 (P1-T9/T10) :** Garde-fous d'architecture (Pest Arch) et validation intégrale des critères du MVP.

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
1. Présenter le bilan de validation de la **PHASE 1 (MVP du Noyau)** à l'utilisateur.
2. Engager la **PHASE 2 (Stabilisation du Système de Plugins)** :
   - Gestionnaire de dépendances avec contraintes SemVer strictes et résolution récursive.
   - Système de hooks (Actions et Filtres) avec priorités d'exécution.
   - Enregistrement des éléments de menu d'administration par plugin (`NavigationItemContract`).
   - Registre de permissions par plugin (`PermissionRegistry`).
   - Diagnostics avancés et rapports d'incident.

---

## 8. Points Critiques à ne pas Modifier sans Justification
- Ne jamais ajouter de contrôleur de blog ou de gestionnaire de contenu directement dans `app/Http/Controllers` ou `app/Core`.
- Ne jamais charger un plugin dont le manifeste échoue à la validation de schéma.
- Conserver la séparation physique entre `platform/plugins/` et le code de base de l'application Laravel.
