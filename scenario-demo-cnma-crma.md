# Scénario de démonstration (10–15 min) — Application CNMA/CRMA (Contrats & Sinistres)
*(Version “données via l’UI” + scénario réaliste CNMA/CRMA Algérie — couvre les modules existants)*

## 0) Objectif de la démo (30 secondes)
Montrer au jury **le cycle complet d’un sinistre auto** dans une compagnie d’assurance (réseau **CRMA** + supervision **CNMA**) :
1) Production (assuré/contrat) → 2) Déclaration sinistre → 3) Traitement CRMA (expertise/réserves) → 4) Décision CNMA (validation / complément / refus) → 5) Règlement → 6) Clôture → 7) Visibilité côté assuré (dossiers / paiements / notifications).

---

## 1) Comptes à utiliser (connexion)
> Si tu importes le dump `bdd/gestion_sinistre (18).sql`, les comptes suivants existent déjà.
> Dans ton projet, on voit un fichier `cnma/includes/HASH.php` qui génère un hash pour **"1234"** → dans beaucoup de PFE, le mot de passe de démo utilisé est **1234**.  
> **À vérifier 24h avant la soutenance** : test rapide de login ; sinon change le mot de passe via l’écran “Profil” (CNMA / CRMA / Assuré).

| Rôle | Email | Mot de passe (démo) | À montrer |
|---|---|---|---|
| **CNMA (admin)** | `admin@cnma.dz` | `1234` | Tableau de bord, dossiers en attente, validation / complément / refus, stats, gestion utilisateurs |
| **CRMA Alger (agent)** | `alger@crma.dz` | `1234` | Assurés / tiers / contrats, création dossier, expertise, réserves, règlements, encaissements, historique, notifications |
| **Assuré** | `aida.moufouki@gmail.com` | `1234` | Espace assuré : contrats, dossiers, paiements, notifications, profil |

> **Important démo** : garde ces 3 sessions ouvertes dans 3 onglets (ou 3 navigateurs) pour éviter de perdre du temps.

---

## 2) Données exactes (réalistes) à utiliser
Ce scénario se base sur une situation réaliste : **assurée à Alger**, accident matériel avec **tiers assuré chez une autre compagnie**, traitement CRMA puis validation CNMA, règlement amiable, puis clôture.

### 2.1 Contrat de l’assurée (déjà présent dans le dump)
- **Assurée** : *Moufouki Aida*  
  - Email assuré : `aida.moufouki@gmail.com`
- **Contrat** : **`CRMA-ALG-2026-002`** *(agence : CRMA Alger)*  
- **Véhicule** (lié au contrat) : **Peugeot 308**  
  - Matricule : **`12994-122-16`**

### 2.2 Tiers adverse (déjà présent)
- **Tiers** : *Ali Karim*  
- **Compagnie adverse** : **SAA**  
- **N° police adverse** : **`SAA123456`**  
- **Responsabilité** : **oui**  

### 2.3 Expert (déjà présent)
- **Expert automobile** : *Mansouri Nadia* *(existe dans la table `expert` dans le dump)*.

### 2.4 Dossier sinistre à créer pendant la démo
Saisir **exactement** :
- **Date sinistre** : `2026-05-15`
- **Date déclaration** : `2026-05-16`  *(⚠ doit rester ≤ 5 jours sinon refus automatique “hors délai”)*  
- **Lieu** : `Alger – Kouba (Rond-point El Madania)`
- **Description** : `Collision à faible vitesse, dommages pare-chocs avant + optique gauche. Constat amiable disponible.`
- **Infos complémentaires** : `Assurée non blessée. Tiers reconnaît responsabilité.`
- **Réserve initiale (RC)** : `120000` DA *(dégâts matériels estimés)*

### 2.5 Fichiers à uploader pendant la démo
Utilise des fichiers **sans espaces** pour éviter les soucis :
- `BDAU9862.JPG` *(déjà dans `cnma/uploads/` dans ton projet)*  
Choisir comme type document :
- **Constat** (id 1) ou **Photos accident** (id 3) ou **Carte grise** (id 4)

---

## 3) Déroulé optimal (10–15 minutes) — étape par étape
> Format : **Action → Résultat attendu → État dossier / points jury**

