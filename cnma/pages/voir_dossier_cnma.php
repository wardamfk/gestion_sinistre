<?php
include("../includes/auth.php");
include("../includes/config.php");

if($_SESSION['role'] != 'CNMA') {
    header("Location: login.php");
    exit();
}

if(!isset($_GET['id'])) { echo "Dossier introuvable"; exit(); }
$id_dossier = intval($_GET['id']);

// === DOSSIER ===
$dossier = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT d.*, e.nom_etat,
           c.numero_police,
           p.nom AS nom_assure, p.prenom AS prenom_assure,
           p.telephone AS tel_assure,
           pt.nom AS nom_tiers, pt.prenom AS prenom_tiers,
           t.compagnie_assurance, t.responsable,
           v.marque, v.modele, v.matricule,
           ex.nom AS nom_expert, ex.prenom AS prenom_expert,
           ag.nom_agence, ag.wilaya,
           u.nom AS agent_nom
    FROM dossier d
    LEFT JOIN etat_dossier e ON d.id_etat = e.id_etat
    LEFT JOIN contrat c ON d.id_contrat = c.id_contrat
    LEFT JOIN assure ass ON c.id_assure = ass.id_assure
    LEFT JOIN personne p ON ass.id_personne = p.id_personne
    LEFT JOIN tiers t ON d.id_tiers = t.id_tiers
    LEFT JOIN personne pt ON t.id_personne = pt.id_personne
    LEFT JOIN vehicule v ON c.id_vehicule = v.id_vehicule
    LEFT JOIN expert ex ON d.id_expert = ex.id_expert
    LEFT JOIN utilisateur u ON d.cree_par = u.id_user
    LEFT JOIN agence ag ON u.id_agence = ag.id_agence
    WHERE d.id_dossier = $id_dossier
"));

if(!$dossier) { die("Dossier introuvable"); }

$etat = $dossier['id_etat'];

// Totaux
$total_reserve = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT IFNULL(SUM(montant),0) as t FROM reserve WHERE id_dossier=$id_dossier"))['t'];
$total_regle = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT IFNULL(SUM(montant),0) as t FROM reglement WHERE id_dossier=$id_dossier"))['t'];
$reste = $total_reserve - $total_regle;

// Badge état
$badge_class = "badge";
if($etat==2) $badge_class.=" blue";
elseif($etat==3) $badge_class.=" orange";
elseif($etat==4) $badge_class.=" green";
elseif($etat==5) $badge_class.=" red";
elseif($etat==7 || $etat==8) $badge_class.=" gray";
elseif($etat==14) $badge_class.=" gray";
else $badge_class.=" gray";

