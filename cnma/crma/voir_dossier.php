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
</head>
<body>
<?php include('../includes/header.php'); ?>
<?php include('../includes/sidebar.php'); ?>

<div class="main">

<!-- EN-TÊTE DOSSIER -->
<div class="dossier-hero-crma">
    <div>
        <div style="font-size:13px;color:rgba(255,255,255,0.7);margin-bottom:6px;">
            <i class="fa fa-folder"></i> Dossier sinistre
        </div>
        <h2 style="color:white;font-size:24px;font-weight:700;margin:0 0 10px;">
            <?= $dossier['numero_dossier']; ?>
        </h2>
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <?php
            $etat_colors = [
                1=>'gray',2=>'blue',3=>'purple',4=>'green',5=>'red',
                6=>'orange',7=>'teal',8=>'green',9=>'orange',14=>'gray'
            ];
            $ec = $etat_colors[$etat] ?? 'gray';
            ?>
            <span class="badge-crma <?= $ec; ?>"><?= $dossier['nom_etat']; ?></span>
            <span style="color:rgba(255,255,255,0.7);font-size:13px;">
                <i class="fa fa-calendar"></i> <?= $dossier['date_sinistre']; ?>
            </span>
            <span style="color:rgba(255,255,255,0.7);font-size:13px;">
                <i class="fa fa-map-marker-alt"></i> <?= $dossier['lieu_sinistre']; ?>
            </span>
        </div>
    </div>
    <div style="text-align:right;">
        <div style="color:rgba(255,255,255,0.7);font-size:12px;margin-bottom:4px;">Agence</div>
        <div style="color:white;font-weight:600;"><?= $dossier['nom_agence']; ?> — <?= $dossier['wilaya']; ?></div>
        <div style="color:rgba(255,255,255,0.7);font-size:12px;margin-top:8px;">Expert</div>
        <div style="color:white;font-weight:600;"><?= ($expert_dossier && $expert_dossier['nom']) ? $expert_dossier['nom'].' '.$expert_dossier['prenom'] : 'Non affecté'; ?></div>
    </div>
</div>

<!-- BILAN FINANCIER -->
<div class="finance-bar-crma">
    <div class="finance-item-crma reserve">
        <div class="fi-icon"><i class="fa fa-shield-halved"></i></div>
        <div>
            <div class="fi-label">Total Réserves</div>
            <div class="fi-value"><?= number_format($total_reserve,2,',',' '); ?> <span>DA</span></div>
        </div>
    </div>
    <div class="finance-item-crma regle">
        <div class="fi-icon"><i class="fa fa-money-bill-wave"></i></div>
        <div>
            <div class="fi-label">Total Réglé</div>
            <div class="fi-value"><?= number_format($total_regle,2,',',' '); ?> <span>DA</span></div>
        </div>
    </div>
    <div class="finance-item-crma reste">
        <div class="fi-icon"><i class="fa fa-scale-balanced"></i></div>
        <div>
            <div class="fi-label">Reste à régler</div>
            <div class="fi-value" style="color:<?= $reste>0?'#c62828':'#2e7d32'; ?>"><?= number_format($reste,2,',',' '); ?> <span>DA</span></div>
        </div>
    </div>
    <div class="finance-item-crma enc">
        <div class="fi-icon"><i class="fa fa-arrow-down"></i></div>
        <div>
            <div class="fi-label">Encaissements</div>
            <div class="fi-value" style="color:#2e7d32;"><?= number_format($total_enc,2,',',' '); ?> <span>DA</span></div>
        </div>
    </div>
</div>

<!-- ACTIONS RAPIDES -->
<div class="action-bar-crma">
    <?php if($etat == 8 && $_SESSION['role'] == 'CRMA'): ?>
    <a href="cloturer_dossier.php?id=<?= $id_dossier; ?>" class="crma-btn success" onclick="return confirm('Clôturer ce dossier ?')">
        <i class="fa fa-archive"></i> Clôturer dossier
    </a>
    <?php endif; ?>
    <?php if($etat == 14): ?>
    <span class="crma-btn" style="background:#e8f5e9;color:#1b5e20;cursor:default;">
        <i class="fa fa-check-circle"></i> Dossier clôturé
    </span>
    <?php endif; ?>
    <a href="mes_dossiers.php" class="crma-btn secondary"><i class="fa fa-arrow-left"></i> Retour</a>
