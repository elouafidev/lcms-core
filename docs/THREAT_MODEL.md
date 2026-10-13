# Modèle de Menaces (Threat Model) — Laravel Plugin-First CMS

**Méthodologie :** STRIDE (Spoofing, Tampering, Repudiation, Information Disclosure, Denial of Service, Elevation of Privilege)
**Version :** 1.0.0
**Date de révision :** 2026-10-12
**Statut :** Spécification de référence — Phase 0

---

## 1. Actifs Critiques et Cartographie des Enjeux

1. **Intégrité du Système & Noyau :** Fichiers du code source du noyau Laravel (`app/Core`, `config/`, `routes/`).
2. **Secrets & Configurations :** Fichier `.env`, clés de chiffrement de session (`APP_KEY`), identifiants de base de données.
3. **Données Métier & Utilisateurs :** Données stockées dans les tables SQL, mots de passe hachés des administrateurs, contenus éditoriaux.
4. **Disponibilité du Service :** Capacité du CMS à continuer de répondre aux requêtes même en cas de défaillance d'une extension.

---

## 2. Acteurs et Frontières de Confiance

```text
[ UTILISATEUR PUBLIC / ANONYME ]
           │ (Non fiable - Trafic Web Public)
           ▼
────────────────────── Frontière de Confiance 1 : Périmètre Public / Réseau ────────
           │
[ ADMINISTRATEUR AUTHENTIFIÉ ]
           │ (Semi-fiable - Gestion de contenu, configuration)
           ▼
────────────────────── Frontière de Confiance 2 : Contrôle d'Accès Admin ───────────
           │
[ GESTIONNAIRE DE PLUGINS & SYSTÈME ]
           │ (Hautement sensible - Exécution de code)
           ▼
────────────────────── Frontière de Confiance 3 : Exécution PHP / Système d'Exploitation ──
```

---

## 3. Matrice d'Analyse des Menaces STRIDE

| Catégorie | Menace Spécifique | Vecteur / Scénario | Impact | Mesure d'Atténuation (Mitigation) |
|---|---|---|---|---|
| **Spoofing** | Usurpation de session administrateur | Vol de cookie de session par XSS ou écoute réseau non chiffrée. | Prise de contrôle totale du CMS et du gestionnaire de plugins. | Cookies `HttpOnly`, `Secure`, `SameSite=Strict`. Régénération d'ID de session au login. Forçage HTTPS. |
| **Tampering** | Altération du manifeste d'un plugin (`plugin.json`) | Injection de chemins relatifs malveillants (`../../`) pour charger des classes arbitraires. | Exécution de code non autorisée / Path Traversal. | Validation par schéma JSON strict. Résolution des chemins via `realpath()` restreinte au répertoire racine du plugin. |
| **Tampering** | Altération de tables d'autres plugins | Un plugin modifie ou supprime directement des tables SQL n'appartenant pas à son périmètre. | Corruption de données, perte d'intégrité. | Conventions de nommage des tables avec préfixes obligatoires (`plugin_{slug}_*`). Politiques de révision de code. |
| **Repudiation** | Répudiation d'une action destructive | Un administrateur désactive ou purge un plugin sans laisser de traces. | Impossibilité d'audit post-incident. | Journalisation centralisée immuable de toutes les actions du cycle de vie des plugins (`cms_audit_logs`). |
| **Information Disclosure** | Exposition d'erreurs et de secrets en production | Un plugin lève une exception non interceptée et affiche les variables d'environnement (`APP_KEY`, DB passwords). | Fuite d'identifiants critiques. | `APP_DEBUG=false` obligatoire en production. Handler d'erreurs du noyau interceptant et masquant les traces brutes des plugins. |
| **Denial of Service** | Boucle infinie ou dépendance circulaire | Deux plugins se référencent mutuellement, entraînant un débordement de mémoire au boot. | Crash de l'application (HTTP 500) à chaque requête. | Détection de cycles via tri topologique (Kahn) lors de la résolution des dépendances avant chargement. Safe Mode. |
| **Denial of Service** | Plugin provoquant une exception fatale | Un Service Provider de plugin lance une exception non gérée dans `boot()`. | Blocage complet du site et du panneau d'administration. | Mécanisme de récupération d'urgence (Safe Mode via CLI / drapeau cache) permettant d'isoler le plugin défaillant. |
| **Elevation of Privilege** | Contournement du contrôle d'accès aux fonctions plugins | Un utilisateur sans droit accède directement aux URLs des contrôleurs d'un plugin. | Accès non autorisé à des fonctionnalités réservées. | Enregistrement obligatoire de middlewares d'autorisation et vérification des permissions au niveau des routes du noyau. |

---

## 4. Limites de Confiance & Vérité Technique sur l'Isolation PHP

> **Avertissement Architectural Majeur :**
> En PHP conventionnel, tous les plugins actifs s'exécutent dans le même processus de travail (`php-fpm` ou worker). Par conséquent, un plugin malveillant disposant des droits d'écriture sur le disque ou exécutant des instructions système peut théoriquement altérer l'ensemble de l'environnement.
>
> Pour cette raison :
> 1. L'installation de plugins doit être réservée exclusivement au rôle `super-admin`.
> 2. Le CMS ne fait jamais la fausse promesse d'une "sandbox impénétrable" sans infrastructure de virtualisation dédiée.
> 3. La sécurité opérationnelle repose sur la vérification d'intégrité, la validation des manifestes et la réversibilité rapide en cas d'erreur.
