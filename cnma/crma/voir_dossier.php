<?php
include('../includes/config.php');
session_start();

if(!isset($_GET['id'])){ echo "Dossier introuvable"; exit(); }
$id_dossier = $_GET['id'];
$id_user = $_SESSION['id_user'];
$role    = $_SESSION['role'];

$dossier = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT d.*, e.nom_etat, e.motif_obligatoire,
           p.nom AS nom_assure, p.prenom AS prenom_assure, p.telephone,
           pt.nom AS nom_tiers, pt.prenom AS prenom_tiers,
           t.compagnie_assurance, t.responsable,
           c.numero_police, f.nom_formule,
           v.marque, v.modele, v.matricule,
           ex.nom AS nom_expert, ex.prenom AS prenom_expert,
           ag.nom_agence, ag.wilaya
    FROM dossier d
    LEFT JOIN etat_dossier e ON d.id_etat=e.id_etat
    LEFT JOIN contrat c ON d.id_contrat=c.id_contrat
    LEFT JOIN formule f ON c.id_formule=f.id_formule
    LEFT JOIN assure ass ON c.id_assure=ass.id_assure
    LEFT JOIN personne p ON ass.id_personne=p.id_personne
    LEFT JOIN tiers t ON d.id_tiers=t.id_tiers
    LEFT JOIN personne pt ON t.id_personne=pt.id_personne
    LEFT JOIN vehicule v ON c.id_vehicule=v.id_vehicule
    LEFT JOIN expert ex ON d.id_expert=ex.id_expert
    LEFT JOIN agence ag ON ag.id_agence=(SELECT id_agence FROM utilisateur WHERE id_user=d.cree_par LIMIT 1)
    WHERE d.id_dossier=$id_dossier
"));

if(!$dossier){ die("Dossier introuvable"); }

$expert_dossier = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT e.nom,e.prenom,e.id_expert FROM dossier d LEFT JOIN expert e ON d.id_expert=e.id_expert WHERE d.id_dossier=$id_dossier"));

$total_reserve = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(montant),0) as t FROM reserve WHERE id_dossier=$id_dossier"))['t'];
$total_regle   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(montant),0) as t FROM reglement WHERE id_dossier=$id_dossier"))['t'];
$total_enc     = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(montant),0) as t FROM encaissement WHERE id_dossier=$id_dossier"))['t'];
$reste = $total_reserve - $total_regle;
$cout_reel = $total_regle - $total_enc;
$taux_recours = $total_regle > 0 ? round($total_enc / $total_regle * 100, 1) : 0;

$encaissement_autorise = in_array($dossier['responsable'], ['oui', 'partiel']);
$etat = $dossier['id_etat'];

// ── Transitions autorisées selon état et rôle ─────────────────────────────
// Format: [ nouvel_etat => ['label'=>..., 'icon'=>..., 'class'=>..., 'confirm'=>bool] ]
$transitions = [];

if ($role === 'CRMA') {
    switch ($etat) {
        case 2: // En cours CRMA
            $transitions[16] = ['label'=>'Demander contre-expertise', 'icon'=>'fa-rotate',      'class'=>'btn-outline',  'confirm'=>true];
            $transitions[13] = ['label'=>'Mettre en attente recours',  'icon'=>'fa-pause-circle','class'=>'btn-warning',  'confirm'=>true];
            $transitions[20] = ['label'=>'Passer en gestion recours',  'icon'=>'fa-gavel',       'class'=>'btn-info',     'confirm'=>false]; // motif obligatoire
            $transitions[11] = ['label'=>'Classer sans suite',         'icon'=>'fa-ban',         'class'=>'btn-danger',   'confirm'=>false]; // motif obligatoire
            break;
        case 4: // Validé CNMA
            $transitions[17] = ['label'=>'Marquer judiciaire',         'icon'=>'fa-gavel',       'class'=>'btn-outline',  'confirm'=>true];
            break;
        case 5: // Refusé CNMA
            $transitions[12] = ['label'=>'Classer après rejet',        'icon'=>'fa-folder-minus','class'=>'btn-danger',   'confirm'=>true];
            break;
        case 8: // Règlement définitif amiable
            $transitions[14] = ['label'=>'Clôturer',                   'icon'=>'fa-archive',     'class'=>'btn-primary',  'confirm'=>true];
            break;
        case 17: // Règlement judiciaire
            $transitions[14] = ['label'=>'Clôturer',                   'icon'=>'fa-archive',     'class'=>'btn-primary',  'confirm'=>true];
            break;
        case 11: // Classé sans suite
        case 12: // Classé après rejet
        case 14: // Clôturé
            $transitions[15] = ['label'=>'Reprendre dossier',          'icon'=>'fa-folder-open', 'class'=>'btn-primary',  'confirm'=>false]; // motif obligatoire
            break;
        case 13: // En attente recours
            $transitions[15] = ['label'=>'Reprendre dossier',          'icon'=>'fa-folder-open', 'class'=>'btn-primary',  'confirm'=>false]; // motif obligatoire
            $transitions[18] = ['label'=>'Reprise après recours abouti','icon'=>'fa-check-double','class'=>'btn-success',  'confirm'=>true];
            break;
        case 18: // Repris pour recours abouti
            $transitions[19] = ['label'=>'Classé recours abouti',      'icon'=>'fa-check-circle','class'=>'btn-success',  'confirm'=>false]; // motif encaissement
            break;
        case 16: // Contre-expertise
            $transitions[2]  = ['label'=>'Retour en cours CRMA',       'icon'=>'fa-undo',        'class'=>'btn-outline',  'confirm'=>true];
            $transitions[11] = ['label'=>'Classer sans suite',         'icon'=>'fa-ban',         'class'=>'btn-danger',   'confirm'=>false];
            break;
    }
}

