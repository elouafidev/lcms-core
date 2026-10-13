# ADR-0001 : Choix d'une Architecture en Monolithe Modulaire

**Statut :** Accepté
**Date :** 2026-10-12
**Auteurs :** Équipe d'Architecture CMS

---

## 1. Contexte et Problématique
Le projet vise la création d'un CMS moderne, extensible et facilement déployable, capable d'alimenter divers scénarios (blogs, vitrines, e-commerce, headless).
Deux grandes options d'architecture s'opposaient :
1. Une architecture distribuée orientée microservices (chaque plugin étant un service autonome avec sa propre base de données et API réseau).
2. Une architecture en monolithe modulaire Laravel, unifiant le noyau d'exécution et isolant logiquement les extensions dans des répertoires dédiés (`platform/plugins/*`).

## 2. Décision
Nous retenons formellement le **Monolithe Modulaire Laravel**.
- L'ensemble du système s'exécute au sein d'une seule instance Laravel.
- Le noyau (`app/Core`) fournit l'infrastructure sans aucune connaissance métier.
- Les plugins sont physiquement isolés dans `platform/plugins/` avec leurs propres espaces de noms, migrations, contrôleurs et vues.
- La communication inter-modules est régie par des contrats publics (`app/Core/Contracts`), des événements typés et des hooks.

## 3. Justifications et Bénéfices
- **Simplicité opérationnelle :** Déploiement unifié sur un serveur unique sans la complexité réseau, l'orchestration Kubernetes ou les transactions distribuées (Saga).
- **Performances optimales :** Les appels inter-modules s'effectuent en mémoire PHP via l'injection de dépendances, éliminant la latence réseau des microservices.
- **Transactions ACID :** Possibilité d'encadrer l'installation ou la mise à jour d'un plugin dans une transaction de base de données locale.
- **Courbe d'apprentissage maîtrisée :** Respect des conventions Laravel que maîtrisent déjà les développeurs de l'écosystème.

## 4. Conséquences et Contraintes
- **Discipline requise :** Risque de couplage accidentel si des développeurs importent des classes privées d'autres plugins. *Mesure corrective :* Mise en place de tests d'architecture automatisés interdisant les imports non autorisés.
- **Isolation d'exécution :** En cas d'erreur fatale non gérée au boot d'un plugin, l'ensemble du processus peut s'interrompre. *Mesure corrective :* Implémentation obligatoire d'un mécanisme de Safe Mode et de tolérance aux pannes au niveau du Core.

## 5. Alternatives Écartées
- **Microservices :** Rejeté en raison de la surconsommation de ressources, de la complexité de déploiement pour les petits projets (vitrines, blogs) et de la difficulté à maintenir des transactions atomiques.
- **Monolithe traditionnel couplé :** Rejeté car il viole l'exigence fondatrice d'un noyau minimaliste et d'indépendance des extensions.
