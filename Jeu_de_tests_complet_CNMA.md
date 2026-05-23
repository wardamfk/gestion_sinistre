# Jeu de tests complet — Application de gestion des sinistres (CNMA)

Date : 2026-05-20  
Projet : **PfeCnma** (PHP / MySQL)  
Objectif : disposer d’un **script de démo + jeu de tests** couvrant **toutes les fonctionnalités présentes** pour une soutenance / jury.

---

## 1) Périmètre & rôles

L’application est organisée en 3 espaces :

- **CNMA (Administration)** : pages dans `cnma/pages/*`
- **CRMA (Agent)** : pages dans `cnma/crma/*` + layout `cnma/includes/*`
- **ASSURÉ** : pages dans `cnma/assure/*`

Fonctionnalités globales (d’après le projet) :
- Gestion assurés / personnes
- Gestion tiers adverses
- Gestion véhicules
- Gestion contrats (+ impression)
- Création & gestion dossiers sinistres (workflow d’états)
  - Documents (upload/suppression)
  - Expertise
  - Réserves
  - Règlements & statuts
  - Encaissements (recours)
  - Historique des actions
- Validation CNMA (valider / refuser / demander complément)
- Statistiques CNMA
- Notifications (CRMA / ASSURÉ)
- Gestion utilisateurs (CNMA)

---

## 2) Préparation (avant la démo)

### 2.1 Installation / Base de données
1. Démarrer Apache + MySQL (WAMP/XAMPP).
2. Importer la base `gestion_sinistre` depuis : `bdd/gestion_sinistre (18).sql`
3. Vérifier la connexion dans : `cnma/includes/config.php` (host, user, db, port).
4. Ouvrir l’application :
   - Entrée : `http://localhost/PfeCnma/`
   - Login : `http://localhost/PfeCnma/cnma/pages/login.php`

### 2.2 Comptes de test (recommandé pour le jury)
Comme les mots de passe des comptes déjà présents dans le dump SQL peuvent être inconnus, le plus robuste est de **préparer des comptes “jury”** avec des mots de passe connus.

**Option A (recommandée) :** utiliser un compte CNMA existant, puis créer les comptes CRMA.
- Connexion CNMA → menu **Utilisateurs** : `cnma/pages/gestion_utilisateurs.php`
- Créer un compte **CRMA** (ex: “Agent Jury Alger”) + choisir l’agence **CRMA Alger**.

**Option B :** si vous ne pouvez pas vous connecter en CNMA, réinitialiser le mot de passe CNMA dans la DB.
- Table : `utilisateur`, ligne `role='CNMA'`
- Remplacer `mot_de_passe` par un hash BCrypt généré via PHP (`password_hash`).

### 2.3 Jeux de données “jury” (à créer une seule fois)
L’idée est d’avoir **1 contrat actif** + **1 tiers** + **1 dossier** pour faire tout le parcours.

Données exemple (à adapter) :
- **Personne/Assuré** : Nom “Jury”, Prénom “Test”, Email `jury.assure@test.dz`, CIN unique
- **Véhicule** : matricule unique (ex: `99999-999-99`), n° chassis unique
- **Contrat** : police unique (ex: `CRMA-ALG-JURY-001`), statut actif, capital + taxe + timbre
- **Tiers adverse** : Nom “Tiers Jury”, tel/email, infos véhicule tiers
- **Dossier** : date sinistre récente, description claire, lieu, etc.
- **Pièces jointes** : préparer 2–3 fichiers (jpg/pdf). Vous pouvez utiliser les fichiers déjà présents dans `cnma/uploads/`.

---

## 3) Parcours “Jury” (script de démo prêt à dérouler)

### Scénario J1 — Démo end-to-end (15–25 min)
Objectif : **montrer le maximum de fonctionnalités** en un parcours logique.

#### Phase 1 — CRMA : production (assuré/contrat)
1. Se connecter en **CRMA**.
2. **Personnes** : créer une personne (si besoin)  
   - Page : `cnma/crma/ajouter_personne.php` (contrôles unicité : CIN/email).
3. **Assurés** : créer l’assuré lié à la personne  
   - Page : `cnma/crma/ajouter_assure.php`