### Minute 0:00 – 1:00 — Introduction + architecture logique
1. **Login CRMA (Agent Alger)**  
   - Action : se connecter `alger@crma.dz`
   - Résultat attendu : accès au **Tableau de bord CRMA** avec stats (dossiers / finances / notifications).
   - Point jury : expliquer **séparation des rôles** : CRMA = traitement opérationnel ; CNMA = contrôle/validation.

### Minute 1:00 – 3:00 — Production (Contrat / Assuré / Tiers / Expert) *en “visite guidée rapide”*
2. Menu **Production → Contrats**  
   - Action : rechercher `CRMA-ALG-2026-002`, ouvrir/visualiser, puis **Imprimer contrat** (si bouton présent).
   - Résultat attendu : fiche contrat + véhicule + garanties ; impression/génération.
   - Point jury : montrer la **traçabilité contrat → sinistre**.

3. Menu **Production → Assurés**  
   - Action : retrouver *Moufouki Aida* (email `aida.moufouki@gmail.com`).
   - Résultat attendu : fiche assuré, contrats associés, possibilité de créer compte assuré si absent.
   - Point jury : lien **Personne → Assuré → Contrat → Dossier**.

4. Menu **Production → Tiers adverses**  
   - Action : vérifier le tiers *Ali Karim* avec police `SAA123456` et responsabilité **oui**.
   - Résultat attendu : fiche tiers existante.
   - Point jury : sinistres RC/recours, tiers responsable.

5. Menu **Production → Experts**  
   - Action : vérifier *Mansouri Nadia*.
   - Résultat attendu : liste experts (gestion référentiel).

*(Objectif : prouver que les référentiels existent. Pas besoin de créer en live pour tenir 15 min.)*

### Minute 3:00 – 6:00 — Création du dossier sinistre (CRMA)
6. Menu **Sinistres → Nouveau dossier**
   - Action : créer un dossier avec les **données exactes** (section 2.4) :
     - Contrat : `CRMA-ALG-2026-002` (Peugeot 308)
     - Tiers : Ali Karim (SAA / `SAA123456`, responsable oui)
     - Expert : Mansouri Nadia
     - Dates : sinistre `2026-05-15`, déclaration `2026-05-16`
     - Réserve RC : `120000`
   - Résultat attendu :
     - Message succès + redirection vers **voir_dossier**
     - **Numéro dossier généré automatiquement** : avec le dump actuel, le prochain devrait être **`DOS-ALG-2026-0015`**.
   - **État attendu** : **En cours CRMA (id_etat=2)**.
   - Point jury :
     - génération automatique du numéro par agence/année,
     - calcul du délai déclaration,
     - réserve initiale.

7. Dans le dossier → onglet **Documents**
   - Action : uploader `BDAU9862.JPG` en type **Photos accident**.
   - Résultat attendu : le document apparaît dans la liste des documents du dossier.
   - Point jury : “dossier complet = pièces justificatives”.
   - Erreurs à éviter :
     - ne pas uploader 2 fois le **même nom** (le fichier est écrasé côté serveur),
     - préférer un nom simple sans espaces.

8. Dans le dossier → onglet **Expertise**
   - Action : ajouter une expertise :
     - Date expertise : `2026-05-17`
     - Montant indemnité : `115000`
     - Commentaire : `Choc pare-chocs + optique gauche, devis conforme.`
   - Résultat attendu : expertise enregistrée + entrée dans l’historique.
   - **État** : reste **En cours CRMA** (l’état est géré séparément).

### Minute 6:00 – 8:00 — Transmission à la CNMA (contrôle)
9. Dans le dossier → onglet **Info / État**
   - Action : changer l’état vers **Transmis CNMA (id_etat=3)**.
   - Résultat attendu :
     - état passe à “Transmis CNMA”
     - historique : “Changement d’état → Transmis CNMA”
   - Point jury : séparation “agent prépare → CNMA décide”.

### Minute 8:00 – 10:30 — Décision CNMA (validation) + traçabilité
10. **Logout CRMA** (ou nouvel onglet) → Login **CNMA** `admin@cnma.dz`
    - Action : ouvrir **Dossiers attente**.
    - Résultat attendu : le dossier `DOS-ALG-2026-0015` apparaît.
    - Point jury : la CNMA voit uniquement ce qui est transmis.

