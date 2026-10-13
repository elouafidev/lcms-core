# Spécification du Système de Plugins — Laravel Plugin-First CMS

**Version du document :** 1.0.0
**Date de révision :** 2026-10-12
**Statut :** Spécification de référence — Phase 0

---

## 1. Structure Standard d'un Plugin

Chaque plugin réside dans son propre répertoire sous `platform/plugins/{vendor}/{plugin-slug}/` (ou convention à plat `platform/plugins/{plugin-slug}/` pour le MVP).

```text
platform/plugins/demo-hello/
├── plugin.json                 # Manifeste obligatoire et versionné
├── composer.json               # (Optionnel) Dépendances de package spécifiques
├── src/
│   ├── Providers/
│   │   └── DemoPluginServiceProvider.php # Service Provider principal
│   ├── Http/
│   │   └── Controllers/
│   │       └── HelloController.php
│   ├── Models/
│   └── Services/
├── database/
│   └── migrations/             # Migrations appartenant exclusivement au plugin
├── resources/
│   └── views/                  # Vues Blade isolées
├── routes/
│   └── web.php                 # Déclaration des routes du plugin
└── tests/                      # Tests unitaires et d'intégration du plugin
```

---

## 2. Schéma et Validation du Manifeste (`plugin.json`)

Le fichier `plugin.json` est le contrat d'identité du plugin. Il est validé par le schéma JSON strict du noyau (`PluginManifestValidator`).

### 2.1. Exemple de Manifeste
```json
{
  "$schema": "https://cms.local/schemas/plugin-v1.json",
  "id": "acme.demo-hello",
  "name": "Acme Demo Hello",
  "version": "1.0.0",
  "description": "Démonstration d'un plugin minimal respectant les contrats du noyau.",
  "author": {
    "name": "Équipe Core",
    "email": "dev@cms.local"
  },
  "compatibility": {
    "cms": "^1.0.0",
    "php": ">=8.2",
    "laravel": "^11.0|^12.0"
  },
  "providers": [
    "Plugins\\DemoHello\\Providers\\DemoPluginServiceProvider"
  ],
  "dependencies": {
    "acme.media-library": "^1.0.0"
  },
  "conflicts": [
    "legacy.hello"
  ],
  "permissions": [
    {
      "key": "demo.view",
      "label": "Consulter les pages de démonstration"
    }
  ],
  "routes": {
    "web": "routes/web.php",
    "api": null
  },
  "autoload": {
    "psr-4": {
      "Plugins\\DemoHello\\": "src/"
    }
  }
}
```

### 2.2. Règles de Validation Incompressibles
1. **Identifiant (`id`) :** Doit correspondre à la regex `^[a-z0-9_-]+\.[a-z0-9_-]+$` (namespace vendor.slug).
2. **Version sémantique :** Conforme SemVer 2.0.0 (`MAJOR.MINOR.PATCH`).
3. **Protection contre la traversée de répertoire :** Aucun chemin relatif (`providers`, `routes`, `autoload`) ne doit contenir de séquences `../` ou de liens symboliques pointant hors du dossier racine du plugin.

---

## 3. Machine à États du Cycle de Vie (State Machine)

Le cycle de vie d'un plugin distingue strictement l'état des fichiers, l'état de la base de données et l'état d'activation applicative.

```text
               [ Découverte dans filesystem ]
                             │
                             ▼
                    ┌─────────────────┐
                    │    AVAILABLE    │  (Fichiers présents, manifeste validé)
                    └─────────────────┘
                             │
                  [ Action : Installer ] (Exécution migrations initiales)
                             ▼
                    ┌─────────────────┐
                    │    INSTALLED    │  (Migrations appliquées, inactif)
                    └─────────────────┘
                        │         ▲
      [ Action : Activer ]       [ Action : Désactiver ]
                        ▼         │
                    ┌─────────────────┐
                    │     ACTIVE      │  (Providers exécutés, routes montées)
                    └─────────────────┘
                        │         │
                        │    [ Exception critique ]
                        ▼         ▼
                    ┌─────────────────┐
                    │      ERROR      │  (Mis en quarantaine, routes coupées)
                    └─────────────────┘
```

### 3.3. Définition des États

| État | Définition Technique | Comportement au Boot |
|---|---|---|
| `AVAILABLE` | Dossier détecté, manifeste conforme. | Non chargé par Laravel. |
| `INSTALLED` | Migrations de base de données jouées avec succès. | Non chargé par Laravel. |
| `ACTIVE` | Actif et résolu par le graphe de dépendances. | ServiceProviders enregistrés et exécutés au boot. Routes accessibles. |
| `DISABLED` | Désactivé volontairement par l'administrateur. | Fichiers et données conservés. Aucun code exécuté. |
| `ERROR` | Échec de validation, dépendance rompue ou crash intercepté. | Isolé automatiquement, alerte affichée dans la console admin. |

---

## 4. Résolution des Dépendances et Détection de Cycles

1. **Graphe Dirigé Acyclique (DAG) :** Tous les plugins actifs forment un graphe orienté.
2. **Tri Topologique (Algorithme de Kahn) :** Permet de déterminer l'ordre déterministe d'enregistrement des ServiceProviders.
3. **Détection de Conflits et Cycles :** Si le plugin `A` requiert `B` et que `B` requiert `A`, ou si une contrainte de version SemVer est insatisfaite :
   - Le gestionnaire refuse l'activation.
   - Un diagnostic explicite est retourné avec la chaîne de dépendance fautive.

---

## 5. Contrats Publics Initiaux (`app/Core/Contracts`)

Pour assurer une isolation pérenne, le noyau expose des interfaces stables :

- `PluginInterface` : Contrat d'identification de base de l'extension.
- `PluginManifestInterface` : Objet immuable représentant le manifeste validé.
- `PluginLifecycleListenerInterface` : Hooks déclenchés lors des transitions d'état.
- `HookSubscriberInterface` : Déclaration des filtres et actions souscrits par le plugin.
- `NavigationItemContract` : Injection standardisée d'entrées dans le menu d'administration.
