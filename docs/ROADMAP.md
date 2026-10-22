# Feuille de Route Stratégique (Roadmap) — Laravel Plugin-First CMS

**Version :** 1.0.0
**Date de révision :** 2026-10-12
**Statut :** Plan Directeur

---

## 1. Vue d'Ensemble des Phases

```text
  [ Phase 0 : Conception & Architecture ] (Actuelle)
                 │
                 ▼
  [ Phase 1 : MVP du Noyau & Démo Plugin ]
                 │
                 ▼
  [ Phase 2 : Stabilisation & Tolérance aux Pannes ]
                 │
                 ▼
  [ Phase 3 : Plugins Fondamentaux ] (Media, Pages, Menus, Blog, SEO)
                 │
                 ▼
  [ Phase 4 : API REST & Mode Headless ]
                 │
                 ▼
  [ Phase 5 : Écosystème & Distribution des Plugins ]
                 │
                 ▼
  [ Phase 6 : Fonctionnalités Avancées ] (Builder, i18n, Multi-site)
                 │
                 ▼
  [ Phase 7 : Préparation Cloud & Production ]
```

---

## 2. Détail des Phases et Critères de Validation

### Phase 0 — Analyse et Conception (Statut : En cours de validation)
- **Objectif :** Poser les bases documentaires, les contrats d'interface, la matrice de sécurité et le plan de réalisation sans polluer le dépôt avec du code non structuré.
- **Livrables :** `AGENTS.md`, `README.md`, `docs/CONTEXT.md`, `docs/ARCHITECTURE.md`, `docs/PLUGIN_SYSTEM.md`, `docs/SECURITY.md`, `docs/THREAT_MODEL.md`, `docs/TESTING.md`, `docs/ROADMAP.md`, `docs/PLAN.md`, ADR-0001, ADR-0002.
- **Critère de passage :** Validation de l'architecture et des contrats par l'architecte / utilisateur.

---

### Phase 1 — MVP du Noyau (Prochaine Étape)
- **Objectif :** Disposer d'un CMS minimal capable de démarrer sans plugin métier et d'activer/désactiver un plugin de démonstration.
- **Composants :**
  - Installation Laravel propre avec SQLite/PostgreSQL.
  - Authentification racine administrateur.
  - Panneau minimal d'administration pour la liste des plugins (`platform/core/AdminPanel`).
  - `PluginDiscovery` (lecture du dossier `platform/plugins/`).
  - `ManifestValidator` (validation JSON selon schéma strict).
  - `PluginRegistry` et `PluginManager` (états `AVAILABLE`, `INSTALLED`, `ACTIVE`, `DISABLED`).
  - Plugin de démonstration `platform/plugins/demo-hello`.
  - Migration dédiée du plugin de démonstration (`demo_messages` table).
  - Routes du plugin activées et désactivées dynamiquement.
- **Critère de validation :** Tests unitaires et fonctionnels au vert. Le CMS démarre à vide sans erreur. L'activation du plugin monte la route avec code HTTP 200, sa désactivation renvoie 404 sans altérer les données de la base.

---

### Phase 2 — Stabilisation du Système de Plugins
- **Objectif :** Résilience logicielle, détection de dépendances et isolation robuste.
- **Composants :**
  - `DependencyResolver` : algorithme de tri topologique (Kahn) et détection de cycles.
  - Gestionnaire de conflits et contraintes de versions SemVer.
  - Système de Hooks (Actions et Filtres).
  - Registre de permissions par plugin et injection des menus du panneau admin.
  - Safe Mode / Recovery CLI en cas d'erreur critique de plugin.
- **Critère de validation :** Rejet systématique de deux plugins en dépendance circulaire. Mode de secours fonctionnel sans intervention manuelle sur la base.

---

### Phase 3 — Plugins Fondamentaux
- **Objectif :** Développer les fonctionnalités éditoriales standard sous forme de plugins autonomes.
- **Composants :**
  1. `lcmscore.media-library` : Gestion des uploads, formats, métadonnées (aucun impact direct sur les autres plugins).
  2. `lcmscore.pages` : Gestionnaire de pages statiques (table `plugin_pages_items`).
  3. `lcmscore.menus` : Gestionnaire de navigation arborescente.
  4. `lcmscore.blog` : Articles, catégories, commentaires.
  5. `lcmscore.seo` : Métadonnées OpenGraph, balises canoniques, sitemap via hooks.
  6. Thème Blade découplé par défaut.
- **Critère de validation :** Chaque plugin peut être désactivé sans faire planter les autres. Pages et Blog peuvent fonctionner conjointement sans couplage direct.

---

### Phase 4 — API et Mode Headless
- **Objectif :** Rendre l'ensemble des données des plugins accessibles via des API REST versionnées.
- **Composants :**
  - Routes d'API unifiées `/api/v1/*`.
  - Authentification par jetons sécurisés (Laravel Sanctum).
  - Ressources et sérialiseurs JSON stricts.
  - Throttling et documentation OpenAPI / Swagger automatique.

---

### Phase 5 — Écosystème & Distribution
- **Objectif :** Faciliter la création et le partage d'extensions tierces.
- **Composants :**
  - Commande Artisan `php artisan cms:plugin:make <id>`.
  - Distribution de packages via Composer.
  - Import sécurisé d'archives ZIP avec vérification de hachage SHA-256 et contrôle anti-traversée de répertoire.

---

### Phase 6 — Fonctionnalités Avancées
- **Objectif :** Support de cas d'usage complexes d'entreprise.
- **Composants :**
  - Moteur de types de contenus dynamiques (Custom Content Types).
  - Constructeur de mise en page visuel par blocs.
  - Support multilingue (i18n) et multi-domaines (Multi-tenant).

---

### Phase 7 — Production, Cloud & Observabilité
- **Objectif :** Préparation au déploiement à grande échelle.
- **Composants :**
  - Conteneurisation Docker multi-étapes optimisée.
  - Pipelines CI/CD automatisés (PHPStan niveau 8, Pint, Pest, Trivy/Snyk).
  - Monitoring des performances et traçabilité des erreurs (OpenTelemetry / Sentry).
  - Procédures de sauvegarde et de restauration automatisées testées.