11. CNMA → ouvrir le dossier → **Valider**
    - Action : valider le dossier.
    - Résultat attendu :
      - état passe à **Validé CNMA (id_etat=4)**,
      - historique : “Validation CNMA”,
      - **notification envoyée à l’agent CRMA** (type `validation`).
    - Point jury : traçabilité, date validation, séparation des pouvoirs.

*(Option 30s si tu veux montrer “Complément” sans casser le scénario : juste ouvrir la fenêtre/choix motifs, expliquer, puis annuler.)*

### Minute 10:30 – 13:30 — Règlement + clôture (CRMA)
12. Retour CRMA (Agent Alger) → **Notifications**
    - Résultat attendu : une notification “Dossier validé par la CNMA”.
    - Point jury : communication interne CNMA → CRMA.

13. Ouvrir `DOS-ALG-2026-0015` → onglet **Règlements**
    - Action : ajouter un règlement **120000** DA (chèque).
    - Résultat attendu :
      - règlement enregistré + référence chèque générée automatiquement,
      - **état dossier** devient **Règlement définitif amiable (id_etat=8)** car total réglé ≥ total réserve,
      - notification “paiement finalisé” envoyée à l’assuré (dans l’app ; email si SMTP configuré).
      - **(Cas spécial) Réserve complémentaire automatique** : si tu saisis un règlement **supérieur** au total réserve actif, l’application peut créer automatiquement une **réserve complémentaire** (type `complementaire`) pour couvrir l’écart, afin de garder la cohérence comptable (réserve = engagement, règlement = paiement).  
        → Donc en démo, mets **règlement = réserve** si tu ne veux pas déclencher ce mécanisme.
    - Point jury :
      - cohérence réserve ↔ règlement,
      - génération de référence,
      - notification.

14. Clôture
    - Action : cliquer **Clôturer dossier** (autorisé uniquement si état=8).
    - Résultat attendu :
      - état passe à **Clôturé (id_etat=14)**,
      - historique : “Clôture dossier”.
    - Point jury : règle métier “on ne clôture pas tant que non réglé totalement”.

### Minute 13:30 – 15:00 — Vue Assuré (preuve de valeur)
15. Login **Assuré** `aida.moufouki@gmail.com`
    - Action : ouvrir **Mes dossiers** puis `DOS-ALG-2026-0015`.
    - Résultat attendu : dossier visible + état (validé / réglé / clôturé) selon étape.

16. **Mes paiements**
    - Résultat attendu : règlement visible (montant / statut).

17. **Notifications**
    - Résultat attendu : notification de règlement finalisé (et éventuellement de clôture).
    - Point jury : transparence et suivi client.

---

## 3-bis) (Très demandé par le jury) : Complément CNMA + Refus CNMA (mini-démo sans casser le scénario)
Le plus simple est de **montrer des dossiers déjà existants** (issus du dump), pour ne pas “casser” ton dossier principal.

### A) Complément CNMA (état 6 — motif obligatoire)
**Objectif** : montrer le cas “CNMA demande des documents”.

1) Login **CNMA** → menu **Tous dossiers**  
2) Ouvrir un dossier en état **“Complément demandé”** (id_etat=6).  
   - Exemple dans ton dump : il existe des dossiers en état 6 (ex: `DOS-ALG-2026-0007` apparaît dans les notifications de complément).
3) Montrer :
   - **Motif** (ex: “Facture de réparation manquante”, “PV de police manquant”, “Carte grise manquante”…)
   - **Historique** : action “Demande de complément CNMA” + ancien/nouvel état + motif
   - **Notifications** :
     - notification envoyée au **CRMA** (agent) pour compléter,
     - notification envoyée à l’**assuré** (dans l’app ; email seulement si SMTP OK).

> À expliquer au jury : “Complément” = dossier **non rejeté**, il est **en attente de pièces**. Le CRMA complète puis **re-transmet** à la CNMA (retour à l’état 3).

### B) Refus CNMA (état 5 — motif obligatoire)
**Objectif** : montrer le cas “CNMA refuse avec justification”.