</div>

<!-- ONGLETS -->
<div class="crma-tabs">
    <button class="crma-tab-btn active" onclick="showTab('info',this)"><i class="fa fa-info-circle"></i> Informations</button>
    <button class="crma-tab-btn" onclick="showTab('documents',this)"><i class="fa fa-file-alt"></i> Documents</button>
    <button class="crma-tab-btn" onclick="showTab('expertise',this)"><i class="fa fa-search"></i> Expertise</button>
    <button class="crma-tab-btn" onclick="showTab('reserves',this)"><i class="fa fa-shield-halved"></i> Réserves</button>
    <button class="crma-tab-btn" onclick="showTab('reglements',this)"><i class="fa fa-money-bill"></i> Règlements</button>
    <button class="crma-tab-btn" onclick="showTab('encaissements',this)"><i class="fa fa-arrow-down"></i> Encaissements</button>
    <button class="crma-tab-btn" onclick="showTab('historique',this)"><i class="fa fa-history"></i> Historique</button>
</div>

<!-- ===== INFORMATIONS ===== -->
<div id="info" class="crma-tab-content" style="display:block;">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

    <div class="crma-card">
        <h4><i class="fa fa-car-burst"></i> Sinistre</h4>
        <div class="info-row-crma"><span>Date sinistre</span><span><?= $dossier['date_sinistre']; ?></span></div>
        <div class="info-row-crma"><span>Lieu</span><span><?= $dossier['lieu_sinistre']; ?></span></div>
        <div class="info-row-crma"><span>Délai déclaration</span><span><?= $dossier['delai_declaration']; ?> jours</span></div>
        <div class="info-row-crma"><span>Description</span><span><?= $dossier['description']; ?></span></div>
        <div class="info-row-crma"><span>Statut validation</span>
            <span><?php
            $sv_map = ['non_soumis'=>['gray','Non soumis'],'en_attente'=>['orange','En attente'],'valide'=>['green','Validé'],'refuse'=>['red','Refusé']];
            $sv = $sv_map[$dossier['statut_validation']] ?? ['gray',$dossier['statut_validation']];
            echo "<span class='badge-crma {$sv[0]}' style='font-size:11px;'>{$sv[1]}</span>";
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
            $rc = ['oui'=>'red','non'=>'green','partiel'=>'orange'];
            echo "<span class='badge-crma ".($rc[$r]??'gray')."' style='font-size:11px;'>".ucfirst($r)."</span>";
            ?></span>
        </div>
    </div>

    <div class="crma-card">
        <h4><i class="fa fa-info-circle"></i> État financier</h4>
        <div class="info-row-crma"><span>Réserve</span><span style="font-family:monospace;font-weight:700;color:#0d47a1;"><?= number_format($total_reserve,2,',',' '); ?> DA</span></div>
        <div class="info-row-crma"><span>Réglé</span><span style="font-family:monospace;font-weight:700;color:#2e7d32;"><?= number_format($total_regle,2,',',' '); ?> DA</span></div>
        <div class="info-row-crma"><span>Reste</span><span style="font-family:monospace;font-weight:700;color:<?= $reste>0?'#c62828':'#2e7d32'; ?>;"><?= number_format($reste,2,',',' '); ?> DA</span></div>
        <div class="info-row-crma"><span>Encaissements</span><span style="font-family:monospace;font-weight:700;color:#2e7d32;"><?= number_format($total_enc,2,',',' '); ?> DA</span></div>
        <div class="info-row-crma"><span>Coût réel</span><span style="font-family:monospace;font-weight:700;color:#e65100;"><?= number_format($cout_reel,2,',',' '); ?> DA</span></div>
    </div>

</div>
</div>

