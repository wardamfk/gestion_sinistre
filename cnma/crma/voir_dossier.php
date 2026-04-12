<?php
include('../includes/config.php');
session_start();

if(!isset($_GET['id'])){ echo "Dossier introuvable"; exit(); }
$id_dossier = $_GET['id'];
$id_user = $_SESSION['id_user'];

$dossier = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT d.*, e.nom_etat,
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

$expert_dossier = mysqli_fetch_assoc(mysqli_query($conn,"SELECT e.nom,e.prenom,e.id_expert FROM dossier d LEFT JOIN expert e ON d.id_expert=e.id_expert WHERE d.id_dossier=$id_dossier"));

$total_reserve = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(montant),0) as t FROM reserve WHERE id_dossier=$id_dossier"))['t'];
$total_regle   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(montant),0) as t FROM reglement WHERE id_dossier=$id_dossier"))['t'];
$total_enc     = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(montant),0) as t FROM encaissement WHERE id_dossier=$id_dossier"))['t'];
$reste = $total_reserve - $total_regle;
$cout_reel = $total_regle - $total_enc;
$taux_recours = $total_regle > 0 ? round($total_enc / $total_regle * 100, 1) : 0;

$encaissement_autorise = in_array($dossier['responsable'], ['oui', 'partiel']);
$etat = $dossier['id_etat'];
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
/* ===== OVERRIDES COMPACTS ===== */

