# Documentation Officielle — LCMS Core

Bienvenue dans l'espace documentaire de **LCMS Core** (Laravel Plugin-First CMS v2.0.0).

---

## 📑 Sommaire des Documents Disponibles

| Document | Format | Description |
|---|---|---|
| [**Portail de Documentation Technique Complète**](./index.html) | HTML interactif | Guide complet avec recherche en temps réel : démarrage, architecture, guide de création d'extensions, moteur de thèmes, sécurité TOTP 2FA, contrôle d'accès RBAC, gestion des mises à jour Core et référence CLI. |
| [**Guide de Dépannage Hébergement Mutualisé & `public_html`**](./TROUBLESHOOTING_PUBLIC_HTML.md) | Markdown | Guide pas-à-pas et matrice de décision pour configurer LCMS Core sur les serveurs mutualisés (cPanel, Plesk, etc.) où la racine Web est `public_html`. |
| [**Instructions Permanentes d'Ingénierie**](../AGENTS.md) | Markdown | Règles d'architecture, directives d'isolation, exigences de sécurité et standards de qualité pour contributeurs et agents d'IA. |
| [**Architecture & Décisions Structurantes (ADR)**](../IAdocs/decisions/) | Markdown | Registre complet des décisions d'architecture (ADR-0001 à ADR-0006). |
| [**Spécification du Système de Mise à Jour Core**](../IAdocs/Update_System/) | Markdown | Spécifications, sécurité anti-Zip-Slip, schéma du manifeste `update.json` et rollback automatique. |

---

## 🏛️ Architecture et Principes Fondamentaux

LCMS Core applique une stricte séparation en 4 couches d'ingénierie :

1. **Noyau Minimal (*Lean Core* — `Core/`) :**
   - Aucune logique métier (pas de blog, pages, formulaires ou boutiques codés en dur).
   - Démarrage, découverte de manifestes, injection de dépendances (IoC).
   - Résolution topologique des dépendances ordonnancée par l'algorithme de Kahn.
   - Bus d'interception par Hooks (`Hook::filter()` et `Hook::action()`).
   - Tolérance aux pannes (*Emergency Safe Mode* et mise en quarantaine automatique).
   - Layout d'administration unifié sous AdminLTE 4 et Bootstrap 5.3.

2. **Contrats Publics Stricts (`Core/Contracts/`) :**
   - `PluginInterface` : Identifiant, nom, version et cycle de vie.
   - `PluginManifestInterface` : Métadonnées validées, permissions, routes, autoload et exigences Composer (`getComposerRequirements()`).
   - `HookSubscriberInterface` : Écouteurs d'actions et de filtres.
   - `ThemeInterface` : Moteur de présentation et hiérarchie de surcharge Blade.
   - `MediaStorageInterface` : Abstraction de persistance des médias.

3. **Écosystème de 15 Extensions Autonomes (`Plugins/`) :**
   - Chaque extension est étanche, possède son manifeste `plugin.json`, ses routes, modèles Eloquent, vues Blade, permissions et migrations isolées (`cms_plugin_migrations`).
   - **Extensions incluses :** `pages`, `menus`, `media-library`, `seo`, `blog`, `commerce-lite`, `forms`, `blocks`, `i18n`, `search`, `newsletter`, `redirects`, `analytics`, `api-headless`, `demo-hello`.

4. **Moteur de Thèmes Découplé (`themes/`) :**
   - Thème de référence clair et minimal : `default-modern`.
   - Thème hybride d'exception : `lumina-lifestyle` (Slow-Living Journal & Boutique artisanale avec design system terracotta & lin).
   - Surcharge dynamique de vues Blade (*View Overriding*) sans toucher aux sources des plugins.

---

## 🧩 Spécification du Manifeste `plugin.json`

Tout plugin doit obligatoirement inclure à sa racine un fichier `plugin.json` validé par `ManifestValidator` :

```json
{
  "id": "lcmscore.mon-plugin",
  "name": "Mon Extension Métier",
  "version": "1.0.0",
  "description": "Description concise de l'extension",
  "author": {
    "name": "Auteur ou Organisation",
    "email": "contact@exemple.com"
  },
  "compatibility": {
    "php": "^8.2",
    "lcms": "^2.0"
  },
  "autoload": {
    "psr-4": {
      "Plugins\\MonPlugin\\": "src/"
    }
  },
  "providers": [
    "Plugins\\MonPlugin\\Providers\\MonPluginServiceProvider"
  ],
  "dependencies": {
    "lcmscore.media-library": "^1.0"
  },
  "conflicts": [],
  "requirements": {
    "composer": {
      "spatie/schema-org": "^4.0"
    }
  },
  "permissions": [
    {
      "key": "mon_plugin.manage",
      "label": "Gérer Mon Plugin"
    }
  ],
  "routes": {
    "web": "routes/web.php",
    "api": "routes/api.php"
  }
}
```

### Prise en charge des Dépendances Composer (`requirements.composer`)

Le bloc `requirements.composer` déclare les bibliothèques PHP requises au niveau de l'application hôte :
- **Validation automatique :** Inspecté à la découverte par `ManifestValidator` et accessible via `$manifest->getComposerRequirements()`.
- **Inspection Administrateur :** Sur la fiche de détail du plugin (`/admin/plugins/{id}`), l'encadré dédié **Composer Packages Requirements** vérifie en temps réel la présence du package via `\Composer\InstalledVersions::isInstalled($pkg)`.
- **Statuts clairs :** Badge vert `Installed` si la dépendance est active ; badge d'avertissement avec recommandation directe (ex: `composer require spatie/schema-org`) si le package est manquant.

---

## 💻 Commandes Artisan Principales

```bash
# Démonstration clé en main (Blog, Boutique, Pages, Menus, Formulaire, Blocs)
php artisan lcms:seed-demo

# Lister les extensions et leur statut
php artisan cms:plugin:list

# Activer ou désactiver une extension
php artisan cms:plugin:enable lcmscore.commerce-lite
php artisan cms:plugin:disable lcmscore.commerce-lite

# Publier les assets des extensions et des thèmes
php artisan cms:plugin:publish-assets --all
php artisan cms:theme:publish-assets --all

# Exécuter les seeders de plugins
php artisan cms:plugin:seed --all

# Mode de secours d'urgence (Emergency Safe Mode)
php artisan cms:recovery:safe-mode --on
php artisan cms:recovery:safe-mode --off

# Sécurité 2FA CLI
php artisan cms:security:enable-2fa admin@lcms.local
php artisan cms:recovery:disable-2fa admin@lcms.local
```

---

## 🧪 Normes de Qualité et Tests

Avant toute livraison ou validation d'étape :

```bash
# 1. Tests automatisés (425 tests, 2 802 assertions)
php artisan test

# 2. Analyse statique (Niveau 8 strict)
php vendor/bin/phpstan analyse --level=8 --memory-limit=512M

# 3. Validation du style de code Laravel
php vendor/bin/pint --test
```