<!-- ===== DOCUMENTS ===== -->
<div id="documents" class="crma-tab-content">
<div class="crma-card">
    <h4><i class="fa fa-upload"></i> Ajouter document</h4>
    <form action="upload_document.php" method="POST" enctype="multipart/form-data" style="display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap;">
        <input type="hidden" name="id_dossier" value="<?= $id_dossier; ?>">
        <div style="flex:1;min-width:180px;">
            <label style="font-size:11px;font-weight:700;color:#546e7a;text-transform:uppercase;display:block;margin-bottom:6px;">Type</label>
            <select name="type" style="width:100%;padding:10px;border:1.5px solid #e0e0e0;border-radius:8px;">
                <?php $types=mysqli_query($conn,"SELECT * FROM type_document"); while($t=mysqli_fetch_assoc($types)) echo "<option value='{$t['id_type_document']}'>{$t['nom_type']}</option>"; ?>
            </select>
        </div>
        <div style="flex:2;min-width:200px;">
            <label style="font-size:11px;font-weight:700;color:#546e7a;text-transform:uppercase;display:block;margin-bottom:6px;">Fichier</label>
            <input type="file" name="fichier" required style="width:100%;padding:9px;border:1.5px solid #e0e0e0;border-radius:8px;background:#fafafa;">
        </div>
        <button type="submit" class="crma-btn primary"><i class="fa fa-upload"></i> Uploader</button>
    </form>
</div>

<table class="crma-table">
    <thead><tr><th>Type</th><th>Fichier</th><th>Date</th><th>Action</th></tr></thead>
    <tbody>
    <?php
    $docs=mysqli_query($conn,"SELECT d.*,t.nom_type FROM document d LEFT JOIN type_document t ON d.id_type_document=t.id_type_document WHERE d.id_dossier=$id_dossier");
    if(mysqli_num_rows($docs)==0) echo "<tr><td colspan='4' style='text-align:center;color:#90a4ae;padding:30px;'>Aucun document</td></tr>";
    while($d=mysqli_fetch_assoc($docs)){
        echo "<tr><td>{$d['nom_type']}</td><td><a href='../uploads/{$d['nom_fichier']}' target='_blank' class='crma-btn primary sm'><i class='fa fa-eye'></i> Voir</a></td><td>{$d['date_upload']}</td><td><a href='supprimer_documents.php?id={$d['id_document']}&dossier=$id_dossier' onclick=\"return confirm('Supprimer ?')\" class='crma-btn danger sm'><i class='fa fa-trash'></i></a></td></tr>";
    }
    ?>
    </tbody>
</table>
</div>

<!-- ===== EXPERTISE ===== -->
<div id="expertise" class="crma-tab-content">
<div class="crma-card">
    <h4><i class="fa fa-plus"></i> Ajouter expertise</h4>
    <form action="ajouter_expertise.php" method="POST" enctype="multipart/form-data" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
        <input type="hidden" name="id_dossier" value="<?= $id_dossier; ?>">
        <div>
            <label style="font-size:11px;font-weight:700;color:#546e7a;text-transform:uppercase;display:block;margin-bottom:6px;">Expert</label>
            <select name="id_expert" required style="width:100%;padding:10px;border:1.5px solid #e0e0e0;border-radius:8px;">
                <?php $experts=mysqli_query($conn,"SELECT * FROM expert"); while($e=mysqli_fetch_assoc($experts)){ $sel=($e['id_expert']==$expert_dossier['id_expert'])?"selected":""; echo "<option value='{$e['id_expert']}' $sel>{$e['nom']} {$e['prenom']}</option>"; } ?>
            </select>
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;color:#546e7a;text-transform:uppercase;display:block;margin-bottom:6px;">Date expertise</label>
            <input type="date" name="date_expertise" required style="width:100%;padding:10px;border:1.5px solid #e0e0e0;border-radius:8px;">
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;color:#546e7a;text-transform:uppercase;display:block;margin-bottom:6px;">Montant indemnité (DA)</label>
            <input type="number" name="montant_indemnite" required style="width:100%;padding:10px;border:1.5px solid #e0e0e0;border-radius:8px;">
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;color:#546e7a;text-transform:uppercase;display:block;margin-bottom:6px;">Rapport PDF</label>
            <input type="file" name="rapport" required style="width:100%;padding:9px;border:1.5px solid #e0e0e0;border-radius:8px;">
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;color:#546e7a;text-transform:uppercase;display:block;margin-bottom:6px;">Commentaire</label>
            <input type="text" name="commentaire" style="width:100%;padding:10px;border:1.5px solid #e0e0e0;border-radius:8px;">
        </div>
        <div style="display:flex;align-items:flex-end;">
            <button type="submit" class="crma-btn primary" style="width:100%;"><i class="fa fa-plus"></i> Ajouter</button>
        </div>
    </form>