/* Hero ultra-compact */
.dossier-hero-v2 {
 background: linear-gradient(90deg, #368a5dff, #85d29cff);
  border-radius: var(--radius-lg);
  padding: 14px 22px;
  margin-bottom: 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}
.dossier-hero-v2 .dh-left {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}
.dossier-hero-v2 .dh-num {
  font-size: 18px;
  font-weight: 700;
  color: #fff;
  font-family: 'DM Mono', monospace;
  letter-spacing: -.2px;
}
.dossier-hero-v2 .dh-meta {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
  align-items: center;
}
.dossier-hero-v2 .dh-meta span {
  font-size: 12px;
  color: rgba(255,255,255,.65);
  display: flex;
  align-items: center;
  gap: 5px;
}
.dossier-hero-v2 .dh-right {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 12px;
  color: rgba(255,255,255,.6);
}

/* Badge état inline */
.badge-hero {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 11px;
  border-radius: 999px;
  font-size: 11.5px;
  font-weight: 600;
  background: rgba(255,255,255,.18);
  color: #fff;
  border: 1px solid rgba(255,255,255,.2);
  white-space: nowrap;
}

/* ===== KPI BAR — UNE SEULE LIGNE ===== */
.kpi-bar {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
  margin-bottom: 16px;
}
.kpi-item {
  background: #fff;
  border: 1px solid var(--gray-200);
  border-radius: var(--radius);
  padding: 12px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  transition: box-shadow .15s;
}
.kpi-item:hover { box-shadow: var(--shadow); }
.kpi-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
  flex-shrink: 0;
}
.kpi-body { flex: 1; min-width: 0; }
.kpi-label {
  font-size: 10px;
  font-weight: 600;
  color: var(--gray-500);
  text-transform: uppercase;
  letter-spacing: .5px;
  margin-bottom: 3px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.kpi-value {
  font-size: 20px;
  font-weight: 700;
  font-family: 'DM Mono', monospace;
  line-height: 1;
  white-space: nowrap;
}
.kpi-value small {
  font-size: 11px;
  font-family: 'DM Sans', sans-serif;
  font-weight: 400;
  color: var(--gray-400);
  margin-left: 2px;
}

/* Variantes couleur KPI */
.kpi-reserve .kpi-icon { background: var(--blue-50);   color: var(--blue-700);  }
.kpi-reserve .kpi-value { color: var(--blue-800); }
.kpi-regle   .kpi-icon { background: var(--green-100); color: var(--green-700); }
.kpi-regle   .kpi-value { color: var(--green-800); }
.kpi-reste   .kpi-icon { background: var(--red-50);    color: var(--red-600);   }
.kpi-reste   .kpi-value { color: var(--red-600); }
.kpi-reste.ok .kpi-icon { background: var(--green-100); color: var(--green-700); }
.kpi-reste.ok .kpi-value { color: var(--green-700); }
.kpi-enc     .kpi-icon { background: var(--teal-50);   color: var(--teal-700);  }
.kpi-enc     .kpi-value { color: var(--teal-700); }

/* ===== ONGLETS PLUS COMPACTS ===== */
.crma-tabs {
  margin-bottom: 16px;
  gap: 0;
  border-bottom: 2px solid var(--gray-200);
}
.crma-tab-btn {
  padding: 9px 16px;
  font-size: 12.5px;
}

/* ===== ACTION BAR COMPACTE ===== */
.action-bar-v2 {
  display: flex;
  gap: 8px;
  align-items: center;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

/* ===== TAB ENCAISSEMENTS ===== */

/* Statut compact — badge pill au lieu d'un grand block */
.enc-status-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 13px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  margin-bottom: 14px;
}
.enc-status-pill.ok  { background: var(--green-100); color: var(--green-800); border: 1px solid var(--green-200); }
.enc-status-pill.nok { background: var(--red-50);    color: var(--red-700);   border: 1px solid var(--red-100);   }

/* KPIs encaissements — 4 colonnes compactes */
.enc-kpi-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
  margin-bottom: 18px;
}
.enc-kpi {
  background: #fff;
  border: 1px solid var(--gray-200);
  border-radius: var(--radius);
  padding: 13px 15px;
  text-align: center;
}
.enc-kpi .ek-label {
  font-size: 10px;
  font-weight: 600;
  color: var(--gray-500);
  text-transform: uppercase;
  letter-spacing: .5px;
  margin-bottom: 6px;
}
.enc-kpi .ek-val {
  font-size: 21px;
  font-weight: 700;
  font-family: 'DM Mono', monospace;
  color: var(--gray-800);
}
.enc-kpi .ek-val small {
  font-size: 11px;
  font-family: 'DM Sans', sans-serif;
  color: var(--gray-400);
  margin-left: 2px;
}
.enc-kpi.c-blue  { border-top: 3px solid var(--blue-600);  }
.enc-kpi.c-blue  .ek-val { color: var(--blue-800); }
.enc-kpi.c-green { border-top: 3px solid var(--green-600); }
.enc-kpi.c-green .ek-val { color: var(--green-800); }
.enc-kpi.c-amber { border-top: 3px solid var(--amber-600); }
.enc-kpi.c-amber .ek-val { color: var(--amber-600); }
.enc-kpi.c-teal  { border-top: 3px solid var(--teal-600);  }
.enc-kpi.c-teal  .ek-val { color: var(--teal-700); }

/* Empty state encaissements interdit */
.enc-forbidden {
  background: var(--gray-50);
  border: 1px dashed var(--gray-300);
  border-radius: var(--radius-lg);
  padding: 40px 20px;
  text-align: center;
  color: var(--gray-500);
}
.enc-forbidden .ef-icon {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--red-50);
  color: var(--red-600);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  margin: 0 auto 14px;
}
.enc-forbidden h4 {
  font-size: 15px;
  font-weight: 600;
  color: var(--gray-800);
  margin-bottom: 6px;
}
.enc-forbidden p {
  font-size: 13px;
  color: var(--gray-500);
  max-width: 380px;
  margin: 0 auto;
  line-height: 1.6;
}

