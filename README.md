# Laravel Plugin-First CMS

> Système de gestion de contenu extensible, modulaire et hautement sécurisé, fondé sur un noyau minimaliste Laravel et une architecture orientée plugins et contrats stricts.

---

## 1. Vision et Spécificités

Contrairement aux CMS traditionnels qui intègrent nativement des collections rigides de pages, de blogs, de formulaires ou de boutiques, **Laravel Plugin-First CMS** dissocie entièrement le moteur de plateforme de la logique éditoriale.

Le noyau fournit exclusivement les mécanismes d'infrastructure indispensables :
- Authentification et gestion du compte administrateur racine.
- Registre central et découverte dynamique des extensions (`platform/plugins/`).
- Validation rigoureuse des manifestes (`plugin.json`) avec schéma strict.
- Machine à états du cycle de vie des plugins (`available`, `installed`, `active`, `disabled`, `error`).
- Moteur de résolution de dépendances et détection préventive de conflits/cycles.
- Enregistrement contextualisé des routes, vues, migrations et permissions.
- Mécanisme de tolérance aux pannes et mode de secours (*Emergency Safe Mode*).

---

## 2. État Actuel du Projet

- **Phase active :** **PHASE 0 — Analyse, Conception et Architecture**
- **Statut :** Architecture documentée, contrats initiaux rédigés, modèle de menaces formalisé, plan opérationnel établi. En attente de validation architecturale avant démarrage de la Phase 1 (Noyau MVP).

---

## 3. Prérequis et Environnement Technique

- **Langage :** PHP 8.2+ (strict types).
- **Framework :** Laravel 11.x ou 12.x.
- **Gestionnaire de dépendances :** Composer 2.x.
- **Base de données principale :** SQLite (développement/tests rapides) ou PostgreSQL / MySQL (production).
- **Moteur de templates :** Blade pour le panneau d'administration minimal.
- **Outils qualité :** PHPUnit / Pest, PHPStan (niveau 8), Laravel Pint.
- **Console & Frontend d'orchestration :** Vite + React 19 pour le tableau de bord d'architecture interactif.

---

## 4. Structure Globale du Projet

```text
├── AGENTS.md                   # Instructions permanentes pour agents IA & développeurs
├── README.md                   # Présentation générale et démarrage
├── app/
│   ├── Core/                   # Noyau du CMS (minimal, sans logique métier)
│   │   ├── Contracts/          # Interfaces publiques stables pour les plugins
│   │   ├── Exceptions/         # Gestion unifiée des exceptions du noyau
│   │   ├── PluginManager/      # Orchestrateur du cycle de vie des plugins
│   │   ├── PluginRegistry/     # Registre en mémoire et persistant des extensions
│   │   ├── PluginDiscovery/    # Découverte automatique dans le filesystem
│   │   ├── DependencyResolver/ # Résolveur topologique et détection de cycles
│   │   ├── Lifecycle/          # Machine à états (Install, Activate, Deactivate, Uninstall)
│   │   ├── Hooks/              # Système d'actions et filtres extensibles
│   │   ├── Permissions/        # Registre et contrôle des permissions par plugin
│   │   └── Recovery/           # Mode sans échec et résilience en cas de crash
├── platform/
│   ├── core/                   # Administration système minimale (Blade & Auth)
│   ├── plugins/                # Répertoire isolé des plugins installés
│   └── themes/                 # Thèmes de présentation découplés
├── docs/                       # Spécifications et documentation d'architecture
│   ├── ARCHITECTURE.md         # Architecture détaillée et flux d'exécution
│   ├── CONTEXT.md              # État de l'art et suivi inter-sessions
│   ├── PLAN.md                 # Découpage des tâches et critères d'acceptation
│   ├── ROADMAP.md              # Feuille de route des phases 0 à 7
│   ├── SECURITY.md             # Directives de sécurité et mitigations
│   ├── THREAT_MODEL.md         # Modèle de menaces STRIDE
│   ├── PLUGIN_SYSTEM.md        # Fonctionnement interne du gestionnaire de plugins
│   ├── TESTING.md              # Stratégie de tests et assurance qualité
│   └── decisions/              # Architecture Decision Records (ADR)
```

---

## 5. Démarrage Rapide

### 5.1. Installation locale

```bash
# 1. Cloner le projet
git clone <repository_url> laravel-cms && cd laravel-cms

# 2. Configurer les variables d'environnement
cp .env.example .env

# 3. Installer les dépendances PHP et initialiser l'application
composer install
php artisan key:generate

# 4. Exécuter les migrations du noyau
php artisan migrate

# 5. Lancer le serveur de développement
php artisan serve
```

### 5.2. Commandes de Diagnostic et Tests

```bash
# Vérifier la santé du registre de plugins
php artisan cms:plugin:list

# Mode sans échec (désactive tous les plugins tiers temporairement)
php artisan cms:recovery:safe-mode --on

# Exécution des tests unitaires et d'architecture
composer test
composer run analyse
```

---

## 6. Documentation Complète

Pour approfondir la conception et les directives techniques :
- [Architecture du Système](docs/ARCHITECTURE.md)
- [Cycle de Vie des Plugins](docs/PLUGIN_SYSTEM.md)
- [Modèle de Menaces & Sécurité](docs/THREAT_MODEL.md)
- [Spécification des User Stories & Commits](docs/USER_STORIES.md)
- [Plan Opérationnel](docs/PLAN.md)
- [Feuille de Route](docs/ROADMAP.md)
- [Journal des Décisions Architecturales (ADR)](docs/decisions/README.md)