</div>

<table class="crma-table">
    <thead><tr><th>Date</th><th>Expert</th><th>Montant</th><th>Rapport</th><th>Commentaire</th><th>Actions</th></tr></thead>
    <tbody>
    <?php
    $expertises=mysqli_query($conn,"SELECT ex.*,e.nom,e.prenom FROM expertise ex LEFT JOIN expert e ON ex.id_expert=e.id_expert WHERE ex.id_dossier=$id_dossier ORDER BY ex.id_expertise DESC");
    if(mysqli_num_rows($expertises)==0) echo "<tr><td colspan='6' style='text-align:center;color:#90a4ae;padding:30px;'>Aucune expertise</td></tr>";
    while($ex=mysqli_fetch_assoc($expertises)){
        echo "<tr>
        <td>{$ex['date_expertise']}</td>
        <td>{$ex['nom']} {$ex['prenom']}</td>
        <td style='font-family:monospace;font-weight:700;'>".number_format($ex['montant_indemnite'],2,',',' ')." DA</td>
        <td>".($ex['rapport_pdf']?"<a href='../uploads/{$ex['rapport_pdf']}' target='_blank' class='crma-btn primary sm'><i class='fa fa-file-pdf'></i> Voir</a>":'—')."</td>
        <td>{$ex['commentaire']}</td>
        <td>
            <a href='modifier_expertise.php?id={$ex['id_expertise']}' class='crma-btn warning sm'><i class='fa fa-pen'></i></a>
            <a href='supprimer_expertise.php?id={$ex['id_expertise']}&dossier=$id_dossier' onclick=\"return confirm('Supprimer ?')\" class='crma-btn danger sm'><i class='fa fa-trash'></i></a>
        </td></tr>";
    }
    ?>
    </tbody>
</table>
</div>

<!-- ===== RÉSERVES ===== -->
<div id="reserves" class="crma-tab-content">
<div style="display:flex;gap:14px;align-items:center;margin-bottom:18px;">
    <div class="fi-stat" style="background:#e8eaf6;">
        <div style="font-size:11px;color:#546e7a;font-weight:700;text-transform:uppercase;">Total Réserves</div>
        <div style="font-size:22px;font-weight:700;color:#0d47a1;font-family:monospace;"><?= number_format($total_reserve,2,',',' '); ?> DA</div>
    </div>
</div>

<?php if(in_array($etat,[1,2,3,7])): ?>
<div class="crma-card">
    <h4><i class="fa fa-plus"></i> Ajouter réserve</h4>
    <form action="ajouter_reserve.php" method="POST" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:14px;align-items:flex-end;">
        <input type="hidden" name="id_dossier" value="<?= $id_dossier; ?>">
        <div>
            <label style="font-size:11px;font-weight:700;color:#546e7a;text-transform:uppercase;display:block;margin-bottom:6px;">Montant (DA)</label>
            <input type="number" step="0.01" name="montant" required style="width:100%;padding:10px;border:1.5px solid #e0e0e0;border-radius:8px;">
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;color:#546e7a;text-transform:uppercase;display:block;margin-bottom:6px;">Garantie</label>
            <select name="id_garantie" style="width:100%;padding:10px;border:1.5px solid #e0e0e0;border-radius:8px;">
                <?php $gar=mysqli_query($conn,"SELECT * FROM garantie"); while($g=mysqli_fetch_assoc($gar)) echo "<option value='{$g['id_garantie']}'>{$g['nom_garantie']}</option>"; ?>
            </select>
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;color:#546e7a;text-transform:uppercase;display:block;margin-bottom:6px;">Commentaire</label>
            <input type="text" name="commentaire" style="width:100%;padding:10px;border:1.5px solid #e0e0e0;border-radius:8px;">
        </div>
        <button type="submit" class="crma-btn primary"><i class="fa fa-plus"></i> Ajouter</button>
    </form>