/* Formulaire encaissement compact */
.enc-form {
  background: #fff;
  border: 1px solid var(--gray-200);
  border-radius: var(--radius-lg);
  padding: 18px 20px;
  margin-bottom: 18px;
}
.enc-form-title {
  font-size: 12.5px;
  font-weight: 600;
  color: var(--gray-700);
  text-transform: uppercase;
  letter-spacing: .5px;
  margin-bottom: 14px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--gray-100);
  display: flex;
  align-items: center;
  gap: 7px;
}
.enc-form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 1fr auto;
  gap: 12px;
  align-items: flex-end;
}
.enc-form-grid .fg {
  display: flex;
  flex-direction: column;
  gap: 5px;
}
.enc-form-grid label {
  font-size: 10.5px;
  font-weight: 600;
  color: var(--gray-500);
  text-transform: uppercase;
  letter-spacing: .4px;
}
.enc-form-grid input,
.enc-form-grid select {
  padding: 8px 10px;
  border: 1px solid var(--gray-300);
  border-radius: var(--radius);
  font-size: 13px;
  font-family: 'DM Sans', sans-serif;
  color: var(--gray-800);
  background: #fff;
}
.enc-form-grid input:focus,
.enc-form-grid select:focus {
  border-color: var(--green-600);
  outline: none;
  box-shadow: 0 0 0 2px rgba(22,163,74,.12);
}
.enc-form-comment {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 12px;
  align-items: flex-end;
  margin-top: 10px;
}

/* Info card dans les tabs */
.info-row-crma {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 8px 0;
  border-bottom: 1px solid var(--gray-100);
  font-size: 13.5px;
}
.info-row-crma:last-child { border-bottom: none; }
.info-row-crma span:first-child { color: var(--gray-500); font-weight: 400; }
.info-row-crma span:last-child  { font-weight: 500; color: var(--gray-800); text-align: right; max-width: 60%; }

/* Conteneur tab moins de padding vertical */
#encaissements.crma-tab-content { padding-top: 4px; }
</style>
</head>
<body>
<?php include('../includes/header.php'); ?>
<?php include('../includes/sidebar.php'); ?>

<div class="main" style="padding-top:16px;">

<!-- ===== HERO COMPACT ===== -->
<div class="dossier-hero-v2">
  <div class="dh-left">
    <div class="dh-num"><?= $dossier['numero_dossier']; ?></div>
    <?php
    $etat_colors = [2=>'blue',3=>'purple',4=>'green',5=>'red',6=>'orange',7=>'teal',8=>'green',9=>'orange',14=>'gray'];
    $ec = $etat_colors[$etat] ?? 'gray';
    ?>
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

<!-- ===== KPI BAR — 4 métriques sur 1 ligne ===== -->
<div class="kpi-bar">
  <div class="kpi-item kpi-reserve">
    <div class="kpi-icon"><i class="fa fa-shield-halved"></i></div>
    <div class="kpi-body">
      <div class="kpi-label">Réserves</div>
      <div class="kpi-value"><?= number_format($total_reserve, 0, ',', ' '); ?><small>DA</small></div>
    </div>
  </div>
  <div class="kpi-item kpi-regle">
    <div class="kpi-icon"><i class="fa fa-money-bill-wave"></i></div>
    <div class="kpi-body">
      <div class="kpi-label">Réglé</div>
      <div class="kpi-value"><?= number_format($total_regle, 0, ',', ' '); ?><small>DA</small></div>
    </div>
  </div>
  <div class="kpi-item kpi-reste<?= $reste <= 0 ? ' ok' : ''; ?>">
    <div class="kpi-icon"><i class="fa fa-scale-balanced"></i></div>
    <div class="kpi-body">
      <div class="kpi-label">Reste</div>
      <div class="kpi-value"><?= number_format($reste, 0, ',', ' '); ?><small>DA</small></div>
    </div>
  </div>
  <div class="kpi-item kpi-enc">
    <div class="kpi-icon"><i class="fa fa-arrow-trend-down"></i></div>
    <div class="kpi-body">
      <div class="kpi-label">Encaissements</div>
      <div class="kpi-value"><?= number_format($total_enc, 0, ',', ' '); ?><small>DA</small></div>
    </div>
  </div>
</div>

