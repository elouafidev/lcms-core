# ADR-0002 : Spécification des Manifestes et Découplage par Contrats Publics

**Statut :** Accepté
**Date :** 2026-10-12
**Auteurs :** Équipe d'Architecture CMS

---

## 1. Contexte et Problématique
Pour qu'un écosystème de plugins tiers reste stable à long terme, le CMS doit savoir exactement comment découvrir, valider, charger et ordonnancer les extensions sans supposer leur structure interne et sans tolérer de comportements erratiques.

## 2. Décision
1. Chaque extension doit obligatoirement inclure un manifeste standardisé `plugin.json` à sa racine.
2. Tout manifeste est validé contre un schéma formel strict avant toute tentative d'exécution ou d'instanciation de code PHP.
3. Les interactions entre le noyau et les plugins reposent sur des interfaces PHP strictes (`app/Core/Contracts/`), évitant toute adhérence avec les classes concrètes internes du noyau.

## 3. Justifications et Bénéfices
- **Sécurité et intégrité :** La validation préalable du manifeste prévient les attaques par injection de chemins (Path Traversal) et bloque immédiatement les plugins dont la syntaxe ou les dépendances sont corrompues.
- **Stabilité de l'API :** Les développeurs de plugins s'appuient sur des contrats d'interfaces immuables garantis par la politique SemVer du CMS, permettant des mises à niveau transparentes du noyau sans casser les extensions existantes.
- **Découplage absolu :** Le noyau peut être refactorisé sans impacter les plugins tant que les contrats d'interface sont respectés.

## 4. Conséquences et Contraintes
- **Rigueur accrue pour les auteurs de plugins :** Un plugin dépourvu de `plugin.json` valide ne sera tout simplement pas découvert par le CMS.
- **Maintenance des contrats :** Les contrats publics doivent être rédigés avec une grande minutie dès la Phase 1 car toute modification d'interface ultérieure imposera un incrément de version majeure (SemVer).