</div>
<?php else: ?>
<div class="msg-crma warning"><i class="fa fa-exclamation-triangle"></i> Impossible d'ajouter réserve — dossier dans cet état</div>
<?php endif; ?>

<table class="crma-table">
    <thead><tr><th>Date</th><th>Garantie</th><th>Montant</th><th>Type</th><th>Commentaire</th><th>Actions</th></tr></thead>
    <tbody>
    <?php
    $reserves=mysqli_query($conn,"SELECT r.*,g.nom_garantie FROM reserve r LEFT JOIN garantie g ON r.id_garantie=g.id_garantie WHERE r.id_dossier=$id_dossier ORDER BY r.id_reserve DESC");
    if(mysqli_num_rows($reserves)==0) echo "<tr><td colspan='6' style='text-align:center;color:#90a4ae;padding:30px;'>Aucune réserve</td></tr>";
    while($r=mysqli_fetch_assoc($reserves)){
        $type_colors=['initiale'=>'blue','expertise'=>'teal','ajustement'=>'orange'];
        $tc=$type_colors[$r['type_reserve']]??'gray';
        echo "<tr>
        <td>{$r['date_reserve']}</td>
        <td>{$r['nom_garantie']}</td>
        <td style='font-family:monospace;font-weight:700;'>".number_format($r['montant'],2,',',' ')." DA</td>
        <td><span class='badge-crma $tc' style='font-size:11px;'>{$r['type_reserve']}</span></td>
        <td>{$r['commentaire']}</td>
        <td>
            <a href='modifier_reserve.php?id={$r['id_reserve']}' class='crma-btn warning sm'><i class='fa fa-pen'></i></a>
            <a href='supprimer_reserve.php?id={$r['id_reserve']}&dossier=$id_dossier' onclick=\"return confirm('Supprimer ?')\" class='crma-btn danger sm'><i class='fa fa-trash'></i></a>
        </td></tr>";
    }
    ?>
    </tbody>
</table>
</div>

<!-- ===== RÈGLEMENTS ===== -->
<div id="reglements" class="crma-tab-content">
<div style="margin-bottom:18px;">
    <div class="fi-stat" style="background:#e8f5e9;display:inline-block;">
        <div style="font-size:11px;color:#546e7a;font-weight:700;text-transform:uppercase;">Total Réglé</div>
        <div style="font-size:22px;font-weight:700;color:#2e7d32;font-family:monospace;"><?= number_format($total_regle,2,',',' '); ?> DA</div>
    </div>
</div>

<?php if($etat==3): ?>
<div class="msg-crma info"><i class="fa fa-info-circle"></i> Règlement impossible — dossier transmis à la CNMA</div>
<?php elseif($etat==5): ?>
<div class="msg-crma error"><i class="fa fa-times-circle"></i> Règlement impossible — dossier refusé par la CNMA</div>
<?php elseif($etat==8): ?>
<div class="msg-crma success"><i class="fa fa-check-circle"></i> Dossier réglé totalement</div>
<?php else: ?>
<div class="crma-card">
    <h4><i class="fa fa-plus"></i> Ajouter règlement</h4>
    <form action="ajouter_reglement.php" method="POST" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:14px;align-items:flex-end;">
        <input type="hidden" name="id_dossier" value="<?= $id_dossier; ?>">
        <div>
            <label style="font-size:11px;font-weight:700;color:#546e7a;text-transform:uppercase;display:block;margin-bottom:6px;">Montant (DA)</label>
            <input type="number" step="0.01" name="montant" required style="width:100%;padding:10px;border:1.5px solid #e0e0e0;border-radius:8px;">
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;color:#546e7a;text-transform:uppercase;display:block;margin-bottom:6px;">Mode</label>
            <select name="mode" style="width:100%;padding:10px;border:1.5px solid #e0e0e0;border-radius:8px;">
                <option>Chèque</option>
            </select>
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;color:#546e7a;text-transform:uppercase;display:block;margin-bottom:6px;">Commentaire</label>
            <input type="text" name="commentaire" style="width:100%;padding:10px;border:1.5px solid #e0e0e0;border-radius:8px;">
        </div>
        <button type="submit" class="crma-btn primary"><i class="fa fa-plus"></i> Ajouter</button>
    </form>