4. **Véhicules** : créer un véhicule  
   - Page : `cnma/crma/ajouter_vehicule.php` (unicité matricule/chassis via `check_unique.php`)
5. **Contrats** : créer un contrat pour l’assuré + associer le véhicule  
   - Page : `cnma/crma/ajouter_contrat.php`
6. **Imprimer contrat**  
   - Page : `cnma/crma/print_contrat.php`
7. **Créer compte ASSURÉ** (pour montrer l’espace assuré)  
   - Page : `cnma/crma/creer_compte_assure.php`

Résultat attendu :
- Un contrat actif visible côté CRMA + visible côté assuré.

#### Phase 2 — CRMA : création dossier sinistre + pièces + réserve + expertise + règlement
8. **Créer un dossier** à partir du contrat  
   - Page : `cnma/crma/creer_dossier.php`
9. **Ajouter/choisir un tiers adverse**  
   - Pages : `cnma/crma/gerer_tiers.php`, `cnma/crma/ajouter_tiers.php`
10. Ouvrir **Voir dossier** et déposer des **documents**  
   - Page : `cnma/crma/voir_dossier.php` + upload `cnma/crma/upload_document.php`
11. Ajouter une **réserve** (puis modifier)  
   - Pages : `cnma/crma/ajouter_reserve.php`, `cnma/crma/modifier_reserve.php`
12. Ajouter une **expertise** (puis modifier / supprimer si nécessaire)  
   - Pages : `cnma/crma/ajouter_expertise.php`, `cnma/crma/modifier_expertise.php`, `cnma/crma/supprimer_expertise.php`
13. Ajouter un **règlement** (partiel) puis valider le statut côté CRMA  
   - Pages : `cnma/crma/ajouter_reglement.php`, `cnma/crma/gerer_reglement_statut.php`

Résultat attendu :
- Le dossier contient documents + réserve(s) + expertise(s) + règlement(s).
- L’historique du dossier se remplit (action, date, ancien/nouvel état).

#### Phase 3 — Transmission CNMA + décision (complément → validation)
14. **Transmettre le dossier à CNMA**  
   - Action via : `cnma/crma/changer_etat_dossier.php` (état “Transmis CNMA”)
15. Se connecter en **CNMA**, ouvrir **Dossiers attente**  
   - Page : `cnma/pages/dossiers_attente.php`
16. Demander un **complément** (motif obligatoire)  
   - Page : `cnma/pages/complement_cnma.php`
17. Revenir en **CRMA**, ouvrir le dossier, ajouter l’info complémentaire + re-transmettre.
18. Revenir en **CNMA**, **Valider** le dossier  
   - Page : `cnma/pages/valider_cnma.php`

Résultat attendu :
- Le dossier passe par : Transmis CNMA → Complément demandé → Transmis CNMA → Validé CNMA.
- Une notification est envoyée (au minimum côté **ASSURÉ** si prévu par le workflow).

#### Phase 4 — ASSURÉ : consultation + notifications
19. Se connecter en **ASSURÉ**
20. Vérifier :
   - Mes contrats : `cnma/assure/mes_contrats.php`
   - Mes dossiers : `cnma/assure/mes_dossiers_assure.php`
   - Notifications : `cnma/assure/notifications_assure.php` (marquer lu / tout marquer lu)

#### Phase 5 — Clôture + statistiques
21. Clôturer le dossier (CNMA ou CRMA selon votre process)  
   - Pages : `cnma/pages/cloturer_dossier_cnma.php` et/ou `cnma/crma/cloturer_dossier.php`
22. CNMA : vérifier **Historique global** + **Statistiques**  
   - Pages : `cnma/pages/historique_global.php`, `cnma/pages/statistiques_cnma.php`

---

## 4) Jeu de tests complet (checklist exhaustive par fonctionnalité)

> Format : chaque cas = **Préconditions / Étapes / Résultat attendu** + page(s).

### A) Authentification & sécurité (tous rôles)

**TC-AUTH-01 — Connexion valide (CNMA/CRMA/ASSURÉ)**
- Pages : `cnma/pages/login.php`
- Préconditions : compte actif existant
- Étapes :
  1. Saisir email + mot de passe valides
  2. Valider