if ($role === 'CNMA') {
    if ($etat == 8) {
        $transitions[14] = ['label'=>'Clôturer',                       'icon'=>'fa-archive',     'class'=>'btn-primary',  'confirm'=>true];
    }
}
// ─────────────────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Dossier <?= $dossier['numero_dossier']; ?> — CRMA</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/style_crma.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ── Hero compact ── */
.dossier-hero-v2{background:linear-gradient(90deg,#368a5dff,#85d29cff);border-radius:var(--radius-lg);padding:14px 22px;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
.dossier-hero-v2 .dh-left{display:flex;align-items:center;gap:16px;flex-wrap:wrap}
.dossier-hero-v2 .dh-num{font-size:18px;font-weight:700;color:#fff;font-family:'DM Mono',monospace;letter-spacing:-.2px}
.dossier-hero-v2 .dh-meta{display:flex;gap:14px;flex-wrap:wrap;align-items:center}
.dossier-hero-v2 .dh-meta span{font-size:12px;color:rgba(255,255,255,.65);display:flex;align-items:center;gap:5px}
.dossier-hero-v2 .dh-right{display:flex;align-items:center;gap:10px;font-size:12px;color:rgba(255,255,255,.6)}
.badge-hero{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:999px;font-size:11.5px;font-weight:600;background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.2);white-space:nowrap}

/* ── KPI bar ── */
.kpi-bar{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px}
.kpi-item{background:#fff;border:1px solid var(--gray-200);border-radius:var(--radius);padding:12px 16px;display:flex;align-items:center;gap:12px;transition:box-shadow .15s}
.kpi-item:hover{box-shadow:var(--shadow)}
.kpi-icon{width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0}
.kpi-body{flex:1;min-width:0}
.kpi-label{font-size:10px;font-weight:600;color:var(--gray-500);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.kpi-value{font-size:20px;font-weight:700;font-family:'DM Mono',monospace;line-height:1;white-space:nowrap}
.kpi-value small{font-size:11px;font-family:'DM Sans',sans-serif;font-weight:400;color:var(--gray-400);margin-left:2px}
.kpi-reserve .kpi-icon{background:var(--blue-50);color:var(--blue-700)}
.kpi-reserve .kpi-value{color:var(--blue-800)}
.kpi-regle .kpi-icon{background:var(--green-100);color:var(--green-700)}
.kpi-regle .kpi-value{color:var(--green-800)}
.kpi-reste .kpi-icon{background:var(--red-50);color:var(--red-600)}
.kpi-reste .kpi-value{color:var(--red-600)}
.kpi-reste.ok .kpi-icon{background:var(--green-100);color:var(--green-700)}
.kpi-reste.ok .kpi-value{color:var(--green-700)}
.kpi-enc .kpi-icon{background:var(--teal-50);color:var(--teal-700)}
.kpi-enc .kpi-value{color:var(--teal-700)}

/* ── Action bar compact ── */
.action-bar-v2{display:flex;gap:8px;align-items:center;margin-bottom:16px;flex-wrap:wrap}

/* ── Workflow transitions section ── */
.transitions-bar{background:#fff;border:1px solid var(--gray-200);border-radius:var(--radius-lg);padding:14px 18px;margin-bottom:16px}
.transitions-bar .tb-title{font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px;display:flex;align-items:center;gap:6px}
.transitions-bar .tb-title i{color:var(--green-700)}
.transitions-bar .tb-buttons{display:flex;gap:8px;flex-wrap:wrap}

/* ── Motif modal ── */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:900;align-items:center;justify-content:center;backdrop-filter:blur(2px)}
.modal-overlay.open{display:flex}
.modal-box{background:#fff;border-radius:18px;padding:32px 36px;width:580px;max-width:96vw;max-height:90vh;overflow-y:auto;box-shadow:0 24px 70px rgba(0,0,0,.22);animation:modalIn .18s ease}
@keyframes modalIn{from{transform:translateY(10px);opacity:0}to{transform:translateY(0);opacity:1}}
.modal-box h3{font-size:16px;font-weight:700;margin-bottom:20px;padding-bottom:14px;border-bottom:2px solid var(--gray-100);display:flex;align-items:center;gap:10px;color:var(--gray-800)}
.modal-box .form-group{margin-bottom:18px}
.modal-box .form-group label{display:block;font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.5px;margin-bottom:7px}
.modal-box .form-group select,
.modal-box .form-group textarea{width:100%;padding:11px 14px;border:1.5px solid var(--gray-200);border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--gray-800);background:var(--gray-50);transition:border-color .18s}
.modal-box .form-group select:focus,
.modal-box .form-group textarea:focus{border-color:var(--green-600);outline:none;background:#fff;box-shadow:0 0 0 3px rgba(22,163,74,.12)}
.motif-obligatoire-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:600;background:var(--red-50);color:var(--red-700);border:1px solid var(--red-100);margin-bottom:8px}
.modal-btn-row{display:flex;gap:10px;margin-top:22px}
.modal-btn-row .btn{flex:1;justify-content:center;padding:12px;font-size:14px}

/* ── Tabs ── */
.crma-tabs{margin-bottom:16px;gap:0;border-bottom:2px solid var(--gray-200)}
.crma-tab-btn{padding:9px 16px;font-size:12.5px}

/* ── Info rows compact ── */
.info-row-crma{display:flex;justify-content:space-between;align-items:flex-start;padding:8px 0;border-bottom:1px solid #f0f4f8;font-size:13.5px}
.info-row-crma:last-child{border-bottom:none}
.info-row-crma span:first-child{color:var(--gray-500);font-weight:400}
.info-row-crma span:last-child{font-weight:500;color:var(--gray-800);text-align:right;max-width:60%}

/* ── Messages ── */
.msg-ok{background:var(--green-100);color:var(--green-800);border-left:3px solid var(--green-600);padding:12px 16px;border-radius:var(--radius);margin-bottom:14px;font-size:13.5px;display:flex;align-items:center;gap:8px}
.msg-err{background:var(--red-50);color:var(--red-700);border-left:3px solid var(--red-600);padding:12px 16px;border-radius:var(--radius);margin-bottom:14px;font-size:13.5px;display:flex;align-items:center;gap:8px}

/* Encaissements */
.enc-status-pill{display:inline-flex;align-items:center;gap:6px;padding:5px 13px;border-radius:999px;font-size:12px;font-weight:600;margin-bottom:14px}
.enc-status-pill.ok{background:var(--green-100);color:var(--green-800);border:1px solid var(--green-200)}
.enc-status-pill.nok{background:var(--red-50);color:var(--red-700);border:1px solid var(--red-100)}
.enc-kpi-row{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:18px}
.enc-kpi{background:#fff;border:1px solid var(--gray-200);border-radius:var(--radius);padding:13px 15px;text-align:center}
.enc-kpi .ek-label{font-size:10px;font-weight:600;color:var(--gray-500);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px}
.enc-kpi .ek-val{font-size:21px;font-weight:700;font-family:'DM Mono',monospace;color:var(--gray-800)}
.enc-kpi .ek-val small{font-size:11px;font-family:'DM Sans',sans-serif;color:var(--gray-400);margin-left:2px}
.enc-kpi.c-blue{border-top:3px solid var(--blue-600)}.enc-kpi.c-blue .ek-val{color:var(--blue-800)}
.enc-kpi.c-green{border-top:3px solid var(--green-600)}.enc-kpi.c-green .ek-val{color:var(--green-800)}
.enc-kpi.c-amber{border-top:3px solid var(--amber-600)}.enc-kpi.c-amber .ek-val{color:var(--amber-600)}
.enc-kpi.c-teal{border-top:3px solid var(--teal-600)}.enc-kpi.c-teal .ek-val{color:var(--teal-700)}
.enc-forbidden{background:var(--gray-50);border:1px dashed var(--gray-300);border-radius:var(--radius-lg);padding:40px 20px;text-align:center;color:var(--gray-500)}
.enc-forbidden .ef-icon{width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 14px}
.enc-form{background:#fff;border:1px solid var(--gray-200);border-radius:var(--radius-lg);padding:18px 20px;margin-bottom:18px}
.enc-form-title{font-size:12.5px;font-weight:600;color:var(--gray-700);text-transform:uppercase;letter-spacing:.5px;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--gray-100);display:flex;align-items:center;gap:7px}
.enc-form-grid{display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:12px;align-items:flex-end}
.enc-form-grid .fg{display:flex;flex-direction:column;gap:5px}
.enc-form-grid label{font-size:10.5px;font-weight:600;color:var(--gray-500);text-transform:uppercase;letter-spacing:.4px}
.enc-form-grid input,.enc-form-grid select{padding:8px 10px;border:1px solid var(--gray-300);border-radius:var(--radius);font-size:13px;font-family:'DM Sans',sans-serif;color:var(--gray-800);background:#fff}
.enc-form-grid input:focus,.enc-form-grid select:focus{border-color:var(--green-600);outline:none;box-shadow:0 0 0 2px rgba(22,163,74,.12)}
</style>
</head>
<body>
<?php include('../includes/header.php'); ?>
<?php include('../includes/sidebar.php'); ?>

<div class="main" style="padding-top:16px;">

<!-- ─── HERO ─────────────────────────────────────────────────────── -->
<div class="dossier-hero-v2">
  <div class="dh-left">
    <div class="dh-num"><?= $dossier['numero_dossier']; ?></div>
    <span class="badge-hero"><i class="fa fa-circle" style="font-size:7px;opacity:.7;"></i> <?= $dossier['nom_etat']; ?></span>
    <div class="dh-meta">
      <span><i class="fa fa-calendar"></i> <?= $dossier['date_sinistre']; ?></span>
      <span><i class="fa fa-map-marker-alt"></i> <?= $dossier['lieu_sinistre']; ?></span>
      <span><i class="fa fa-user-tie"></i> <?= ($expert_dossier && $expert_dossier['nom']) ? $expert_dossier['nom'].' '.$expert_dossier['prenom'] : 'Aucun expert'; ?></span>
    </div>
  </div>
  <div class="dh-right">
    <span><i class="fa fa-building" style="margin-right:4px;"></i><?= $dossier['nom_agence']; ?> · <?= $dossier['wilaya']; ?></span>
  </div>
</div>

<!-- ─── KPI BAR ───────────────────────────────────────────────────── -->
<div class="kpi-bar">
  <div class="kpi-item kpi-reserve">
    <div class="kpi-icon"><i class="fa fa-shield-halved"></i></div>
    <div class="kpi-body">
      <div class="kpi-label">Réserves</div>
      <div class="kpi-value"><?= number_format($total_reserve,0,',',' '); ?><small>DA</small></div>
    </div>
  </div>
  <div class="kpi-item kpi-regle">
    <div class="kpi-icon"><i class="fa fa-money-bill-wave"></i></div>
    <div class="kpi-body">
      <div class="kpi-label">Réglé</div>
      <div class="kpi-value"><?= number_format($total_regle,0,',',' '); ?><small>DA</small></div>
    </div>
  </div>
  <div class="kpi-item kpi-reste<?= $reste<=0?' ok':''; ?>">
    <div class="kpi-icon"><i class="fa fa-scale-balanced"></i></div>
    <div class="kpi-body">
      <div class="kpi-label">Reste</div>
      <div class="kpi-value"><?= number_format($reste,0,',',' '); ?><small>DA</small></div>
    </div>
  </div>
  <div class="kpi-item kpi-enc">
    <div class="kpi-icon"><i class="fa fa-arrow-trend-down"></i></div>
    <div class="kpi-body">
      <div class="kpi-label">Encaissements</div>
      <div class="kpi-value"><?= number_format($total_enc,0,',',' '); ?><small>DA</small></div>
    </div>
  </div>
</div>

<!-- ─── MESSAGES ─────────────────────────────────────────────────── -->
<?php if(isset($_GET['ok']) && $_GET['ok']==='etat_change'): ?>
<div class="msg-ok"><i class="fa fa-check-circle"></i> État du dossier mis à jour avec succès.</div>
<?php endif; ?>
<?php if(isset($_GET['err']) && $_GET['err']==='motif_required'): ?>
<div class="msg-err"><i class="fa fa-exclamation-triangle"></i> Un motif est obligatoire pour ce changement d'état.</div>
<?php endif; ?>

<!-- ─── BOUTONS ACTIONS DE BASE ───────────────────────────────────── -->
<div class="action-bar-v2">
  <a href="mes_dossiers.php" class="btn btn-outline btn-sm" style="margin-left:auto;"><i class="fa fa-arrow-left"></i> Retour</a>
</div>

<!-- ─── TRANSITIONS D'ÉTAT (WORKFLOW) ────────────────────────────── -->
<?php if (!empty($transitions)): ?>
<div class="transitions-bar">
  <div class="tb-title"><i class="fa fa-route"></i> Actions workflow — passer à un nouvel état</div>
  <div class="tb-buttons">
    <?php foreach ($transitions as $nouvel_etat_id => $t): ?>
    <button class="btn <?= $t['class']; ?> btn-sm"
            onclick="openTransition(<?= $nouvel_etat_id ?>, '<?= htmlspecialchars($t['label']) ?>')">
      <i class="fa <?= $t['icon']; ?>"></i> <?= $t['label']; ?>
    </button>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- ─── MODAL CHANGEMENT D'ÉTAT + MOTIF ──────────────────────────── -->
<div class="modal-overlay" id="modal-transition">
  <div class="modal-box">
    <h3 id="modal-transition-title"><i class="fa fa-route" style="color:var(--green-700)"></i> Changement d'état</h3>

    <form method="POST" action="changer_etat_dossier.php" id="form-transition">
      <input type="hidden" name="id_dossier" value="<?= $id_dossier; ?>">
      <input type="hidden" name="nouvel_etat" id="input-nouvel-etat" value="">

      <!-- Badge état cible -->
      <div style="background:var(--gray-50);border:1px solid var(--gray-200);border-radius:var(--radius);padding:12px 16px;margin-bottom:18px;font-size:13px;">
        <span style="color:var(--gray-500);">État actuel :</span>
        <strong style="margin-left:6px;"><?= $dossier['nom_etat']; ?></strong>
        <span style="margin:0 10px;color:var(--gray-400);">→</span>
        <strong id="label-nouvel-etat" style="color:var(--green-700);"></strong>
      </div>

      <!-- Motif -->
      <div class="form-group" id="groupe-motif" style="display:none;">
        <label id="label-motif">Motif <span id="motif-required-star" style="color:red;display:none;">*</span></label>
        <div id="motif-obligatoire-badge" class="motif-obligatoire-badge" style="display:none;">
          <i class="fa fa-exclamation-triangle"></i> Motif obligatoire pour cet état
        </div>
        <select name="id_motif" id="select-motif">
          <option value="">— Choisir un motif —</option>
        </select>
      </div>

      <!-- Commentaire libre -->
      <div class="form-group">
        <label>Commentaire <span style="color:var(--gray-400);font-weight:400;">(optionnel)</span></label>
        <textarea name="commentaire" rows="3" placeholder="Précisions sur ce changement d'état…"></textarea>
      </div>

      <div class="modal-btn-row">
        <button type="submit" class="btn btn-primary" id="btn-confirmer-transition">
          <i class="fa fa-check"></i> Confirmer le changement
        </button>
        <button type="button" class="btn btn-outline" onclick="closeTransition()">Annuler</button>
      </div>
    </form>
  </div>
</div>

<!-- ─── ONGLETS ───────────────────────────────────────────────────── -->
<div class="crma-tabs">
  <button class="crma-tab-btn active" onclick="showTab('info',this)"><i class="fa fa-info-circle"></i> Informations</button>
  <button class="crma-tab-btn" onclick="showTab('documents',this)"><i class="fa fa-file-alt"></i> Documents</button>
  <button class="crma-tab-btn" onclick="showTab('expertise',this)"><i class="fa fa-search"></i> Expertise</button>
  <button class="crma-tab-btn" onclick="showTab('reserves',this)"><i class="fa fa-shield-halved"></i> Réserves</button>
  <button class="crma-tab-btn" onclick="showTab('reglements',this)"><i class="fa fa-money-bill"></i> Règlements</button>
  <button class="crma-tab-btn" onclick="showTab('encaissements',this)"><i class="fa fa-arrow-down"></i> Encaissements</button>
  <button class="crma-tab-btn" onclick="showTab('historique',this)"><i class="fa fa-history"></i> Historique</button>
</div>

<!-- ─── TAB: INFORMATIONS ─────────────────────────────────────────── -->
<div id="info" class="crma-tab-content" style="display:block;">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
  <div class="crma-card">
    <h4><i class="fa fa-car-burst"></i> Sinistre</h4>
    <div class="info-row-crma"><span>Date sinistre</span><span><?= $dossier['date_sinistre']; ?></span></div>
    <div class="info-row-crma"><span>Lieu</span><span><?= $dossier['lieu_sinistre']; ?></span></div>
    <div class="info-row-crma"><span>Délai déclaration</span><span><?= $dossier['delai_declaration']; ?> jours</span></div>
    <div class="info-row-crma"><span>Description</span><span><?= $dossier['description']; ?></span></div>
    <div class="info-row-crma"><span>Statut validation</span>
      <span><?php
        $sv_map=['non_soumis'=>['badge-gray','Non soumis'],'en_attente'=>['badge-amber','En attente'],'valide'=>['badge-green','Validé'],'refuse'=>['badge-red','Refusé']];
        $sv=$sv_map[$dossier['statut_validation']]??['badge-gray',$dossier['statut_validation']];
        echo "<span class='badge {$sv[0]}'>{$sv[1]}</span>";
      ?></span>
    </div>
  </div>
  <div class="crma-card">
    <h4><i class="fa fa-user"></i> Assuré & Contrat</h4>
    <div class="info-row-crma"><span>Assuré</span><span><?= $dossier['nom_assure'].' '.$dossier['prenom_assure']; ?></span></div>
    <div class="info-row-crma"><span>Téléphone</span><span><?= $dossier['telephone']; ?></span></div>
    <div class="info-row-crma"><span>N° Police</span><span><?= $dossier['numero_police']; ?></span></div>
    <div class="info-row-crma"><span>Formule</span><span><?= $dossier['nom_formule']; ?></span></div>
    <div class="info-row-crma"><span>Véhicule</span><span><?= $dossier['marque'].' '.$dossier['modele'].' — '.$dossier['matricule']; ?></span></div>
  </div>
  <div class="crma-card">
    <h4><i class="fa fa-users"></i> Tiers</h4>
    <div class="info-row-crma"><span>Nom</span><span><?= $dossier['nom_tiers'].' '.$dossier['prenom_tiers']; ?></span></div>
    <div class="info-row-crma"><span>Compagnie</span><span><?= $dossier['compagnie_assurance']; ?></span></div>
    <div class="info-row-crma"><span>Responsabilité</span>
      <span><?php
        $r=$dossier['responsable'];$rc=['oui'=>'badge-red','non'=>'badge-green','partiel'=>'badge-amber'];
        echo "<span class='badge ".($rc[$r]??'badge-gray')."'>".ucfirst($r)."</span>";
      ?></span>
    </div>
  </div>
  <div class="crma-card">
    <h4><i class="fa fa-info-circle"></i> État financier</h4>
    <div class="info-row-crma"><span>Réserve</span><span style="font-family:monospace;font-weight:700;color:var(--blue-800);"><?= number_format($total_reserve,2,',',' '); ?> DA</span></div>
    <div class="info-row-crma"><span>Réglé</span><span style="font-family:monospace;font-weight:700;color:var(--green-800);"><?= number_format($total_regle,2,',',' '); ?> DA</span></div>
    <div class="info-row-crma"><span>Reste</span><span style="font-family:monospace;font-weight:700;color:<?= $reste>0?'var(--red-600)':'var(--green-700)'; ?>;"><?= number_format($reste,2,',',' '); ?> DA</span></div>
    <div class="info-row-crma"><span>Encaissements</span><span style="font-family:monospace;font-weight:700;color:var(--teal-700);"><?= number_format($total_enc,2,',',' '); ?> DA</span></div>
    <div class="info-row-crma"><span>Coût réel</span><span style="font-family:monospace;font-weight:700;color:var(--amber-600);"><?= number_format($cout_reel,2,',',' '); ?> DA</span></div>
  </div>
</div>
</div>

<!-- ─── TAB: DOCUMENTS ───────────────────────────────────────────── -->
<div id="documents" class="crma-tab-content">
<div class="crma-card">
  <h4><i class="fa fa-upload"></i> Ajouter document</h4>
  <form action="upload_document.php" method="POST" enctype="multipart/form-data" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
    <input type="hidden" name="id_dossier" value="<?= $id_dossier; ?>">
    <div style="flex:1;min-width:160px;">
      <label style="font-size:10px;font-weight:600;color:var(--gray-500);text-transform:uppercase;display:block;margin-bottom:5px;">Type</label>
      <select name="type" style="width:100%;padding:9px;border:1px solid var(--gray-300);border-radius:var(--radius);font-size:13px;">
        <?php $types=mysqli_query($conn,"SELECT * FROM type_document"); while($t=mysqli_fetch_assoc($types)) echo "<option value='{$t['id_type_document']}'>{$t['nom_type']}</option>"; ?>
      </select>
    </div>
    <div style="flex:2;min-width:200px;">
      <label style="font-size:10px;font-weight:600;color:var(--gray-500);text-transform:uppercase;display:block;margin-bottom:5px;">Fichier</label>
      <input type="file" name="fichier" required style="width:100%;padding:8px;border:1px solid var(--gray-300);border-radius:var(--radius);">
    </div>
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-upload"></i> Uploader</button>
  </form>
</div>
<div class="crma-table-wrapper">
<table class="crma-table">
  <thead><tr><th>Type</th><th>Fichier</th><th>Date</th><th>Action</th></tr></thead>
  <tbody>
  <?php
  $docs=mysqli_query($conn,"SELECT d.*,t.nom_type FROM document d LEFT JOIN type_document t ON d.id_type_document=t.id_type_document WHERE d.id_dossier=$id_dossier");
  if(mysqli_num_rows($docs)==0) echo "<tr><td colspan='4'><div class='empty-state'><i class='fa fa-file-alt'></i><p>Aucun document</p></div></td></tr>";
  while($d=mysqli_fetch_assoc($docs)){
    echo "<tr><td>{$d['nom_type']}</td><td><a href='../uploads/{$d['nom_fichier']}' target='_blank' class='btn btn-primary btn-xs'><i class='fa fa-eye'></i> Voir</a></td><td style='font-size:12px;'>{$d['date_upload']}</td><td><a href='supprimer_documents.php?id={$d['id_document']}&dossier=$id_dossier' onclick=\"return confirm('Supprimer ?')\" class='btn btn-danger btn-xs'><i class='fa fa-trash'></i></a></td></tr>";
  }
  ?>
  </tbody>
</table>
</div>
</div>

<!-- ─── TAB: EXPERTISE ───────────────────────────────────────────── -->
<div id="expertise" class="crma-tab-content">
<div class="crma-card">
  <h4><i class="fa fa-plus"></i> Ajouter expertise</h4>
  <form action="ajouter_expertise.php" method="POST" enctype="multipart/form-data" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
    <input type="hidden" name="id_dossier" value="<?= $id_dossier; ?>">
    <div class="form-group" style="margin:0"><label>Expert</label>
      <select name="id_expert" required>
        <?php $experts=mysqli_query($conn,"SELECT * FROM expert"); while($e=mysqli_fetch_assoc($experts)){ $sel=($e['id_expert']==$expert_dossier['id_expert'])?"selected":""; echo "<option value='{$e['id_expert']}' $sel>{$e['nom']} {$e['prenom']}</option>"; } ?>
      </select>
    </div>
    <div class="form-group" style="margin:0"><label>Date expertise</label><input type="date" name="date_expertise" required></div>
    <div class="form-group" style="margin:0"><label>Montant indemnité (DA)</label><input type="number" name="montant_indemnite" required></div>
    <div class="form-group" style="margin:0"><label>Rapport PDF</label><input type="file" name="rapport" required></div>
    <div class="form-group" style="margin:0"><label>Commentaire</label><input type="text" name="commentaire"></div>
    <div style="display:flex;align-items:flex-end;"><button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa fa-plus"></i> Ajouter</button></div>
  </form>
</div>
<div class="crma-table-wrapper">
<table class="crma-table">
  <thead><tr><th>Date</th><th>Expert</th><th>Montant</th><th>Rapport</th><th>Commentaire</th><th>Actions</th></tr></thead>
  <tbody>
  <?php
  $expertises=mysqli_query($conn,"SELECT ex.*,e.nom,e.prenom FROM expertise ex LEFT JOIN expert e ON ex.id_expert=e.id_expert WHERE ex.id_dossier=$id_dossier ORDER BY ex.id_expertise DESC");
  if(mysqli_num_rows($expertises)==0) echo "<tr><td colspan='6'><div class='empty-state'><i class='fa fa-search'></i><p>Aucune expertise</p></div></td></tr>";
  while($ex=mysqli_fetch_assoc($expertises)){
    echo "<tr><td style='font-size:12px;'>{$ex['date_expertise']}</td><td>{$ex['nom']} {$ex['prenom']}</td><td class='num-cell' style='font-weight:600;'>".number_format($ex['montant_indemnite'],2,',',' ')." DA</td><td>".($ex['rapport_pdf']?"<a href='../uploads/{$ex['rapport_pdf']}' target='_blank' class='btn btn-primary btn-xs'><i class='fa fa-file-pdf'></i> Voir</a>":'—')."</td><td style='font-size:12px;'>{$ex['commentaire']}</td><td style='display:flex;gap:4px;'><a href='modifier_expertise.php?id={$ex['id_expertise']}' class='btn btn-outline btn-xs'><i class='fa fa-pen'></i></a><a href='supprimer_expertise.php?id={$ex['id_expertise']}&dossier=$id_dossier' onclick=\"return confirm('Supprimer ?')\" class='btn btn-danger btn-xs'><i class='fa fa-trash'></i></a></td></tr>";
  }
  ?>
  </tbody>
</table>
</div>
</div>

<!-- ─── TAB: RÉSERVES ────────────────────────────────────────────── -->
<div id="reserves" class="crma-tab-content">
<?php if(in_array($etat,[1,2,3,7,9,15,16])): ?>
<div class="crma-card">
  <h4><i class="fa fa-plus"></i> Ajouter réserve</h4>
  <form action="ajouter_reserve.php" method="POST" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:flex-end;">
    <input type="hidden" name="id_dossier" value="<?= $id_dossier; ?>">
    <div class="form-group" style="margin:0"><label>Montant (DA)</label><input type="number" step="0.01" name="montant" required></div>
    <div class="form-group" style="margin:0"><label>Garantie</label><select name="id_garantie"><?php $gar=mysqli_query($conn,"SELECT * FROM garantie"); while($g=mysqli_fetch_assoc($gar)) echo "<option value='{$g['id_garantie']}'>{$g['nom_garantie']}</option>"; ?></select></div>
    <div class="form-group" style="margin:0"><label>Commentaire</label><input type="text" name="commentaire"></div>
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Ajouter</button>
  </form>
</div>
<?php else: ?>
<div class="msg msg-warning" style="margin-bottom:16px;"><i class="fa fa-exclamation-triangle"></i> Ajout de réserve non disponible dans l'état actuel.</div>
<?php endif; ?>
<div class="crma-table-wrapper">
<table class="crma-table">
  <thead><tr><th>Date</th><th>Garantie</th><th>Montant</th><th>Type</th><th>Commentaire</th><th>Actions</th></tr></thead>
  <tbody>
  <?php
  $reserves=mysqli_query($conn,"SELECT r.*,g.nom_garantie FROM reserve r LEFT JOIN garantie g ON r.id_garantie=g.id_garantie WHERE r.id_dossier=$id_dossier ORDER BY r.id_reserve DESC");
  if(mysqli_num_rows($reserves)==0) echo "<tr><td colspan='6'><div class='empty-state'><i class='fa fa-shield-halved'></i><p>Aucune réserve</p></div></td></tr>";
  while($r=mysqli_fetch_assoc($reserves)){
    $type_badges=['initiale'=>'badge-blue','expertise'=>'badge-teal','ajustement'=>'badge-amber'];
    $tb=$type_badges[$r['type_reserve']]??'badge-gray';
    echo "<tr><td style='font-size:12px;'>{$r['date_reserve']}</td><td>{$r['nom_garantie']}</td><td class='num-cell' style='font-weight:600;'>".number_format($r['montant'],2,',',' ')." DA</td><td><span class='badge $tb' style='font-size:11px;'>{$r['type_reserve']}</span></td><td style='font-size:12px;'>{$r['commentaire']}</td><td style='display:flex;gap:4px;'><a href='modifier_reserve.php?id={$r['id_reserve']}' class='btn btn-outline btn-xs'><i class='fa fa-pen'></i></a><a href='supprimer_reserve.php?id={$r['id_reserve']}&dossier=$id_dossier' onclick=\"return confirm('Supprimer ?')\" class='btn btn-danger btn-xs'><i class='fa fa-trash'></i></a></td></tr>";
  }
  ?>
  </tbody>
</table>
</div>
</div>

<!-- ─── TAB: RÈGLEMENTS ──────────────────────────────────────────── -->
<div id="reglements" class="crma-tab-content">
<?php if($etat==3): ?>
<div class="msg msg-info" style="margin-bottom:16px;"><i class="fa fa-info-circle"></i> Règlement impossible — dossier transmis à la CNMA pour validation.</div>
<?php elseif($etat==5): ?>
<div class="msg msg-error" style="margin-bottom:16px;"><i class="fa fa-times-circle"></i> Règlement impossible — dossier refusé par la CNMA.</div>
<?php elseif($etat==8): ?>
<div class="msg msg-success" style="margin-bottom:16px;"><i class="fa fa-check-circle"></i> Dossier intégralement réglé.</div>
<?php elseif(in_array($etat,[4,7,15])): ?>
<div class="crma-card">
  <h4><i class="fa fa-plus"></i> Ajouter règlement</h4>
  <form action="ajouter_reglement.php" method="POST" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:flex-end;">
    <input type="hidden" name="id_dossier" value="<?= $id_dossier; ?>">
    <div class="form-group" style="margin:0"><label>Montant (DA)</label><input type="number" step="0.01" name="montant" required></div>
    <div class="form-group" style="margin:0"><label>Mode</label><select name="mode"><option>Chèque</option><option>Virement</option></select></div>
    <div class="form-group" style="margin:0"><label>Commentaire</label><input type="text" name="commentaire"></div>
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Ajouter</button>
  </form>
</div>
<?php else: ?>
<div class="msg msg-warning" style="margin-bottom:16px;"><i class="fa fa-exclamation-triangle"></i> Règlement non disponible — état : <?= $dossier['nom_etat']; ?>.</div>
<?php endif; ?>
<div class="crma-table-wrapper">
<table class="crma-table">
  <thead><tr><th>Date</th><th>Montant</th><th>Mode</th><th>Statut</th><th>Commentaire</th><th>Actions</th></tr></thead>
  <tbody>
  <?php
  $reglements=mysqli_query($conn,"SELECT * FROM reglement WHERE id_dossier=$id_dossier ORDER BY id_reglement DESC");
  if(mysqli_num_rows($reglements)==0) echo "<tr><td colspan='6'><div class='empty-state'><i class='fa fa-money-bill'></i><p>Aucun règlement</p></div></td></tr>";
  while($reg=mysqli_fetch_assoc($reglements)){
    $statuts=['en_attente'=>['badge-amber','En attente'],'disponible'=>['badge-green','Disponible'],'remis'=>['badge-teal','Remis']];
    $s=$statuts[$reg['statut']]??['badge-gray',$reg['statut']];
    $actions="<a href='modifier_reglement.php?id={$reg['id_reglement']}' class='btn btn-outline btn-xs'><i class='fa fa-pen'></i></a>";
    if($reg['statut']=='en_attente') $actions.="<a href='gerer_reglement_statut.php?id={$reg['id_reglement']}&dossier=$id_dossier&statut=disponible' class='btn btn-primary btn-xs' onclick=\"return confirm('Marquer disponible ?')\"><i class='fa fa-check'></i></a>";
    if($reg['statut']=='disponible') $actions.="<a href='gerer_reglement_statut.php?id={$reg['id_reglement']}&dossier=$id_dossier&statut=remis' class='btn btn-outline btn-xs' onclick=\"return confirm('Marquer remis ?')\"><i class='fa fa-handshake'></i></a>";
    $actions.="<a href='supprimer_reglement.php?id={$reg['id_reglement']}&dossier=$id_dossier' onclick=\"return confirm('Supprimer ?')\" class='btn btn-danger btn-xs'><i class='fa fa-trash'></i></a>";
    echo "<tr><td style='font-size:12px;'>{$reg['date_reglement']}</td><td class='num-cell' style='font-weight:600;'>".number_format($reg['montant'],2,',',' ')." DA</td><td>{$reg['mode_paiement']}</td><td><span class='badge {$s[0]}' style='font-size:11px;'>{$s[1]}</span></td><td style='font-size:12px;'>{$reg['commentaire']}</td><td style='display:flex;gap:4px;flex-wrap:wrap;'>$actions</td></tr>";
  }
  ?>
  </tbody>
</table>
</div>
</div>

<!-- ─── TAB: ENCAISSEMENTS ───────────────────────────────────────── -->
<div id="encaissements" class="crma-tab-content">
  <?php if($encaissement_autorise): ?>
  <span class="enc-status-pill ok"><i class="fa fa-circle-check"></i> Encaissement autorisé</span>
  <?php else: ?>
  <span class="enc-status-pill nok"><i class="fa fa-circle-xmark"></i> Encaissement non autorisé — tiers non responsable</span>
  <?php endif; ?>

  <div class="enc-kpi-row">
    <div class="enc-kpi c-blue"><div class="ek-label">Total règlements</div><div class="ek-val"><?= number_format($total_regle,0,',',' '); ?><small>DA</small></div></div>
    <div class="enc-kpi c-green"><div class="ek-label">Total encaissements</div><div class="ek-val"><?= number_format($total_enc,0,',',' '); ?><small>DA</small></div></div>
    <div class="enc-kpi c-amber"><div class="ek-label">Coût réel sinistre</div><div class="ek-val"><?= number_format($cout_reel,0,',',' '); ?><small>DA</small></div></div>
    <div class="enc-kpi c-teal"><div class="ek-label">Taux de recours</div><div class="ek-val"><?= $taux_recours; ?><small>%</small></div></div>
  </div>

  <?php if($encaissement_autorise && in_array($etat,[7,8,13,14,19])): ?>
  <div class="enc-form">
    <div class="enc-form-title"><i class="fa fa-plus" style="color:var(--green-700);"></i> Enregistrer un encaissement</div>
    <form action="ajouter_encaissement.php" method="POST">
      <input type="hidden" name="id_dossier" value="<?= $id_dossier; ?>">
      <div class="enc-form-grid">
        <div class="fg"><label>Montant (DA) *</label><input type="number" step="0.01" name="montant" required placeholder="0,00"></div>
        <div class="fg"><label>Date *</label><input type="date" name="date_encaissement" value="<?= date('Y-m-d'); ?>" required></div>
        <div class="fg"><label>Tiers *</label>
          <select name="id_tiers" required>
            <?php $trs=mysqli_query($conn,"SELECT t.id_tiers,p.nom,p.prenom,t.compagnie_assurance FROM tiers t JOIN personne p ON t.id_personne=p.id_personne");
            while($tr=mysqli_fetch_assoc($trs)) echo "<option value='{$tr['id_tiers']}'>{$tr['nom']} {$tr['prenom']} — {$tr['compagnie_assurance']}</option>"; ?>
          </select>
        </div>
        <div class="fg"><label>Type</label>
          <select name="type"><option value="recours">Recours</option><option value="franchise">Franchise</option><option value="epave">Épave</option><option value="autre">Autre</option></select>
        </div>
        <button type="submit" class="btn btn-primary" style="white-space:nowrap;"><i class="fa fa-save"></i> Enregistrer</button>
      </div>
      <div style="margin-top:10px;">
        <input type="text" name="commentaire" placeholder="Commentaire optionnel…" style="width:100%;padding:8px 10px;border:1px solid var(--gray-300);border-radius:var(--radius);font-size:13px;">
      </div>
    </form>
  </div>
  <?php else: ?>
  <div class="enc-forbidden">
    <div class="ef-icon" style="background:<?= !$encaissement_autorise?'var(--red-50)':'var(--gray-100)'; ?>;color:<?= !$encaissement_autorise?'var(--red-600)':'var(--gray-500)'; ?>;">
      <i class="fa <?= !$encaissement_autorise?'fa-ban':'fa-lock'; ?>"></i>
    </div>
    <?php if(!$encaissement_autorise): ?>
    <h4>Aucun recours possible</h4>
    <p>Le tiers est déclaré non responsable. L'encaissement de recours n'est pas applicable.</p>
    <?php else: ?>
    <h4>Encaissement non disponible</h4>
    <p>Le dossier doit être en règlement pour enregistrer un encaissement. État actuel : <strong><?= $dossier['nom_etat']; ?></strong></p>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="crma-table-wrapper" style="margin-top:18px;">
    <div class="table-toolbar" style="padding:12px 16px;"><span style="font-size:12px;font-weight:600;color:var(--gray-600);"><i class="fa fa-list" style="color:var(--green-700);margin-right:6px;"></i>Liste des encaissements</span></div>
    <table class="crma-table">
      <thead><tr><th>Date</th><th>Tiers</th><th>Type</th><th>Montant</th><th>Commentaire</th></tr></thead>
      <tbody>
      <?php
      $encs=mysqli_query($conn,"SELECT enc.*,p.nom,p.prenom,t.compagnie_assurance FROM encaissement enc JOIN tiers t ON enc.id_tiers=t.id_tiers JOIN personne p ON t.id_personne=p.id_personne WHERE enc.id_dossier=$id_dossier ORDER BY enc.id_encaissement DESC");
      if(mysqli_num_rows($encs)==0) echo "<tr><td colspan='5'><div class='empty-state'><i class='fa fa-inbox'></i><p>Aucun encaissement</p></div></td></tr>";
      $type_badges_enc=['recours'=>'badge-blue','franchise'=>'badge-amber','epave'=>'badge-gray','autre'=>'badge-teal'];
      while($enc=mysqli_fetch_assoc($encs)){
        $tb=$type_badges_enc[$enc['type']]??'badge-gray';
        echo "<tr><td style='font-size:12px;'>{$enc['date_encaissement']}</td><td><div style='font-weight:500;'>{$enc['nom']} {$enc['prenom']}</div><div style='font-size:11px;color:var(--gray-400);'>{$enc['compagnie_assurance']}</div></td><td><span class='badge $tb' style='font-size:11px;'>{$enc['type']}</span></td><td class='num-cell' style='font-weight:700;color:var(--green-800);'>".number_format($enc['montant'],2,',',' ')." DA</td><td style='font-size:12px;color:var(--gray-500);'>{$enc['commentaire']}</td></tr>";
      }
      ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ─── TAB: HISTORIQUE ───────────────────────────────────────────── -->
<div id="historique" class="crma-tab-content">
<div class="crma-table-wrapper">
<table class="crma-table">
  <thead><tr><th>Date / Heure</th><th>Action</th><th>Ancien état</th><th>Nouvel état</th><th>Motif</th><th>Commentaire</th></tr></thead>
  <tbody>
  <?php
  $hist=mysqli_query($conn,"
    SELECT h.*,
           ea.nom_etat AS ancien, en.nom_etat AS nouveau,
           m.nom_motif
    FROM historique h
    LEFT JOIN etat_dossier ea ON h.ancien_etat=ea.id_etat
    LEFT JOIN etat_dossier en ON h.nouvel_etat=en.id_etat
    LEFT JOIN motif m ON h.id_motif=m.id_motif
    WHERE h.id_dossier=$id_dossier
    ORDER BY h.date_action DESC");
  while($h=mysqli_fetch_assoc($hist)){
    $a=strtolower($h['action']);
    if(str_contains($a,'valid'))      $as="background:var(--green-100);color:var(--green-800);";
    elseif(str_contains($a,'refus'))  $as="background:var(--red-50);color:var(--red-700);";
    elseif(str_contains($a,'clôture')||str_contains($a,'classé')||str_contains($a,'cloture')) $as="background:#f3e5f5;color:#4a148c;";
    elseif(str_contains($a,'règlement')||str_contains($a,'reglement')) $as="background:var(--teal-50);color:var(--teal-700);";
    elseif(str_contains($a,'réserve')||str_contains($a,'reserve')) $as="background:var(--blue-50);color:var(--blue-800);";
    elseif(str_contains($a,'créat')||str_contains($a,'creat')) $as="background:#e8eaf6;color:#283593;";
    elseif(str_contains($a,'repris')) $as="background:#fff3e0;color:#e65100;";
    elseif(str_contains($a,'encaissement')) $as="background:#f3e5f5;color:#4a148c;";
    else $as="background:var(--gray-100);color:var(--gray-600);";

    $motif_cell = $h['nom_motif']
        ? "<span style='background:var(--amber-50);color:var(--amber-600);border:1px solid var(--amber-100);border-radius:6px;padding:2px 8px;font-size:11px;font-weight:600;'>".$h['nom_motif']."</span>"
        : '<span style="color:var(--gray-300);">—</span>';

    echo "<tr>
      <td style='font-size:12px;'><b>".date('d/m/Y',strtotime($h['date_action']))."</b><br><small style='color:var(--gray-400);'>".date('H:i:s',strtotime($h['date_action']))."</small></td>
      <td><span style='$as padding:3px 10px;border-radius:12px;font-size:11.5px;font-weight:600;'>{$h['action']}</span></td>
      <td style='font-size:12px;color:var(--gray-500);'>".($h['ancien']??'—')."</td>
      <td style='font-size:12px;color:var(--gray-500);'>".($h['nouveau']??'—')."</td>
      <td>$motif_cell</td>
      <td style='font-size:12px;color:var(--gray-500);max-width:180px;'>".htmlspecialchars($h['commentaire']??'')."</td>
    </tr>";
  }
  ?>
  </tbody>
</table>
</div>
</div>

</div><!-- fin .main -->

<!-- ─── JAVASCRIPT ────────────────────────────────────────────────── -->
<script>
// Onglets
function showTab(tab, btn) {
  document.querySelectorAll('.crma-tab-content').forEach(t => t.style.display='none');
  document.querySelectorAll('.crma-tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById(tab).style.display = 'block';
  if(btn) btn.classList.add('active');
}
const params = new URLSearchParams(window.location.search);
const tab = params.get('tab');
if(tab) {
  const btn = document.querySelector(`.crma-tab-btn[onclick*="${tab}"]`);
  showTab(tab, btn);
}

// ─── Modal Transition d'état ─────────────────────────────────────
let motifObligatoire = false;

async function openTransition(nEtat, label) {
  document.getElementById('input-nouvel-etat').value = nEtat;
  document.getElementById('label-nouvel-etat').textContent = label;
  document.getElementById('modal-transition-title').innerHTML =
    '<i class="fa fa-route" style="color:var(--green-700)"></i> ' + label;

  // Charger les motifs via AJAX
  const groupeMotif = document.getElementById('groupe-motif');
  const selectMotif = document.getElementById('select-motif');
  const badgeObligatoire = document.getElementById('motif-obligatoire-badge');
  const starRequired = document.getElementById('motif-required-star');

  try {
    const resp = await fetch(`/PfeCnma/cnma/crma/get_motifs.php?id_etat=${nEtat}`)
    const data = await resp.json();

    selectMotif.innerHTML = '<option value="">— Choisir un motif —</option>';

    if (data.motifs && data.motifs.length > 0) {
      data.motifs.forEach(m => {
        const opt = document.createElement('option');
        opt.value = m.id_motif;
        opt.textContent = m.nom_motif;
        selectMotif.appendChild(opt);
      });
      groupeMotif.style.display = 'block';

      motifObligatoire = data.obligatoire;
      if (data.obligatoire) {
        badgeObligatoire.style.display = 'inline-flex';
        starRequired.style.display = 'inline';
        selectMotif.required = true;
      } else {
        badgeObligatoire.style.display = 'none';
        starRequired.style.display = 'none';
        selectMotif.required = false;
      }
    } else {
      groupeMotif.style.display = 'none';
      selectMotif.required = false;
      motifObligatoire = false;
    }
  } catch (e) {
    groupeMotif.style.display = 'none';
  }

  document.getElementById('modal-transition').classList.add('open');
}

function closeTransition() {
  document.getElementById('modal-transition').classList.remove('open');
}

// Fermer modal en cliquant dehors
document.getElementById('modal-transition').addEventListener('click', function(e) {
  if(e.target === this) closeTransition();
});

// Validation avant submit
document.getElementById('form-transition').addEventListener('submit', function(e) {
  const sel = document.getElementById('select-motif');
  if (motifObligatoire && (!sel.value || sel.value === '')) {
    e.preventDefault();
    sel.style.border = '2px solid var(--red-600)';
    sel.focus();
    alert('⚠ Ce changement d\'état nécessite un motif. Veuillez en sélectionner un.');
    return false;
  }
  
});
</script>
</body>
</html>