1) Login **CNMA** → menu **Tous dossiers**  
2) Ouvrir un dossier en état **“Refusé CNMA”** (id_etat=5).  
   - Exemple dans ton dump : `DOS-ALG-2026-0002` est en refus (voir notifications).
3) Montrer :
   - **Motif** (ex: “Sinistre non couvert par le contrat”, “Montant du dommage inférieur à la franchise”…)
   - **Historique** : “Refus dossier CNMA” + motif + date de refus
   - **Notifications** :
     - CRMA reçoit “refus”,
     - l’assuré reçoit un message (et email si SMTP OK).

> À expliquer au jury : “Refus” = fin du traitement CNMA sur ce dossier, sauf **recours** / reprise selon procédure.

---

## 4) Points importants à “vendre” au jury (pitch technique + métier)
1) **Workflow métier complet** : CRMA traite, CNMA contrôle, assuré suit.  
2) **Traçabilité** : historique des actions, dates, acteurs, états.  
3) **Automatisation** : numéro de dossier auto, délai de déclaration, bascule d’état selon règlement/réserve.  
4) **Communication** : notifications internes + notifications assurés (et emails si SMTP OK).  
5) **Sécurité / rôles** : pages protégées (CNMA/CRMA/ASSURE), sessions séparées.

---

## 5) Erreurs possibles à éviter (très important le jour J)
- **Délai déclaration > 5 jours** : la création peut basculer en “Refusé (déclaration hors délai)” automatiquement.  
  → Toujours mettre date déclaration proche de la date sinistre.
- **Upload** : même nom de fichier = écrasement ; nom avec espaces/accents = risque.  
  → Utiliser `BDAU9862.JPG` ou renommer avant.
- **SMTP** : si non configuré, les emails peuvent apparaître “failed” (sans bloquer la démo).  
  → Ne pas insister sur l’email ; montrer plutôt la **notification in-app**.
- **Clôture** : impossible si pas réglé totalement (règle métier).  
  → Règlement = total réserve pour passer à l’état 8.
- **Règlement > réserve** : peut déclencher une **réserve complémentaire automatique** (selon la logique de ton module règlements).  
  → Pour une démo “propre”, garde **règlement = réserve**.
- **Changement d’état avec motif obligatoire** : certains états exigent un motif (complément/refus/classement…).  
  → Si tu testes ces chemins, sélectionner un motif dans la liste.

---

## 6) Encaissements détaillés (3 types principaux : Franchise / Recours / Épave)
> Dans l’UI (CRMA → Voir dossier → onglet **Encaissements**), tu as les types : **recours**, **franchise**, **epave** (et parfois “autre”).  
> Règle technique dans ton code : encaissement autorisé seulement si le **tiers est responsable** (`responsable = oui` ou `partiel`) et si le dossier est dans un état autorisé (ex: 7,8,13,14,19,20).

### Type 1 — Recours
**Quand ?** Après indemnisation, la CRMA récupère une somme auprès du tiers / sa compagnie (ex: SAA, CAAR…).  
**Exemple démo** :
- Dans ton dossier clôturé (ou en “Gestion pour recours”), onglet Encaissements :
  - Type : **Recours**
  - Tiers : Ali Karim — SAA
  - Montant : `50000` DA
  - Commentaire : `Recours accepté par la compagnie adverse (SAA) — paiement reçu.`
**Résultat attendu** :
- Nouvelle ligne dans tableau encaissements,
- Historique : “Encaissement enregistré — recours”.

### Type 2 — Franchise
**Quand ?** L’assuré paye une franchise (ou une partie) selon contrat.  
**Exemple démo** :
- Type : **Franchise**
- Montant : `10000` DA
- Commentaire : `Franchise contractuelle encaissée auprès de l’assuré.`
**Résultat attendu** :
- Encaissement visible + historique.

### Type 3 — Épave
**Quand ?** Cas véhicule économiquement irréparable : récupération valeur épave / vente / cession.  
**Exemple démo** :
- Type : **Épave**
- Montant : `80000` DA
- Commentaire : `Récupération valeur épave via mise en vente.`
**Résultat attendu** :
- Encaissement visible + historique.

> Conseil démo : fais **un seul encaissement** (recours) si tu veux rester dans 15 minutes. Les 2 autres types : tu les expliques rapidement.

---