// Message succès/info
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dossier <?php echo $dossier['numero_dossier']; ?> — CNMA</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/style_cnma.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .dossier-header {
            background: white; border-radius: 12px; padding: 20px 25px;
            margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            display: flex; justify-content: space-between; align-items: center;
        }
        .dossier-header h2 { margin: 0; color: #1f3a5f; }
        .action-bar {
            display: flex; gap: 10px; flex-wrap: wrap;
            background: white; padding: 15px 20px;
            border-radius: 10px; margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        }
        .btn-valider  { background: #27ae60 !important; }
        .btn-refuser  { background: #e74c3c !important; }
        .btn-complement { background: #f39c12 !important; }
        .btn-cloturer { background: #8e44ad !important; }
        .btn-retour   { background: #7f8c8d !important; }

        .info-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 20px; margin-bottom: 20px;
        }
        .info-box {
            background: white; border-radius: 10px; padding: 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        }
        .info-box h4 {
            margin: 0 0 15px; color: #0d7b1c; font-size: 14px;
            text-transform: uppercase; letter-spacing: 1px;
            border-bottom: 2px solid #e8f5e9; padding-bottom: 8px;
        }
        .info-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f5f5f5; font-size: 14px; }
        .info-row:last-child { border-bottom: none; }
        .info-row .label { color: #666; }
        .info-row .val { font-weight: bold; color: #333; }

        .finance-bar {
            display: flex; gap: 15px; margin-bottom: 20px;
        }
        .finance-item {
            flex: 1; background: white; border-radius: 10px; padding: 15px 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06); text-align: center;
        }
        .finance-item .lbl { font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 6px; }
        .finance-item .num { font-size: 22px; font-weight: bold; font-family: 'Courier New', monospace; color: #1f3a5f; }

        .msg-success { background: #d4edda; color: #155724; padding: 12px 18px; border-radius: 8px; margin-bottom: 15px; }
        .msg-info    { background: #cce5ff; color: #004085; padding: 12px 18px; border-radius: 8px; margin-bottom: 15px; }
        .msg-warning { background: #fff3cd; color: #856404; padding: 12px 18px; border-radius: 8px; margin-bottom: 15px; }

        .action-disabled { background: #ccc !important; cursor: not-allowed; opacity: 0.6; }
        .section-label { font-size: 11px; color: #999; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
    </style>
</head>
<body>

<?php include("sidebar_cnma.php"); ?>
<?php include("../includes/header.php"); ?>

<div class="main">

    <!-- ENTÊTE DOSSIER -->
    <div class="dossier-header">
        <div>
            <h2><i class="fa fa-folder-open"></i> <?php echo $dossier['numero_dossier']; ?></h2>
            <div style="margin-top:6px;">
                <span class="<?php echo $badge_class; ?>"><?php echo $dossier['nom_etat']; ?></span>
                <span style="margin-left:10px; font-size:13px; color:#666;">
                    Agence : <b><?php echo $dossier['nom_agence']; ?></b> — <?php echo $dossier['wilaya']; ?>
                </span>
            </div>
        </div>
        <div style="font-size:13px; color:#666; text-align:right;">
            Créé le : <b><?php echo $dossier['date_creation']; ?></b><br>
            Agent : <b><?php echo $dossier['agent_nom']; ?></b>
        </div>
    </div>

    <!-- MESSAGES -->
    <?php if($msg == 'valide'): ?>
    <div class="msg-success"><i class="fa fa-check-circle"></i> Dossier validé avec succès — le règlement peut maintenant être effectué.</div>
    <?php elseif($msg == 'refuse'): ?>
    <div class="msg-warning"><i class="fa fa-times-circle"></i> Dossier marqué comme refusé.</div>
    <?php elseif($msg == 'complement'): ?>
    <div class="msg-info"><i class="fa fa-info-circle"></i> Demande de complément envoyée au CRMA.</div>
    <?php elseif($msg == 'cloture'): ?>
    <div class="msg-success"><i class="fa fa-archive"></i> Dossier clôturé avec succès.</div>
    <?php endif; ?>

    <!-- BARRE D'ACTIONS CNMA -->
    <div class="action-bar">
        <div class="section-label" style="width:100%; margin:0 0 5px;">Actions disponibles :</div>

        <!-- Valider : seulement si en attente (état 3) -->
        <?php if($etat == 3): ?>
        <a href="valider_cnma.php?id=<?php echo $id_dossier; ?>"
           class="btn btn-valider"
           onclick="return confirm('Valider ce dossier ? Le CRMA pourra effectuer le règlement.')">
            <i class="fa fa-check-circle"></i> Valider
        </a>
        <a href="refuser_cnma.php?id=<?php echo $id_dossier; ?>"
           class="btn btn-refuser"
           onclick="return confirm('Refuser ce dossier ?')">
            <i class="fa fa-times-circle"></i> Refuser
        </a>
        <a href="complement_cnma.php?id=<?php echo $id_dossier; ?>"
           class="btn btn-complement"
           onclick="return confirm('Demander un complément au CRMA ?')">
            <i class="fa fa-paper-plane"></i> Demander complément
        </a>

        <?php elseif($etat == 8): ?>
<span style="color:#27ae60; font-weight:bold; padding:10px;">
    <i class="fa fa-check-circle"></i> Dossier réglé (aucune action CNMA)
</span>

        <?php elseif($etat == 14): ?>
        <span style="color:#27ae60; font-weight:bold; padding:10px;">
            <i class="fa fa-check-circle"></i> Dossier clôturé
        </span>

        <?php else: ?>
        <span style="color:#999; font-size:13px; padding:10px;">
            <i class="fa fa-info-circle"></i>
            <?php
            if($etat == 4) echo "Dossier validé — en cours de règlement par le CRMA";
            elseif($etat == 5) echo "Dossier refusé";
            elseif($etat == 2) echo "Dossier en cours CRMA — pas encore transmis";
            elseif($etat == 7) echo "Règlement partiel en cours";
            else echo "Aucune action disponible pour cet état";
            ?>
        </span>
        <?php endif; ?>

        <a href="tous_dossiers_cnma.php" class="btn btn-retour" style="margin-left:auto;">
            <i class="fa fa-arrow-left"></i> Retour à la liste
        </a>
    </div>

    <!-- BILAN FINANCIER -->
    <div class="finance-bar">
        <div class="finance-item">
            <div class="lbl">Total Réserves</div>
            <div class="num"><?php echo number_format($total_reserve, 2, ',', ' '); ?> <small style="font-size:14px;">DA</small></div>
        </div>
        <div class="finance-item">
            <div class="lbl">Total Réglé</div>
            <div class="num"><?php echo number_format($total_regle, 2, ',', ' '); ?> <small style="font-size:14px;">DA</small></div>
        </div>
        <div class="finance-item">
            <div class="lbl">Reste à régler</div>
            <div class="num" style="color:<?php echo $reste > 0 ? '#e74c3c' : '#27ae60'; ?>">
                <?php echo number_format($reste, 2, ',', ' '); ?> <small style="font-size:14px;">DA</small>
            </div>
        </div>
        <div class="finance-item">
            <div class="lbl">Statut validation</div>
            <div class="num" style="font-size:15px; margin-top:5px;">
                <?php
                $sv = $dossier['statut_validation'];
                $sv_colors = ['non_soumis'=>'#999','en_attente'=>'#f39c12','valide'=>'#27ae60','refuse'=>'#e74c3c'];
                echo "<span style='color:".($sv_colors[$sv]??'#333')."'>".$sv."</span>";
                ?>
            </div>
        </div>
    </div>

    <!-- INFOS GÉNÉRALES -->
    <div class="info-grid">
        <!-- SINISTRE -->
        <div class="info-box">
            <h4><i class="fa fa-car-burst"></i> Sinistre</h4>
            <div class="info-row"><span class="label">Date sinistre</span><span class="val"><?php echo $dossier['date_sinistre']; ?></span></div>
            <div class="info-row"><span class="label">Lieu</span><span class="val"><?php echo $dossier['lieu_sinistre']; ?></span></div>
            <div class="info-row"><span class="label">Délai déclaration</span><span class="val"><?php echo $dossier['delai_declaration']; ?> jours</span></div>
            <div class="info-row"><span class="label">Description</span><span class="val"><?php echo $dossier['description']; ?></span></div>
            <?php if($dossier['info_complementaire']): ?>
            <div class="info-row"><span class="label">Info complémentaire</span><span class="val"><?php echo $dossier['info_complementaire']; ?></span></div>
            <?php endif; ?>
        </div>

        <!-- ASSURÉ -->
        <div class="info-box">
            <h4><i class="fa fa-user"></i> Assuré</h4>
            <div class="info-row"><span class="label">Nom</span><span class="val"><?php echo $dossier['nom_assure'].' '.$dossier['prenom_assure']; ?></span></div>
            <div class="info-row"><span class="label">Téléphone</span><span class="val"><?php echo $dossier['tel_assure']; ?></span></div>
            <div class="info-row"><span class="label">N° Police</span><span class="val"><?php echo $dossier['numero_police']; ?></span></div>
            <div class="info-row"><span class="label">Véhicule</span><span class="val"><?php echo $dossier['marque'].' '.$dossier['modele'].' — '.$dossier['matricule']; ?></span></div>
            <div class="info-row"><span class="label">Expert</span><span class="val">
                <?php echo $dossier['nom_expert'] ? $dossier['nom_expert'].' '.$dossier['prenom_expert'] : 'Non affecté'; ?>
            </span></div>
        </div>

        <!-- TIERS -->
        <div class="info-box">
            <h4><i class="fa fa-users"></i> Tiers</h4>
            <div class="info-row"><span class="label">Nom</span><span class="val"><?php echo $dossier['nom_tiers'].' '.$dossier['prenom_tiers']; ?></span></div>
            <div class="info-row"><span class="label">Compagnie</span><span class="val"><?php echo $dossier['compagnie_assurance']; ?></span></div>
            <div class="info-row"><span class="label">Responsabilité</span>
                <span class="val">
                    <?php
                    $r = $dossier['responsable'];
                    $rc = ['oui'=>'#e74c3c','non'=>'#27ae60','partiel'=>'#f39c12'];
                    echo "<span style='color:".($rc[$r]??'#333')."'>".ucfirst($r)."</span>";
                    ?>
                </span>
            </div>
        </div>

        <!-- TRANSMISSION -->
        <div class="info-box">
            <h4><i class="fa fa-paper-plane"></i> Transmission</h4>
            <div class="info-row"><span class="label">Transmis le</span><span class="val"><?php echo $dossier['date_transmission'] ?: '—'; ?></span></div>
            <div class="info-row"><span class="label">Validé le</span><span class="val"><?php echo $dossier['date_validation'] ?: '—'; ?></span></div>
        </div>
    </div>

    <!-- ONGLETS -->
    <div class="tabs" style="margin-top:10px;">
        <button class="tab-btn" onclick="showTab('expertises')">Expertises</button>
        <button class="tab-btn" onclick="showTab('reserves')">Réserves</button>
        <button class="tab-btn" onclick="showTab('reglements')">Règlements</button>
        <button class="tab-btn" onclick="showTab('documents')">Documents</button>
        <button class="tab-btn" onclick="showTab('historique')">Historique</button>
    </div>

    <!-- EXPERTISES -->
    <div id="expertises" class="tab-content">
        <h3>Expertises</h3>
        <table class="table">
            <tr><th>Date</th><th>Expert</th><th>Montant indemnité</th><th>Rapport</th><th>Commentaire</th></tr>
            <?php
            $exps = mysqli_query($conn, "
                SELECT ex.*, e.nom, e.prenom
                FROM expertise ex
                LEFT JOIN expert e ON ex.id_expert = e.id_expert
                WHERE ex.id_dossier = $id_dossier
                ORDER BY ex.id_expertise DESC
            ");
            $nb = 0;
            while($ex = mysqli_fetch_assoc($exps)):
                $nb++;
            ?>
            <tr>
                <td><?php echo $ex['date_expertise']; ?></td>
                <td><?php echo $ex['nom'].' '.$ex['prenom']; ?></td>
                <td><b><?php echo number_format($ex['montant_indemnite'] ?? 0, 2, ',', ' '); ?> DA</b></td>
                <td>
                    <?php if($ex['rapport_pdf']): ?>
                    <a href="../uploads/<?php echo $ex['rapport_pdf']; ?>" target="_blank">
                        <i class="fa fa-file-pdf"></i> Voir
                    </a>
                    <?php else: echo "—"; endif; ?>
                </td>
                <td><?php echo $ex['commentaire'] ?: '—'; ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if($nb == 0) echo "<tr><td colspan='5' style='text-align:center;color:#999;'>Aucune expertise</td></tr>"; ?>
        </table>
    </div>

    <!-- RÉSERVES -->
    <div id="reserves" class="tab-content">
        <h3>Réserves — Total : <b><?php echo number_format($total_reserve,2,',',' '); ?> DA</b></h3>
        <table class="table">
            <tr><th>Date</th><th>Garantie</th><th>Type</th><th>Montant</th><th>Commentaire</th></tr>
            <?php
            $res = mysqli_query($conn, "
                SELECT r.*, g.nom_garantie FROM reserve r
                LEFT JOIN garantie g ON r.id_garantie = g.id_garantie
                WHERE r.id_dossier = $id_dossier ORDER BY r.id_reserve DESC
            ");
            $nb = 0;
            while($r = mysqli_fetch_assoc($res)):
                $nb++;
            ?>
            <tr>
                <td><?php echo $r['date_reserve']; ?></td>
                <td><?php echo $r['nom_garantie']; ?></td>
                <td><?php echo $r['type_reserve']; ?></td>
                <td><b><?php echo number_format($r['montant'],2,',',' '); ?> DA</b></td>
                <td><?php echo $r['commentaire'] ?: '—'; ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if($nb == 0) echo "<tr><td colspan='5' style='text-align:center;color:#999;'>Aucune réserve</td></tr>"; ?>
        </table>
    </div>

    <!-- RÈGLEMENTS -->
    <div id="reglements" class="tab-content">
        <h3>Règlements — Total réglé : <b><?php echo number_format($total_regle,2,',',' '); ?> DA</b></h3>
        <table class="table">
            <tr><th>Date</th><th>Montant</th><th>Mode</th><th>Commentaire</th></tr>
            <?php
            $regs = mysqli_query($conn, "SELECT * FROM reglement WHERE id_dossier=$id_dossier ORDER BY id_reglement DESC");
            $nb = 0;
            while($reg = mysqli_fetch_assoc($regs)):
                $nb++;
            ?>
            <tr>
                <td><?php echo $reg['date_reglement']; ?></td>
                <td><b><?php echo number_format($reg['montant'],2,',',' '); ?> DA</b></td>
                <td><?php echo $reg['mode_paiement']; ?></td>
                <td><?php echo $reg['commentaire'] ?: '—'; ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if($nb == 0) echo "<tr><td colspan='4' style='text-align:center;color:#999;'>Aucun règlement</td></tr>"; ?>
        </table>
    </div>

    <!-- DOCUMENTS -->
    <div id="documents" class="tab-content">
        <h3>Documents</h3>
        <table class="table">
            <tr><th>Type</th><th>Fichier</th><th>Date upload</th></tr>
            <?php
            $docs = mysqli_query($conn, "
                SELECT d.*, t.nom_type FROM document d
                LEFT JOIN type_document t ON d.id_type_document = t.id_type_document
                WHERE d.id_dossier = $id_dossier
            ");
            $nb = 0;
            while($d = mysqli_fetch_assoc($docs)):
                $nb++;
            ?>
            <tr>
                <td><?php echo $d['nom_type']; ?></td>
                <td><a href="../uploads/<?php echo $d['nom_fichier']; ?>" target="_blank"><i class="fa fa-eye"></i> <?php echo $d['nom_fichier']; ?></a></td>
                <td><?php echo $d['date_upload']; ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if($nb == 0) echo "<tr><td colspan='3' style='text-align:center;color:#999;'>Aucun document</td></tr>"; ?>
        </table>
    </div>

    <!-- HISTORIQUE -->
    <div id="historique" class="tab-content">
        <h3>Historique des actions</h3>
        <table class="table">
            <tr><th>Date</th><th>Action</th><th>Ancien état</th><th>Nouvel état</th></tr>
            <?php
            $hist = mysqli_query($conn, "
                SELECT h.*, ea.nom_etat AS ancien, en.nom_etat AS nouveau
                FROM historique h
                LEFT JOIN etat_dossier ea ON h.ancien_etat = ea.id_etat
                LEFT JOIN etat_dossier en ON h.nouvel_etat = en.id_etat
                WHERE h.id_dossier = $id_dossier
                ORDER BY h.date_action DESC
            ");
            while($h = mysqli_fetch_assoc($hist)):
            ?>
            <tr>
                <td><?php echo $h['date_action']; ?></td>
                <td><?php echo $h['action']; ?></td>
                <td><?php echo $h['ancien'] ?: '—'; ?></td>
                <td><?php echo $h['nouveau'] ?: '—'; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

</div>

<script>
function showTab(tab){
    document.querySelectorAll(".tab-content").forEach(t => t.style.display="none");
    document.getElementById(tab).style.display="block";
}
const p = new URLSearchParams(window.location.search);
showTab(p.get("tab") || "expertises");
</script>
</body>
</html>