</div>
<?php endif; ?>

<table class="crma-table">
    <thead><tr><th>Date</th><th>Montant</th><th>Mode</th><th>Statut</th><th>Commentaire</th><th>Actions</th></tr></thead>
    <tbody>
    <?php
    $reglements=mysqli_query($conn,"SELECT * FROM reglement WHERE id_dossier=$id_dossier ORDER BY id_reglement DESC");
    if(mysqli_num_rows($reglements)==0) echo "<tr><td colspan='6' style='text-align:center;color:#90a4ae;padding:30px;'>Aucun règlement</td></tr>";
    while($reg=mysqli_fetch_assoc($reglements)){
        $statuts=['en_attente'=>['orange','En attente'],'disponible'=>['green','Disponible'],'remis'=>['teal','Remis']];
        $s=$statuts[$reg['statut']]??['gray',$reg['statut']];
        echo "<tr>
        <td>{$reg['date_reglement']}</td>
        <td style='font-family:monospace;font-weight:700;'>".number_format($reg['montant'],2,',',' ')." DA</td>
        <td>{$reg['mode_paiement']}</td>
        <td><span class='badge-crma {$s[0]}' style='font-size:11px;'>{$s[1]}</span></td>
        <td>{$reg['commentaire']}</td>
        <td style='display:flex;gap:4px;flex-wrap:wrap;'>";
        echo "<a href='modifier_reglement.php?id={$reg['id_reglement']}' class='crma-btn warning sm'><i class='fa fa-pen'></i></a>";
        if($reg['statut']=='en_attente') echo "<a href='gerer_reglement_statut.php?id={$reg['id_reglement']}&dossier=$id_dossier&statut=disponible' class='crma-btn success sm' onclick=\"return confirm('Marquer disponible ?')\"><i class='fa fa-check'></i> Disponible</a>";
        if($reg['statut']=='disponible') echo "<a href='gerer_reglement_statut.php?id={$reg['id_reglement']}&dossier=$id_dossier&statut=remis' class='crma-btn secondary sm' onclick=\"return confirm('Marquer remis ?')\"><i class='fa fa-handshake'></i> Remis</a>";
        echo "<a href='supprimer_reglement.php?id={$reg['id_reglement']}&dossier=$id_dossier' onclick=\"return confirm('Supprimer ?')\" class='crma-btn danger sm'><i class='fa fa-trash'></i></a>";
        echo "</td></tr>";
    }
    ?>
    </tbody>
</table>
</div>

<!-- ===== ENCAISSEMENTS (CORRIGÉ - DANS LE MAIN) ===== -->
<div id="encaissements" class="crma-tab-content">

<!-- KPIs -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px;">
    <div class="fi-stat" style="background:#e8eaf6;">
        <div style="font-size:11px;color:#546e7a;font-weight:700;text-transform:uppercase;">Total Règlements</div>
        <div style="font-size:20px;font-weight:700;color:#0d47a1;font-family:monospace;"><?= number_format($total_regle,2,',',' '); ?> DA</div>
    </div>
    <div class="fi-stat" style="background:#e8f5e9;">
        <div style="font-size:11px;color:#546e7a;font-weight:700;text-transform:uppercase;">Total Encaissements</div>
        <div style="font-size:20px;font-weight:700;color:#2e7d32;font-family:monospace;"><?= number_format($total_enc,2,',',' '); ?> DA</div>
    </div>
    <div class="fi-stat" style="background:#fff3e0;">
        <div style="font-size:11px;color:#546e7a;font-weight:700;text-transform:uppercase;">Coût réel sinistre</div>
        <div style="font-size:20px;font-weight:700;color:#e65100;font-family:monospace;"><?= number_format($cout_reel,2,',',' '); ?> DA</div>
    </div>
    <div class="fi-stat" style="background:#e0f2f1;">
        <div style="font-size:11px;color:#546e7a;font-weight:700;text-transform:uppercase;">Taux de recours</div>
        <div style="font-size:20px;font-weight:700;color:#00695c;"><?= $total_regle>0 ? round($total_enc/$total_regle*100,1) : 0; ?>%</div>
    </div>