- Résultat attendu :
  - Redirection selon rôle :
    - CNMA → `dashboard_cnma.php`
    - CRMA → `crma/dashboard_crma.php`
    - ASSURÉ → `assure/dashboard_assure.php`

**TC-AUTH-02 — Connexion invalide**
- Étapes : email valide + mauvais mot de passe
- Résultat attendu : message “Identifiants incorrects”, aucune session créée.

**TC-AUTH-03 — Compte inactif**
- Préconditions : un ASSURÉ désactivé via CNMA (voir TC-CNMA-USER-04)
- Étapes : tenter login
- Résultat attendu : refus (le login filtre `actif=1`).

**TC-AUTH-04 — Accès direct à une page protégée sans session**
- Pages : ex `cnma/crma/dashboard_crma.php`, `cnma/pages/dashboard_cnma.php`, `cnma/assure/dashboard_assure.php`
- Étapes : ouvrir l’URL directement en navigation privée
- Résultat attendu : redirection vers `cnma/pages/login.php`

**TC-AUTH-05 — Déconnexion**
- Pages : `cnma/pages/logout.php`, `cnma/crma/logout.php`, `cnma/assure/logout.php`
- Résultat attendu : session détruite, retour login.

---

### B) CRMA — Personnes / Assurés

**TC-CRMA-PERS-01 — Ajouter personne (OK)**
- Page : `cnma/crma/ajouter_personne.php`
- Étapes : saisir identité + CIN + email uniques
- Résultat attendu : personne créée, visible dans `gerer_personnes.php`

**TC-CRMA-PERS-02 — Contrôle CIN unique**
- Pages : `cnma/crma/check_cin.php`
- Étapes : ressaisir un CIN existant
- Résultat attendu : blocage / message d’erreur (selon UI).

**TC-CRMA-PERS-03 — Contrôle email unique**
- Pages : `cnma/crma/check_email.php`
- Étapes : ressaisir un email existant
- Résultat attendu : blocage / message d’erreur.

**TC-CRMA-ASS-01 — Ajouter assuré**
- Page : `cnma/crma/ajouter_assure.php`
- Préconditions : personne existante
- Résultat attendu : assuré créé, visible dans `gerer_assures.php`

**TC-CRMA-ASS-02 — Créer compte ASSURÉ**
- Page : `cnma/crma/creer_compte_assure.php`
- Préconditions : personne sans compte
- Résultat attendu : utilisateur role=ASSURE créé + entrée dans table `assure` si absente.

---

### C) CRMA — Véhicules / Contrats

**TC-CRMA-VEH-01 — Ajouter véhicule**
- Page : `cnma/crma/ajouter_vehicule.php`
- Résultat attendu : véhicule visible dans `gerer_vehicules.php`

**TC-CRMA-VEH-02 — Unicité matricule / chassis**
- Page : `cnma/crma/check_unique.php`
- Étapes : tenter création avec matricule déjà existant
- Résultat attendu : refus.

**TC-CRMA-CONTR-01 — Ajouter contrat**
- Page : `cnma/crma/ajouter_contrat.php`
- Préconditions : assuré + véhicule
- Résultat attendu : contrat visible dans `gerer_contrats.php` + police unique.

**TC-CRMA-CONTR-02 — Modifier véhicule d’un contrat**
- Page : `cnma/crma/modifier_vehicule_contrat.php`
- Résultat attendu : véhicule associé mis à jour.

**TC-CRMA-CONTR-03 — Impression contrat**
- Page : `cnma/crma/print_contrat.php`
- Résultat attendu : page imprimable / document généré (selon implémentation).

---

### D) CRMA — Tiers / Experts

**TC-CRMA-TIERS-01 — Ajouter tiers adverse**
- Page : `cnma/crma/ajouter_tiers.php`
- Résultat attendu : tiers visible dans `gerer_tiers.php`

**TC-CRMA-EXPERT-01 — Ajouter / lister expert**
- Page : `cnma/crma/gerer_experts.php`
- Résultat attendu : expert visible et sélectionnable dans une expertise.

---

### E) CRMA — Dossiers sinistres (création & consultation)

**TC-DOS-01 — Créer dossier**
- Page : `cnma/crma/creer_dossier.php`
- Préconditions : contrat + tiers
- Résultat attendu : dossier créé, visible dans `mes_dossiers.php`