<!-- ===== ACTIONS ===== -->
<div class="action-bar-v2">
  <?php if($etat == 8 && $_SESSION['role'] == 'CRMA'): ?>
  <a href="cloturer_dossier.php?id=<?= $id_dossier; ?>" class="btn btn-primary btn-sm" onclick="return confirm('Clôturer ce dossier ?')">
    <i class="fa fa-archive"></i> Clôturer
  </a>
  <?php endif; ?>
  <?php if($etat == 14): ?>
  <span class="badge badge-green" style="padding:7px 14px;font-size:12px;"><i class="fa fa-check-circle"></i> Dossier clôturé</span>
  <?php endif; ?>
  <a href="mes_dossiers.php" class="btn btn-outline btn-sm" style="margin-left:auto;"><i class="fa fa-arrow-left"></i> Retour</a>
</div>

<!-- ===== ONGLETS ===== -->
<div class="crma-tabs">
  <button class="crma-tab-btn active" onclick="showTab('info',this)"><i class="fa fa-info-circle"></i> Informations</button>
  <button class="crma-tab-btn" onclick="showTab('documents',this)"><i class="fa fa-file-alt"></i> Documents</button>
  <button class="crma-tab-btn" onclick="showTab('expertise',this)"><i class="fa fa-search"></i> Expertise</button>
  <button class="crma-tab-btn" onclick="showTab('reserves',this)"><i class="fa fa-shield-halved"></i> Réserves</button>
  <button class="crma-tab-btn" onclick="showTab('reglements',this)"><i class="fa fa-money-bill"></i> Règlements</button>
  <button class="crma-tab-btn" onclick="showTab('encaissements',this)"><i class="fa fa-arrow-down"></i> Encaissements</button>
  <button class="crma-tab-btn" onclick="showTab('historique',this)"><i class="fa fa-history"></i> Historique</button>
</div>

<!-- ===== TAB: INFORMATIONS ===== -->
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
        $sv_map = ['non_soumis'=>['badge-gray','Non soumis'],'en_attente'=>['badge-amber','En attente'],'valide'=>['badge-green','Validé'],'refuse'=>['badge-red','Refusé']];
        $sv = $sv_map[$dossier['statut_validation']] ?? ['badge-gray',$dossier['statut_validation']];
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
        $r = $dossier['responsable'];
        $rc = ['oui'=>'badge-red','non'=>'badge-green','partiel'=>'badge-amber'];
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

<!-- ===== TAB: DOCUMENTS ===== -->
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

<!-- ===== TAB: EXPERTISE ===== -->
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

<!-- ===== TAB: RÉSERVES ===== -->
<div id="reserves" class="crma-tab-content">
<?php if(in_array($etat,[1,2,3,7])): ?>
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
<div class="msg msg-warning"><i class="fa fa-exclamation-triangle"></i> Ajout de réserve impossible dans l'état actuel du dossier.</div>
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

<!-- ===== TAB: RÈGLEMENTS ===== -->
<div id="reglements" class="crma-tab-content">
<?php if($etat==3): ?>
<div class="msg msg-info"><i class="fa fa-info-circle"></i> Règlement impossible — dossier transmis à la CNMA pour validation.</div>
<?php elseif($etat==5): ?>
<div class="msg msg-error"><i class="fa fa-times-circle"></i> Règlement impossible — dossier refusé par la CNMA.</div>
<?php elseif($etat==8): ?>
<div class="msg msg-success"><i class="fa fa-check-circle"></i> Dossier intégralement réglé.</div>
<?php else: ?>
<div class="crma-card">
  <h4><i class="fa fa-plus"></i> Ajouter règlement</h4>
  <form action="ajouter_reglement.php" method="POST" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:flex-end;">
    <input type="hidden" name="id_dossier" value="<?= $id_dossier; ?>">
    <div class="form-group" style="margin:0"><label>Montant (DA)</label><input type="number" step="0.01" name="montant" required></div>
    <div class="form-group" style="margin:0"><label>Mode</label><select name="mode"><option>Chèque</option></select></div>
    <div class="form-group" style="margin:0"><label>Commentaire</label><input type="text" name="commentaire"></div>
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Ajouter</button>
  </form>
