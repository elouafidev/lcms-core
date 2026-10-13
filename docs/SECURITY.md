# Politique de Sécurité Applicative — Laravel Plugin-First CMS

**Version :** 1.0.0
**Dernière mise à jour :** 2026-10-12
**Statut :** Spécification de référence — Phase 0

---

## 1. Principes Fondamentaux de Sécurité

1. **Défense en profondeur :** Chaque couche applicative (réseau, routage, contrôleur, domaine, persistance) applique ses propres validations.
2. **Moindre Privilège :** Les droits d'administration sont granulaires ; l'accès au gestionnaire de plugins requiert une permission explicite `cms.plugins.manage`.
3. **Vérité sur l'isolation PHP :** Dans un environnement d'exécution PHP standard sans conteneurisation dédiée par plugin, le code d'un plugin s'exécute dans le même espace mémoire et processus que le noyau. **Il n'existe pas de bac à sable (sandbox) absolu en pur PHP natif.** La sécurité repose donc sur :
   - La restriction stricte des droits d'installation (seul le super-administrateur peut installer ou activer du code).
   - La validation syntaxique et statique des manifestes.
   - La journalisation inaltérable des actions administratives.

---

## 2. Authentification et Gestion des Sessions

- **Hachage des mots de passe :** Utilisation exclusive de l'algorithme Bcrypt ou Argon2id (cost factor conforme aux recommandations OWASP).
- **Régénération d'identifiant de session :** Régénération systématique de l'ID de session lors de l'authentification pour contrer la fixation de session.
- **Attributs des cookies de session :** `HttpOnly = true`, `Secure = true` (en HTTPS), `SameSite = Lax` ou `Strict`.
- **Limitation du taux de requêtes (Rate Limiting) :** Protection des routes `/admin/login` par throttler exponentiel (5 tentatives par minute par IP).

---

## 3. Protection contre les Vulnérabilités Web Majeures

### 3.1. Injections SQL
- Utilisation exclusive de l'ORM Eloquent ou du Query Builder avec requêtes préparées et liaison de paramètres.
- Interdiction formelle de concaténer des variables utilisateurs dans `DB::raw()` ou des clauses `whereRaw()`.

### 3.2. Cross-Site Scripting (XSS)
- Échappement automatique par défaut dans toutes les vues Blade (`{{ $variable }}`).
- Interdiction de la syntaxe non échappée `{!! $variable !!}` sauf pour du HTML certifié et filtré par un épurateur strict (ex: HTMLPurifier).

### 3.3. Cross-Site Request Forgery (CSRF)
- Toutes les requêtes HTTP modificatrices (`POST`, `PUT`, `PATCH`, `DELETE`) doivent porter un jeton CSRF valide vérifié par le middleware de Laravel.

---

## 4. Sécurité du Système de Plugins

1. **Contrôle d'accès au gestionnaire :** L'activation, la désactivation, l'installation ou la suppression d'un plugin nécessite une double vérification d'autorisation côté serveur.
2. **Validation des Chemins (Path Traversal) :** Lors de la lecture d'un manifeste ou du chargement de ressources, tout chemin relatif est normalisé via `realpath()` et vérifié afin de s'assurer qu'il demeure strictement confiné au répertoire racine du plugin.
3. **Interdiction d'`eval()` et d'inclusions dynamiques arbitraires :** L'exécution de code passe exclusivement par des classes PSR-4 enregistrées et déclarées explicitement dans le manifeste.

---

## 5. Journalisation d'Audit et Diagnostic
Toutes les opérations sensibles sont consignées dans un journal d'audit dédié :
- Connexions administrateur (succès et échecs).
- Activation, désactivation ou mise à jour d'un plugin.
- Exécution de migrations de plugins.
- Déclenchement d'un crash ou passage d'un plugin en état `ERROR`.