**TC-DOS-02 — Consulter dossier**
- Page : `cnma/crma/voir_dossier.php`
- Résultat attendu : affichage complet (contrat, assuré, tiers, état, historique, documents, réserves…).

**TC-DOS-03 — Changer état (workflow)**
- Page : `cnma/crma/changer_etat_dossier.php`
- Étapes : changer vers un état autorisé (ex: “Transmis CNMA”)
- Résultat attendu :
  - État dossier mis à jour
  - Insertion dans `historique`
  - Motif demandé si l’état l’exige (voir table `etat_dossier.motif_obligatoire`)

**TC-DOS-04 — Chargement motifs (AJAX)**
- Page : `cnma/crma/get_motifs.php`
- Étapes : sélectionner un état “motif obligatoire”
- Résultat attendu : liste motifs proposée.

---

### F) Documents (upload & suppression)

**TC-DOC-01 — Upload document**
- Page : `cnma/crma/upload_document.php`
- Préconditions : dossier existant
- Étapes :
  1. Sélectionner un type (Constat/PV/Photos…)
  2. Choisir un fichier (jpg/pdf)
  3. Valider
- Résultat attendu :
  - fichier stocké (répertoire uploads)
  - ligne créée dans table `document`

**TC-DOC-02 — Supprimer un document**
- Page : `cnma/crma/supprimer_document.php`
- Résultat attendu : document supprimé (DB + fichier si implémenté).

**TC-DOC-03 — Supprimer plusieurs documents**
- Page : `cnma/crma/supprimer_documents.php`
- Résultat attendu : suppression multiple.

---

### G) Réserves

**TC-RES-01 — Ajouter réserve**
- Page : `cnma/crma/ajouter_reserve.php`
- Résultat attendu : réserve listée dans le dossier, total réserve recalculé si prévu.

**TC-RES-02 — Modifier réserve**
- Page : `cnma/crma/modifier_reserve.php`
- Résultat attendu : montant/infos mis à jour.

**TC-RES-03 — Supprimer réserve**
- Page : `cnma/crma/supprimer_reserve.php`
- Résultat attendu : réserve supprimée.

---

### H) Expertise

**TC-EXP-01 — Ajouter expertise**
- Page : `cnma/crma/ajouter_expertise.php`
- Résultat attendu : expertise créée, expert associé.

**TC-EXP-02 — Modifier expertise (rapport/montant/commentaire)**
- Page : `cnma/crma/modifier_expertise.php`
- Résultat attendu : données mises à jour.

**TC-EXP-03 — Supprimer expertise**
- Page : `cnma/crma/supprimer_expertise.php`
- Résultat attendu : expertise supprimée.

---

### I) Règlements & encaissements

**TC-REG-01 — Ajouter règlement**
- Page : `cnma/crma/ajouter_reglement.php`
- Résultat attendu : règlement ajouté, visible dans `liste_reglements.php`.

**TC-REG-02 — Modifier règlement**
- Page : `cnma/crma/modifier_reglement.php`
- Résultat attendu : règlement mis à jour.

**TC-REG-03 — Supprimer règlement**
- Page : `cnma/crma/supprimer_reglement.php`
- Résultat attendu : règlement supprimé.

**TC-REG-04 — Gérer statut règlement**
- Page : `cnma/crma/gerer_reglement_statut.php`
- Résultat attendu : statut mis à jour, impact sur l’état dossier si implémenté.

**TC-REG-05 — Confirmer remise totale**
- Page : `cnma/crma/confirmer_remise_totale.php`
- Résultat attendu : bascule vers règlement définitif (si logique appliquée).

**TC-ENC-01 — Ajouter encaissement (recours)**
- Page : `cnma/crma/ajouter_encaissement.php`
- Résultat attendu : encaissement enregistré et visible dans le dossier.

---

### J) Notifications (CRMA / ASSURÉ)

**TC-NOTIF-CRMA-01 — Liste notifications CRMA**
- Page : `cnma/crma/notifications.php`
- Résultat attendu : affichage, possibilité de marquer lu si présent.

**TC-NOTIF-CRMA-02 — Badge compteur (AJAX)**
- Page : `cnma/crma/notification_count.php`
- Résultat attendu : le badge se met à jour (refresh 30s).

