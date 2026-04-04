<?php
session_start();
include('../includes/config.php');
if(!isset($_SESSION['id_user']) || $_SESSION['role'] != 'ASSURE') { header("Location: ../pages/login.php"); exit(); }
$id_user = $_SESSION['id_user'];
$page_title = "Mes paiements";

$assure = mysqli_fetch_assoc(mysqli_query($conn,"SELECT a.id_assure FROM assure a JOIN utilisateur u ON a.id_personne=u.id_personne WHERE u.id_user=$id_user LIMIT 1"));
$id_assure = $assure ? $assure['id_assure'] : 0;

$paiements = mysqli_query($conn,"
    SELECT r.*, d.numero_dossier, d.id_dossier
    FROM reglement r
    JOIN dossier d ON r.id_dossier=d.id_dossier
    JOIN contrat c ON d.id_contrat=c.id_contrat
    WHERE c.id_assure=$id_assure
    ORDER BY r.id_reglement DESC
");

$total_recu = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(r.montant),0) as n FROM reglement r JOIN dossier d ON r.id_dossier=d.id_dossier JOIN contrat c ON d.id_contrat=c.id_contrat WHERE c.id_assure=$id_assure AND r.statut='remis'"))['n'];
$total_disponible = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(r.montant),0) as n FROM reglement r JOIN dossier d ON r.id_dossier=d.id_dossier JOIN contrat c ON d.id_contrat=c.id_contrat WHERE c.id_assure=$id_assure AND r.statut='disponible'"))['n'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mes paiements</title>
<link rel="stylesheet" href="../css/style_assure.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php include('sidebar_assure.php'); ?>
<?php include('header_assure.php'); ?>
<div class="assure-main">
    <div class="page-heading">
        <h2><i class="fa fa-money-check-alt"></i> Mes paiements</h2>
    </div>

    <?php if($total_disponible > 0): ?>
    <div class="msg success" style="font-size:15px;">
        <i class="fa fa-check-circle" style="font-size:20px;"></i>
        <strong>Un chèque est disponible !</strong> Montant : <?= number_format($total_disponible,2,',',' '); ?> DA — Veuillez vous présenter à votre agence CRMA pour le récupérer.
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:22px;">
        <div class="assure-card" style="border-left:4px solid #2e7d32;">
            <div style="font-size:11px;color:#78909c;font-weight:700;text-transform:uppercase;margin-bottom:8px;">Total reçu</div>
            <div class="money-cell" style="font-size:26px;"><?= number_format($total_recu,2,',',' '); ?> <span style="font-size:14px;font-family:Arial;">DA</span></div>
        </div>
        <div class="assure-card" style="border-left:4px solid #f57c00;">
            <div style="font-size:11px;color:#78909c;font-weight:700;text-transform:uppercase;margin-bottom:8px;">Chèque disponible</div>
            <div class="money-cell" style="font-size:26px;color:#e65100;"><?= number_format($total_disponible,2,',',' '); ?> <span style="font-size:14px;font-family:Arial;">DA</span></div>
        </div>
    </div>

    <div class="assure-card">
        <h3><i class="fa fa-list"></i> Historique des paiements</h3>
        <?php if(mysqli_num_rows($paiements)==0): ?>
        <p style="text-align:center;color:#90a4ae;padding:30px;">Aucun paiement pour le moment</p>
        <?php else: ?>
        <table class="assure-table">
            <thead><tr><th>N° Dossier</th><th>Date</th><th>Montant</th><th>Mode</th><th>Référence</th><th>Statut</th></tr></thead>
            <tbody>
            <?php while($r = mysqli_fetch_assoc($paiements)):
                $sc = ['en_attente'=>['orange','En attente'],'disponible'=>['green','Disponible — venir chercher'],'remis'=>['teal','Chèque remis']];
                $si = $sc[$r['statut']] ?? ['gray',$r['statut']];
            ?>
            <tr>
                <td><b style="color:#0d47a1;"><?= $r['numero_dossier']; ?></b></td>
                <td><?= $r['date_reglement']; ?></td>
                <td class="money-cell"><?= number_format($r['montant'],2,',',' '); ?> DA</td>
                <td><?= $r['mode_paiement']; ?></td>
                <td><?= $r['reference_paiement'] ?: '—'; ?></td>
                <td><span class="badge-etat <?= $si[0]; ?>"><?= $si[1]; ?></span></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>