</div>
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

<!-- ===== TAB: ENCAISSEMENTS — REDESIGNÉ ===== -->
<div id="encaissements" class="crma-tab-content">

  <!-- 1. Statut en badge compact (une seule fois) -->
  <?php if($encaissement_autorise): ?>
  <span class="enc-status-pill ok"><i class="fa fa-circle-check"></i> Encaissement autorisé</span>
  <?php else: ?>
  <span class="enc-status-pill nok"><i class="fa fa-circle-xmark"></i> Encaissement non autorisé — tiers non responsable</span>
  <?php endif; ?>

  <!-- 2. KPIs — 4 métriques sur une ligne -->
  <div class="enc-kpi-row">
    <div class="enc-kpi c-blue">
      <div class="ek-label">Total règlements</div>
      <div class="ek-val"><?= number_format($total_regle, 0, ',', ' '); ?><small>DA</small></div>
    </div>
    <div class="enc-kpi c-green">
      <div class="ek-label">Total encaissements</div>
      <div class="ek-val"><?= number_format($total_enc, 0, ',', ' '); ?><small>DA</small></div>
    </div>
    <div class="enc-kpi c-amber">
      <div class="ek-label">Coût réel sinistre</div>
      <div class="ek-val"><?= number_format($cout_reel, 0, ',', ' '); ?><small>DA</small></div>
    </div>
    <div class="enc-kpi c-teal">
      <div class="ek-label">Taux de recours</div>
      <div class="ek-val"><?= $taux_recours; ?><small>%</small></div>
    </div>
  </div>

  <!-- 3. Formulaire OU empty state (jamais les deux) -->
  <?php if($encaissement_autorise && in_array($etat,[7,8,13,14])): ?>
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
          <select name="type">
            <option value="recours">Recours</option>
            <option value="franchise">Franchise</option>
            <option value="epave">Épave</option>
            <option value="autre">Autre</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary" style="white-space:nowrap;"><i class="fa fa-save"></i> Enregistrer</button>
      </div>
      <div class="enc-form-comment" style="margin-top:10px;">
        <div class="fg" style="display:flex;flex-direction:column;gap:5px;">
          <label style="font-size:10.5px;font-weight:600;color:var(--gray-500);text-transform:uppercase;letter-spacing:.4px;">Commentaire (optionnel)</label>
          <input type="text" name="commentaire" placeholder="Précisions sur cet encaissement…" style="padding:8px 10px;border:1px solid var(--gray-300);border-radius:var(--radius);font-size:13px;">
        </div>
      </div>
    </form>
  </div>
  <?php else: ?>
  <!-- Empty state selon la raison -->
  <div class="enc-forbidden">
    <div class="ef-icon">
      <?php if(!$encaissement_autorise): ?>
      <i class="fa fa-ban"></i>
      <?php else: ?>
      <i class="fa fa-lock"></i>
      <?php endif; ?>
    </div>
    <?php if(!$encaissement_autorise): ?>
    <h4>Aucun recours possible</h4>
    <p>Le tiers est déclaré non responsable dans ce dossier. L'enregistrement d'encaissements de recours n'est donc pas applicable.</p>
    <?php else: ?>
    <h4>Encaissement non disponible</h4>
    <p>Le dossier doit être dans un état de règlement (partiel ou total) pour enregistrer un encaissement. État actuel : <strong><?= $dossier['nom_etat']; ?></strong></p>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- 4. Liste des encaissements -->
  <div class="crma-table-wrapper" style="margin-top:18px;">
    <div class="table-toolbar" style="padding:12px 16px;">
      <span style="font-size:12px;font-weight:600;color:var(--gray-600);display:flex;align-items:center;gap:7px;"><i class="fa fa-list" style="color:var(--green-700);"></i> Liste des encaissements</span>
    </div>
    <table class="crma-table">
      <thead><tr><th>Date</th><th>Tiers</th><th>Type</th><th>Montant</th><th>Commentaire</th></tr></thead>
      <tbody>
      <?php
      $encs=mysqli_query($conn,"SELECT enc.*,p.nom,p.prenom,t.compagnie_assurance FROM encaissement enc JOIN tiers t ON enc.id_tiers=t.id_tiers JOIN personne p ON t.id_personne=p.id_personne WHERE enc.id_dossier=$id_dossier ORDER BY enc.id_encaissement DESC");
      if(mysqli_num_rows($encs)==0) echo "<tr><td colspan='5'><div class='empty-state'><i class='fa fa-inbox'></i><p>Aucun encaissement enregistré</p></div></td></tr>";
      $type_badges=['recours'=>'badge-blue','franchise'=>'badge-amber','epave'=>'badge-gray','autre'=>'badge-teal'];
      while($enc=mysqli_fetch_assoc($encs)){
        $tb=$type_badges[$enc['type']]??'badge-gray';
        echo "<tr><td style='font-size:12px;'>{$enc['date_encaissement']}</td><td><div style='font-weight:500;'>{$enc['nom']} {$enc['prenom']}</div><div style='font-size:11px;color:var(--gray-400);'>{$enc['compagnie_assurance']}</div></td><td><span class='badge $tb' style='font-size:11px;'>{$enc['type']}</span></td><td class='num-cell' style='font-weight:700;color:var(--green-800);'>".number_format($enc['montant'],2,',',' ')." DA</td><td style='font-size:12px;color:var(--gray-500);'>{$enc['commentaire']}</td></tr>";
      }
      ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ===== TAB: HISTORIQUE ===== -->