## 7) États du dossier : signification, quand les mettre, et motifs (si obligatoires)
Les états sont gérés par la table `etat_dossier`. Certains exigent un **motif obligatoire** (table `motif`).

| ID | État | Quand le mettre ? (règle métier) | Motif obligatoire ? | Exemples de motifs (si applicable) |
|---|---|---|---|---|
| 1 | Brouillon | Dossier commencé mais pas prêt (si ton UI l’utilise) | Non | — |
| 2 | En cours CRMA | Après création (dossier en traitement agence) | Non | — |
| 3 | Transmis CNMA | Quand le CRMA a complété (docs + réserve + info) et envoie pour décision | Non | — |
| 4 | Validé CNMA | CNMA accepte le dossier → autorise le règlement | Non | — |
| 5 | Refusé CNMA | CNMA refuse le dossier (fin de traitement CNMA) | **Oui** | “Sinistre non couvert par le contrat”, “Montant inférieur à la franchise” |
| 6 | Complément demandé | CNMA demande des pièces/info avant décision | **Oui** | “PV police manquant”, “Facture réparation manquante”, “Carte grise manquante”, “Rapport d’expertise manquant” |
| 7 | Règlement partiel | Après 1er paiement si total réglé < total réserve | Non | — |
| 8 | Règlement définitif amiable | Quand total réglé >= total réserve (paiement complet) | Non | — |
| 9 | En cours d’expertise | Quand le dossier est envoyé à l’expert / expertise en cours | Non | — |
| 11 | Classé sans suite | Dossier classé sans suite (ex: absence de pièces, fraude, prescription…) | **Oui** | “Absence de garantie…”, “Sinistre hors période…”, “Fausse déclaration…” |
| 12 | Classé après rejet | Classement administratif après refus CNMA | Non | — |
| 13 | Classé en attente recours | Dossier mis en attente de recours/retour tiers | Non | — |
| 14 | Clôturé | **Uniquement après règlement définitif** (état 8) | Non | — |
| 15 | Repris | Réouverture / reprise d’un dossier classé (procédure) | **Oui** | “Réception d’une citation…”, “Réclamation fondée…”, “Réouverture pour erreur…” |
| 16 | En cours de contre-expertise | Contre-expertise demandée | Non | — |
| 17 | Règlement définitif judiciaire | Cas contentieux / judiciaire | Non | — |
| 18 | Repris pour recours abouti | Reprise après succès d’un recours | Non | — |
| 19 | Classé après recours abouti | Classement final après recours abouti | Non | “Encaissement du recours” (selon pratique) |
| 20 | Gestion pour recours | On bascule en gestion de recours (tiers responsable / recouvrement) | **Oui** | “Responsabilité de l’assuré dégagée entièrement” |
| 21 | Refusé (déclaration hors délai) | **Automatique** à la création si délai déclaration > 5 jours | **Oui** | (Motif implicite : hors délai) |

### Règles simples à dire au jury (ultra claires)
- **CRMA** : crée + complète + met réserve/expertise + **transmet** (2 → 3).  
- **CNMA** : décide : **valide** (3 → 4) ou **complément** (3 → 6) ou **refus** (3 → 5).  
- **CRMA** après validation : fait le **règlement** : partiel (→ 7) puis total (→ 8) puis **clôture** (→ 14).  
- **Encaissements** : utilisés surtout pour **recours / franchise / épave** (selon le dossier et la responsabilité du tiers).

---

## 8) Réserve complémentaire (important à expliquer)
**Définition** : une **réserve complémentaire** est une réserve ajoutée après coup quand le montant réglé dépasse la réserve initiale (ex: devis réévalué, indemnité finale plus élevée).  

**Dans ton application**, ce mécanisme peut être automatique lors de l’ajout d’un règlement :
- si **total règlements > total réserve actif**, le système ajoute une ligne de réserve **type `complementaire`** pour la différence ;
- ensuite, le dossier peut passer en **7 (partiel)** ou **8 (définitif)** selon la comparaison totale.

**Conseil démo** :
- Pour rester simple : fais **Réserve = 120000** puis **Règlement = 120000**.
- Si tu veux impressionner le jury (bonus 30s) : fais un règlement **125000** → expliquer que le système crée **réserve complémentaire = 5000**.
