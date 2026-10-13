# Stratégie de Tests & Assurance Qualité — Laravel Plugin-First CMS

**Version :** 1.0.0
**Date de révision :** 2026-10-12
**Statut :** Spécification de référence — Phase 0

---

## 1. Pyramide des Tests

La qualité et l'extensibilité du CMS sont garanties par une suite de tests automatisés multi-niveaux :

```text
               / \
              /   \
             / E2E \       - Parcours administrateur complets
            /-------\
           / Feature \     - Activation HTTP, montage de routes, login admin
          /-----------\
         / Integration \   - Contrats de plugins, résolution du DAG, migrations
        /---------------\
       /      Unit       \ - Validation du manifeste, tri topologique, hooks
      /-------------------\
     /    Architecture     \ - Respect des frontières et interdiction d'imports
    /-----------------------\
```

---

## 2. Niveaux de Tests Spécifiques

### 2.1. Tests d'Architecture (Pest Arch / PHPUnit)
Vérifient que les règles de couplage sont respectées au niveau du code source :
- `app/Core` ne doit dépendre d'aucun namespace `Plugins\*`.
- Les classes de `platform/plugins/*` ne doivent jamais référencer directement les contrôleurs ou modèles d'autres plugins.
- Tous les modèles de plugins doivent respecter le préfixe de table configuré.

### 2.2. Tests Unitaires (`tests/Unit/`)
- `ManifestValidatorTest` : Rejet des manifestes invalides (syntaxe JSON, champs manquants, regex d'ID erronée, chemins avec `../`).
- `DependencyResolverTest` :
  - Ordre d'activation correct pour une chaîne linéaire (A -> B -> C).
  - Détection immédiate des cycles (A -> B -> A) avec levée d'une `CircularDependencyException`.
  - Rejet lors d'une dépendance non satisfaite ou d'incompatibilité de version.
- `HookManagerTest` : Ordre des priorités d'exécution des actions et filtres.

### 2.3. Tests Fonctionnels (`tests/Feature/`)
- `AdminAuthenticationTest` : Accès protégé aux routes du panneau, rate-limiting sur le formulaire de connexion.
- `PluginLifecycleHttpTest` :
  - Découverte d'un plugin dans `platform/plugins/demo-hello`.
  - Transition d'état `AVAILABLE` -> `INSTALLED` -> `ACTIVE`.
  - Vérification qu'une route `/demo/hello` déclarée par le plugin retourne un code HTTP 200 lorsque le plugin est actif.
  - Vérification que la route retourne un code HTTP 404 dès que le plugin est désactivé.
  - Absence de fuite de données ou de suppression intempestive de tables lors de la désactivation.

### 2.4. Tests de Sécurité (`tests/Security/`)
- Test de tentative d'injection de traversée de répertoire (`../../etc/passwd`) via le champ `providers` ou `routes`.
- Test de rejet de requête d'administration sans jeton CSRF valide.
- Test de non-affichage des stack traces et variables d'environnement lors d'une erreur critique de plugin.

---

## 3. Commandes d'Exécution et Standards CI

```bash
# Exécution de l'intégralité de la suite de tests
php artisan test

# Exécution ciblée par suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
php artisan test --testsuite=Architecture

# Analyse statique rigoureuse (PHPStan niveau 8)
vendor/bin/phpstan analyse

# Vérification du style de code
vendor/bin/pint --test
```