<div id="historique" class="crma-tab-content">
<div class="crma-table-wrapper">
<table class="crma-table">
  <thead><tr><th>Date / Heure</th><th>Action</th><th>Ancien état</th><th>Nouvel état</th></tr></thead>
  <tbody>
  <?php
  $hist=mysqli_query($conn,"SELECT h.*,ea.nom_etat AS ancien,en.nom_etat AS nouveau FROM historique h LEFT JOIN etat_dossier ea ON h.ancien_etat=ea.id_etat LEFT JOIN etat_dossier en ON h.nouvel_etat=en.id_etat WHERE h.id_dossier=$id_dossier ORDER BY h.date_action DESC");
  while($h=mysqli_fetch_assoc($hist)){
    $a=strtolower($h['action']);
    if(str_contains($a,'valid')) $as="background:var(--green-100);color:var(--green-800);";
    elseif(str_contains($a,'refus')) $as="background:var(--red-50);color:var(--red-700);";
    elseif(str_contains($a,'règlement')||str_contains($a,'reglement')) $as="background:var(--teal-50);color:var(--teal-700);";
    elseif(str_contains($a,'réserve')||str_contains($a,'reserve')) $as="background:var(--blue-50);color:var(--blue-800);";
    elseif(str_contains($a,'créat')||str_contains($a,'creat')) $as="background:#e8eaf6;color:#283593;";
    elseif(str_contains($a,'encaissement')) $as="background:#f3e5f5;color:#4a148c;";
    else $as="background:var(--gray-100);color:var(--gray-600);";
    echo "<tr><td style='font-size:12px;'><b>".date('d/m/Y',strtotime($h['date_action']))."</b><br><small style='color:var(--gray-400);'>".date('H:i:s',strtotime($h['date_action']))."</small></td><td><span style='$as padding:3px 10px;border-radius:12px;font-size:11.5px;font-weight:600;'>{$h['action']}</span></td><td style='font-size:12px;color:var(--gray-500);'>".($h['ancien']??'—')."</td><td style='font-size:12px;color:var(--gray-500);'>".($h['nouveau']??'—')."</td></tr>";
  }
  ?>
  </tbody>
</table>
</div>
</div>

</div><!-- fin .main -->

<script>
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
</script>
</body>
</html>