**TC-NOTIF-ASS-01 — Liste notifications assuré**
- Page : `cnma/assure/notifications_assure.php`
- Étapes : consulter puis “marquer lu” / “tout marquer lu”
- Résultat attendu : champ `lu` mis à jour, badge diminue.

---

### K) CNMA — Validation & suivi

**TC-CNMA-01 — Dossiers en attente**
- Page : `cnma/pages/dossiers_attente.php`
- Préconditions : dossier en état “Transmis CNMA”
- Résultat attendu : dossier listé.

**TC-CNMA-02 — Voir dossier côté CNMA**
- Page : `cnma/pages/voir_dossier_cnma.php`
- Résultat attendu : détails dossier visibles + historique.

**TC-CNMA-03 — Valider dossier**
- Page : `cnma/pages/valider_cnma.php`
- Résultat attendu : état “Validé CNMA”, historique enregistré.

**TC-CNMA-04 — Refuser dossier (motif obligatoire)**
- Page : `cnma/pages/refuser_cnma.php`
- Résultat attendu : état “Refusé CNMA” + motif + message assure (si implémenté).

**TC-CNMA-05 — Demander complément (motif obligatoire)**
- Page : `cnma/pages/complement_cnma.php`
- Résultat attendu : état “Complément demandé” + notification associée.

**TC-CNMA-06 — Clôturer dossier (CNMA)**
- Page : `cnma/pages/cloturer_dossier_cnma.php`
- Résultat attendu : état “Clôturé”, date clôture renseignée.

---

### L) CNMA — Statistiques & historique global

**TC-CNMA-STAT-01 — Statistiques**
- Page : `cnma/pages/statistiques_cnma.php`
- Résultat attendu : indicateurs affichés (volumes, états, etc. selon implémentation).

**TC-CNMA-HIST-01 — Historique global**
- Page : `cnma/pages/historique_global.php`
- Résultat attendu : liste chronologique des actions (filtres si présents).

---

### M) CNMA — Gestion utilisateurs

**TC-CNMA-USER-01 — Créer utilisateur CNMA**
- Page : `cnma/pages/gestion_utilisateurs.php`
- Étapes : nom + email + mdp >= 6 + rôle CNMA
- Résultat attendu : utilisateur créé.

**TC-CNMA-USER-02 — Créer utilisateur CRMA**
- Même page, rôle CRMA + agence obligatoire
- Résultat attendu : utilisateur CRMA créé et rattaché à l’agence.

**TC-CNMA-USER-03 — Email déjà utilisé**
- Étapes : créer un compte avec email existant
- Résultat attendu : message “Cet email est déjà utilisé…”.

**TC-CNMA-USER-04 — Activer / désactiver un ASSURÉ**
- Étapes : action “toggle” sur un assuré
- Résultat attendu : `actif` bascule (1/0), login refusé si inactif.

---

### N) ASSURÉ — Consultation (contrats, dossiers, paiements, profil)

**TC-ASS-01 — Mes contrats**
- Page : `cnma/assure/mes_contrats.php`
- Résultat attendu : liste contrats de l’assuré connecté.

**TC-ASS-02 — Mes dossiers**
- Page : `cnma/assure/mes_dossiers_assure.php`
- Résultat attendu : liste dossiers liés aux contrats de l’assuré.

**TC-ASS-03 — Mes paiements**
- Page : `cnma/assure/mes_paiements.php`
- Résultat attendu : paiements/règlements affichés selon données.

**TC-ASS-04 — Profil assuré**
- Page : `cnma/assure/mon_profil.php`
- Résultat attendu : informations personnelles affichées (et modifiables si prévu).

---

## 5) Notes pratiques “jour J”

- Toujours dérouler la démo avec **3 onglets** : CRMA / CNMA / ASSURÉ (ou 3 navigateurs) pour illustrer le workflow et les notifications.
- Préparer 2 dossiers :
  - 1 dossier “déjà avancé” (avec documents + expertise + règlement) au cas où la saisie prend du temps.
  - 1 dossier “neuf” pour montrer la création rapide.
- Pour les états **motif obligatoire** (Refusé CNMA, Complément demandé, Classé sans suite, etc.), prévoir un motif et expliquer au jury : “le système impose un motif pour traçabilité”.