</div>

<?php if(in_array($etat,[7,8,13,14])): ?>
<div class="crma-card">
    <h4><i class="fa fa-plus"></i> Enregistrer un encaissement</h4>
    <form action="ajouter_encaissement.php" method="POST" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:14px;align-items:flex-end;">
        <input type="hidden" name="id_dossier" value="<?= $id_dossier; ?>">
        <div>
            <label style="font-size:11px;font-weight:700;color:#546e7a;text-transform:uppercase;display:block;margin-bottom:6px;">Montant (DA) *</label>
            <input type="number" step="0.01" name="montant" required style="width:100%;padding:10px;border:1.5px solid #e0e0e0;border-radius:8px;">
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;color:#546e7a;text-transform:uppercase;display:block;margin-bottom:6px;">Date *</label>
            <input type="date" name="date_encaissement" value="<?= date('Y-m-d'); ?>" required style="width:100%;padding:10px;border:1.5px solid #e0e0e0;border-radius:8px;">
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;color:#546e7a;text-transform:uppercase;display:block;margin-bottom:6px;">Tiers (qui paie) *</label>
            <select name="id_tiers" required style="width:100%;padding:10px;border:1.5px solid #e0e0e0;border-radius:8px;">
                <?php
                $trs=mysqli_query($conn,"SELECT t.id_tiers,p.nom,p.prenom,t.compagnie_assurance FROM tiers t JOIN personne p ON t.id_personne=p.id_personne");
                while($tr=mysqli_fetch_assoc($trs)) echo "<option value='{$tr['id_tiers']}'>{$tr['nom']} {$tr['prenom']} — {$tr['compagnie_assurance']}</option>";
                ?>
            </select>
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;color:#546e7a;text-transform:uppercase;display:block;margin-bottom:6px;">Type</label>
            <select name="type" style="width:100%;padding:10px;border:1.5px solid #e0e0e0;border-radius:8px;">
                <option value="recours">Recours</option>
                <option value="franchise">Franchise</option>
                <option value="epave">Épave</option>
                <option value="autre">Autre</option>
            </select>
        </div>
        <div style="grid-column:1/3;">
            <label style="font-size:11px;font-weight:700;color:#546e7a;text-transform:uppercase;display:block;margin-bottom:6px;">Commentaire</label>
            <input type="text" name="commentaire" placeholder="Optionnel" style="width:100%;padding:10px;border:1.5px solid #e0e0e0;border-radius:8px;">
        </div>
        <div style="grid-column:4;display:flex;align-items:flex-end;">
            <button type="submit" class="crma-btn primary" style="width:100%;padding:12px;"><i class="fa fa-save"></i> Enregistrer</button>
        </div>
    </form>
</div>
<?php else: ?>
<div class="msg-crma warning">
    <i class="fa fa-exclamation-triangle"></i>
    Encaissement possible uniquement pour les dossiers en état : <b>Règlement partiel</b>, <b>Règlement total</b>, <b>Classé en attente recours</b> ou <b>Clôturé</b>.<br>
    État actuel : <b><?= $dossier['nom_etat']; ?></b>
</div>
<?php endif; ?>

