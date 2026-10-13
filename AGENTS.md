# Instructions Permanentes pour Agents d'Ingénierie — Laravel Plugin-First CMS

Ce document définit les directives obligatoires que tout agent d'intelligence artificielle ou développeur automatisé doit respecter pour intervenir sur ce dépôt.

---

## 1. Principes Fondamentaux et Règle d'Or

1. **Architecture & Contrats prioritaires :** L'architecture modulaire, l'isolation logique, la stabilité des contrats d'extension et la couverture de tests priment toujours sur le volume de fonctionnalités produites.
2. **Noyau Minimal (Lean Core) :** Le noyau (`app/Core` ou `platform/core`) ne doit contenir **aucune** logique métier spécifique (pas de blog, pas de boutique, pas de pages codées en dur, pas de médiathèque forcée). Le noyau n'assure que :
   - Démarrage et cycle de vie du CMS.
   - Authentification et gestion du compte administrateur racine.
   - Découverte, validation, enregistrement et contrôle du cycle de vie des plugins.
   - Gestion des dépendances entre plugins et vérification des conflits.
   - Définition et mise à disposition des Contrats Publics (`Contracts/`).
   - Mécanisme d'événements, d'interception (Hooks) et de permissions de base.
   - Journalisation, diagnostics et récupération sur incident critique (Safe Mode / Recovery).
3. **Zéro modification du noyau par les plugins :** Un plugin ne modifie jamais les fichiers du noyau et n'accède jamais directement aux implémentations privées d'autres plugins.
4. **Propriété stricte des données :** Chaque plugin possède ses propres migrations et tables. Les suppressions destructives ne sont jamais automatiques.

---

## 2. Règles de Modification du Code et des Fichiers

- **Consultation préalable :** Toujours consulter `AGENTS.md`, `docs/CONTEXT.md` et `docs/PLAN.md` au début de chaque session.
- **Convention Laravel & Typage strict :** Code compatible PHP 8.2+ / Laravel 11 / 12, strict types (`declare(strict_types=1);`), typage exhaustif des arguments et des retours.
- **Injection de dépendances :** Privilégier systématiquement l'injection de dépendances et l'inversion de contrôle (IoC Container Laravel) plutôt que des façades arbitraires ou des singletons opaques.
- **Gestion des erreurs :** Jamais de `try { ... } catch (\Throwable $e) {}` silencieux. Toutes les exceptions de plugin doivent être contextualisées et enregistrées dans les journaux d'audit.

---

## 3. Commandes de Validation et Qualité

Avant de marquer une étape comme terminée :
- Analyse statique : `composer run analyse` ou `phpstan analyse` (niveau 8 recommandé).
- Style et formatage : `composer run lint` / `pint --test`.
- Tests automatisés : `composer test` ou `php artisan test`.
- Vérification de l'intégrité de la base de données : `php artisan migrate:status`.

Ne supprimez jamais un test pour faire passer une suite en échec. Identifiez et corrigez la cause racine.

---

## 4. Exigences de Sécurité

- Aucun secret ou clé API en clair dans les dépôts.
- Validation systématique de tous les manifestes (`plugin.json`) avec schéma strict contre les attaques par traversée de répertoire (`directory traversal`).
- Jamais de fonction `eval()` ou d'inclusion dynamique non filtrée pour charger des plugins.
- Toute action d'activation/désactivation de plugin doit être protégée par un contrôle d'autorisation serveur et un jeton CSRF.
- Mode de récupération d'urgence (Emergency Safe Mode / CLI bypass) obligatoire pour réactiver l'accès admin en cas de plugin défaillant.

---

## 5. Gestion du Contexte entre Sessions

À la fin de chaque étape significative :
1. Mettre à jour `docs/CONTEXT.md` (état réel, risques, décisions, prochaine tâche recommandée).
2. Mettre à jour `docs/PLAN.md` (statuts des tâches).
3. Consigner tout choix structurant sous forme d'ADR dans `docs/decisions/`.
4. Ne jamais prétendre qu'une fonctionnalité est opérationnelle si elle n'a pas été exécutée et testée avec succès.
