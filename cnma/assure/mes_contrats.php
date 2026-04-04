<?php
session_start();
include('../includes/config.php');
if(!isset($_SESSION['id_user']) || $_SESSION['role'] != 'ASSURE') { header("Location: ../pages/login.php"); exit(); }
$id_user = $_SESSION['id_user'];
$page_title = "Mes contrats";

$assure = mysqli_fetch_assoc(mysqli_query($conn,"SELECT a.id_assure FROM assure a JOIN utilisateur u ON a.id_personne=u.id_personne WHERE u.id_user=$id_user LIMIT 1"));
$id_assure = $assure ? $assure['id_assure'] : 0;

$contrats = mysqli_query($conn,"
    SELECT c.*, f.nom_formule, v.marque, v.modele, v.matricule, v.annee, ag.nom_agence, ag.wilaya
    FROM contrat c
    LEFT JOIN formule f ON c.id_formule=f.id_formule
    LEFT JOIN vehicule v ON c.id_vehicule=v.id_vehicule
    LEFT JOIN agence ag ON c.id_agence=ag.id_agence
    WHERE c.id_assure=$id_assure ORDER BY c.id_contrat DESC
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mes contrats</title>
<link rel="stylesheet" href="../css/style_assure.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php include('sidebar_assure.php'); ?>
<?php include('header_assure.php'); ?>
<div class="assure-main">
    <div class="page-heading">
        <h2><i class="fa fa-file-contract"></i> Mes contrats</h2>
    </div>

    <?php if(mysqli_num_rows($contrats)==0): ?>
    <div style="text-align:center;padding:60px;background:white;border-radius:14px;">
        <i class="fa fa-file-contract" style="font-size:48px;color:#cfd8dc;display:block;margin-bottom:16px;"></i>
        <p style="color:#90a4ae;">Aucun contrat pour le moment</p>
    </div>
    <?php else: while($c = mysqli_fetch_assoc($contrats)):
        $sc = ['actif'=>['green','Actif'],'expire'=>['red','Expiré'],'suspendu'=>['orange','Suspendu']];
        $si = $sc[$c['statut']] ?? ['gray',$c['statut']];
        $expire_bientot = (strtotime($c['date_expiration']) - time()) < 30*24*3600 && $c['statut']=='actif';
    ?>
    <div class="assure-card" style="border-left:4px solid <?= $c['statut']=='actif'?'#2e7d32':'#f57c00'; ?>;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
            <div>
                <div style="font-size:18px;font-weight:700;color:#0d47a1;"><?= $c['numero_police']; ?></div>
                <div style="font-size:13px;color:#546e7a;margin-top:4px;"><?= $c['marque'].' '.$c['modele'].' — '.$c['matricule'].' ('.$c['annee'].')'; ?></div>
            </div>
            <div style="display:flex;gap:10px;align-items:center;">
                <span class="badge-etat <?= $si[0]; ?>"><?= $si[1]; ?></span>
                <?php if($expire_bientot): ?>
                <span class="badge-etat orange"><i class="fa fa-exclamation-triangle"></i> Expire bientôt</span>
                <?php endif; ?>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-top:20px;">
            <div>
                <div style="font-size:11px;color:#78909c;font-weight:700;text-transform:uppercase;">Formule</div>
                <div style="font-weight:600;margin-top:4px;"><?= $c['nom_formule']; ?></div>
            </div>
            <div>
                <div style="font-size:11px;color:#78909c;font-weight:700;text-transform:uppercase;">Date d'effet</div>
                <div style="font-weight:600;margin-top:4px;"><?= $c['date_effet']; ?></div>
            </div>
            <div>
                <div style="font-size:11px;color:#78909c;font-weight:700;text-transform:uppercase;">Date d'expiration</div>
                <div style="font-weight:600;margin-top:4px;color:<?= $expire_bientot?'#e65100':'inherit'; ?>"><?= $c['date_expiration']; ?></div>
            </div>
            <div>
                <div style="font-size:11px;color:#78909c;font-weight:700;text-transform:uppercase;">Prime nette</div>
                <div style="font-weight:700;color:#0d47a1;margin-top:4px;"><?= number_format($c['prime_nette'],2,',',' '); ?> DA</div>
            </div>
            <div>
                <div style="font-size:11px;color:#78909c;font-weight:700;text-transform:uppercase;">Net à payer</div>
                <div style="font-weight:700;color:#0d47a1;margin-top:4px;"><?= number_format($c['net_a_payer'],2,',',' '); ?> DA</div>
            </div>
            <div>
                <div style="font-size:11px;color:#78909c;font-weight:700;text-transform:uppercase;">Agence</div>
                <div style="font-weight:600;margin-top:4px;"><?= $c['nom_agence']; ?> — <?= $c['wilaya']; ?></div>
            </div>
        </div>
    </div>
    <?php endwhile; endif; ?>
</div>
</body>
</html>