<h4 style="margin:20px 0 12px;color:#0d7b1c;font-size:15px;"><i class="fa fa-list"></i> Liste des encaissements</h4>
<table class="crma-table">
    <thead><tr><th>Date</th><th>Tiers</th><th>Type</th><th>Montant</th><th>Commentaire</th></tr></thead>
    <tbody>
    <?php
    $encs=mysqli_query($conn,"SELECT enc.*,p.nom,p.prenom,t.compagnie_assurance FROM encaissement enc JOIN tiers t ON enc.id_tiers=t.id_tiers JOIN personne p ON t.id_personne=p.id_personne WHERE enc.id_dossier=$id_dossier ORDER BY enc.id_encaissement DESC");
    if(mysqli_num_rows($encs)==0) echo "<tr><td colspan='5' style='text-align:center;color:#90a4ae;padding:30px;'>Aucun encaissement enregistré</td></tr>";
    $type_icons=['recours'=>'🔄','franchise'=>'📋','epave'=>'🚗','autre'=>'📝'];
    while($enc=mysqli_fetch_assoc($encs)){
        $ti=$type_icons[$enc['type']]??'📝';
        echo "<tr>
        <td>{$enc['date_encaissement']}</td>
        <td><b>{$enc['nom']} {$enc['prenom']}</b><br><small>{$enc['compagnie_assurance']}</small></td>
        <td><span class='badge-crma blue' style='font-size:11px;'>$ti {$enc['type']}</span></td>
        <td style='font-family:monospace;font-weight:700;color:#2e7d32;'>".number_format($enc['montant'],2,',',' ')." DA</td>
        <td>{$enc['commentaire']}</td></tr>";
    }
    ?>
    </tbody>
</table>
</div>

<!-- ===== HISTORIQUE ===== -->
<div id="historique" class="crma-tab-content">
<table class="crma-table">
    <thead><tr><th>Date / Heure</th><th>Action</th><th>Ancien état</th><th>Nouvel état</th></tr></thead>
    <tbody>
    <?php
    $hist=mysqli_query($conn,"SELECT h.*,ea.nom_etat AS ancien,en.nom_etat AS nouveau FROM historique h LEFT JOIN etat_dossier ea ON h.ancien_etat=ea.id_etat LEFT JOIN etat_dossier en ON h.nouvel_etat=en.id_etat WHERE h.id_dossier=$id_dossier ORDER BY h.date_action DESC");
    while($h=mysqli_fetch_assoc($hist)){
        $a=strtolower($h['action']);
        if(str_contains($a,'valid')) $ac="background:#e8f5e9;color:#1b5e20;";
        elseif(str_contains($a,'refus')) $ac="background:#ffebee;color:#b71c1c;";
        elseif(str_contains($a,'règlement')||str_contains($a,'reglement')) $ac="background:#e0f2f1;color:#004d40;";
        elseif(str_contains($a,'réserve')||str_contains($a,'reserve')) $ac="background:#e3f2fd;color:#0d47a1;";
        elseif(str_contains($a,'créat')||str_contains($a,'creat')) $ac="background:#e8eaf6;color:#283593;";
        elseif(str_contains($a,'encaissement')) $ac="background:#f3e5f5;color:#4a148c;";
        else $ac="background:#eceff1;color:#37474f;";
        echo "<tr>
        <td style='font-size:12px;'><b>".date('d/m/Y',strtotime($h['date_action']))."</b><br><small>".date('H:i:s',strtotime($h['date_action']))."</small></td>
        <td><span style='$ac padding:4px 10px;border-radius:12px;font-size:12px;font-weight:600;'>{$h['action']}</span></td>
        <td style='font-size:12px;color:#78909c;'>".($h['ancien']??'—')."</td>
        <td style='font-size:12px;color:#78909c;'>".($h['nouveau']??'—')."</td>
        </tr>";
    }
    ?>
    </tbody>
</table>
</div>

</div><!-- FIN .main -->

<script>
function showTab(tab, btn){
    document.querySelectorAll('.crma-tab-content').forEach(t=>t.style.display='none');
    document.querySelectorAll('.crma-tab-btn').forEach(b=>b.classList.remove('active'));
    document.getElementById(tab).style.display='block';
    if(btn) btn.classList.add('active');
}
const params=new URLSearchParams(window.location.search);
const tab=params.get('tab');
if(tab){
    const btn=document.querySelector(`.crma-tab-btn[onclick*="${tab}"]`);
    showTab(tab,btn);
}
</script>
</body>
</html>