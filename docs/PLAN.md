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
| **P1-T3** | Validateur de manifeste JSON (`ManifestValidator`) et schéma | Phase 1 (US-003) | P1-T2 | **À faire** |
| **P1-T4** | Découvreur de plugins locaux (`PluginDiscovery`) | Phase 1 (US-004) | P1-T3 | **À faire** |
| **P1-T5** | Registre et orchestrateur du cycle de vie (`PluginRegistry`, `PluginManager`) | Phase 1 (US-005) | P1-T4 | **À faire** |
| **P1-T6** | Gestionnaire de migrations isolées par plugin (`PluginMigrationManager`) | Phase 1 (US-006) | P1-T5 | **À faire** |
| **P1-T7** | Authentification de base et Panneau d'administration minimal | Phase 1 (US-007) | P1-T6 | **À faire** |
| **P1-T8** | Création du plugin de démonstration (`platform/plugins/demo-hello`) | Phase 1 (US-008) | P1-T7 | **À faire** |
| **P1-T9** | Suite de tests automatisés du MVP (Unit, Feature, Architecture) | Phase 1 (US-009) | P1-T8 | **À faire** |
| **P1-T10** | Validation des critères d'acceptation du MVP | Phase 1 (US-009) | P1-T9 | **À faire** |

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
