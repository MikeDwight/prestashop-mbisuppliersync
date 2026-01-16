# mbisuppliersync — README d’entretien

## 1. Contexte & problématique métier

En PME e-commerce, la mise à jour des données fournisseurs (prix, stocks) est souvent :
- manuelle (imports CSV, copier/coller)
- peu fiable (erreurs humaines, données incohérentes)
- peu traçable (difficile d’expliquer ce qui a été modifié)

Ces problèmes ont un impact direct sur :
- la fiabilité du catalogue
- la satisfaction client
- la charge opérationnelle des équipes

**Objectif du module `mbisuppliersync`** :  
Mettre en place une synchronisation fournisseur **simple, stable et traçable**, pilotable depuis le back-office, sans dépendance quotidienne à un développeur.

---

## 2. Périmètre V1

### Inclus (IN)
- Module Prestashop autonome `mbisuppliersync`
- Synchronisation fournisseur via **API REST (pull simulé)**
- Back-office métier :
  - lancement manuel de la synchronisation
  - historique des exécutions (runs)
  - détail par produit (run_item)
- Sécurisation du flux :
  - mode **dry-run** (simulation sans impact catalogue)
  - filtrage des données incohérentes
- Persistance SQL dédiée pour la traçabilité et l’audit

### Exclus volontairement (OUT)
- Planification automatique (cron)
- Gestion multi-fournisseurs
- Création ou suppression de produits
- Logique de pricing avancée (marges, règles complexes)
- Notifications / alerting

Le périmètre est volontairement limité afin de livrer une V1 **réaliste, exploitable et défendable** en contexte PME.

---

## 3. Architecture du module (vue logique)

- **Controller Back-office**
  - écrans de pilotage
  - actions de lancement manuel
- **Service de synchronisation**
  - appel de l’API fournisseur
  - orchestration du flux
- **Couche métier**
  - validation des données
  - application des règles (dry-run / réel)
- **Persistance SQL**
  - table `run` : une exécution globale
  - table `run_item` : détail par produit synchronisé

Objectif : une architecture lisible, découplée et facilement explicable en entretien.

---

## 4. Flux de synchronisation (pas à pas)

1. Un utilisateur lance une synchronisation depuis le back-office.
2. Le module appelle l’API fournisseur (pull simulé).
3. Les données reçues sont analysées produit par produit :
   - identification du produit cible
   - validation des valeurs (prix, stock)
   - rejet explicite des données incohérentes
4. Selon le mode choisi :
   - **dry-run** : aucune modification du catalogue, mais le run est tracé
   - **réel** : mise à jour des données produit + traçage complet
5. Chaque exécution génère :
   - un `run` (statut, durée, mode, compteurs)
   - plusieurs `run_item` (résultat détaillé par produit)
6. Les résultats sont consultables immédiatement dans le back-office.

Propriété clé : **traçabilité systématique**, y compris en cas d’erreurs partielles.

---

## 5. Choix techniques clés & justifications

- **Lancement manuel**
  - contrôle total côté métier
  - évite les traitements automatiques opaques
- **Mode dry-run natif**
  - sécurise le catalogue
  - permet de valider les données avant impact réel
- **Tables `run` / `run_item`**
  - auditabilité complète
  - support et diagnostic facilités
- **Filtrage strict des données**
  - priorité à la stabilité et à la qualité des données
- **Périmètre volontairement restreint**
  - crédible pour une PME
  - évolutif sans refonte technique

---

## 6. Compétences démontrées par le projet

- développement de module Prestashop
- intégration back-office orientée métier
- orchestration de flux API REST
- modélisation SQL orientée traçabilité
- sécurisation des flux critiques
- approche pragmatique et exploitable en production

---

## 7. Finalité du projet

Le module `mbisuppliersync` sert de **support d’entretien technique** pour démontrer la capacité à :
- comprendre un besoin métier e-commerce réel
- proposer une solution simple et robuste
- travailler efficacement dans un contexte PME Prestashop
