<?php
session_start();
include('../includes/config.php');
if(!isset($_SESSION['id_user']) || $_SESSION['role'] != 'ASSURE') {
    header("Location: ../pages/login.php"); exit();
}
$id_user = $_SESSION['id_user'];
$page_title = "Tableau de bord";

// Récupérer id_assure
$assure = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT a.id_assure FROM assure a JOIN utilisateur u ON a.id_personne=u.id_personne WHERE u.id_user=$id_user LIMIT 1"));
$id_assure = $assure ? $assure['id_assure'] : 0;

// Stats
$nb_contrats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM contrat WHERE id_assure=$id_assure"))['n'];
$nb_dossiers = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as n FROM dossier d JOIN contrat c ON d.id_contrat=c.id_contrat WHERE c.id_assure=$id_assure"))['n'];
$nb_en_cours = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as n FROM dossier d JOIN contrat c ON d.id_contrat=c.id_contrat WHERE c.id_assure=$id_assure AND d.id_etat NOT IN (5,11,12,14)"))['n'];
$nb_clotures = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as n FROM dossier d JOIN contrat c ON d.id_contrat=c.id_contrat WHERE c.id_assure=$id_assure AND d.id_etat=14"))['n'];
$total_recu = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT IFNULL(SUM(r.montant),0) as n FROM reglement r JOIN dossier d ON r.id_dossier=d.id_dossier JOIN contrat c ON d.id_contrat=c.id_contrat WHERE c.id_assure=$id_assure AND r.statut='remis'"))['n'];
$nb_notifs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM notification WHERE id_destinataire=$id_user AND lu=0"))['n'];

// Dernières notifications
$notifs = mysqli_query($conn, "SELECT n.*, d.numero_dossier FROM notification n LEFT JOIN dossier d ON n.id_dossier=d.id_dossier WHERE n.id_destinataire=$id_user ORDER BY n.date_notification DESC LIMIT 5");

// Étas mapping
$etat_map = [1=>'En cours de déclaration',2=>'En cours de traitement',3=>'En cours de validation',4=>'Dossier accepté',5=>'Refusé',6=>'Documents manquants',7=>'Paiement partiel',8=>'Paiement effectué',9=>'En cours de traitement',11=>'Classé sans suite',12=>'Classé',13=>'En attente',14=>'Clôturé'];
$etat_class_map = [1=>'gray',2=>'blue',3=>'orange',4=>'green',5=>'red',6=>'orange',7=>'teal',8=>'green',9=>'blue',14=>'gray'];

// Dossiers récents
$dossiers = mysqli_query($conn, "SELECT d.id_dossier,d.numero_dossier,d.date_sinistre,d.id_etat,d.total_reserve FROM dossier d JOIN contrat c ON d.id_contrat=c.id_contrat WHERE c.id_assure=$id_assure ORDER BY d.id_dossier DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mon Espace - Dashboard</title>
<link rel="stylesheet" href="../css/style_assure.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php include('sidebar_assure.php'); ?>
<?php include('header_assure.php'); ?>
<div class="assure-main">
    <div class="page-heading">
        <h2><i class="fa fa-home"></i> Bienvenue, <?= htmlspecialchars(explode(' ',$_SESSION['nom'])[0]); ?></h2>
    </div>

    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fa fa-file-contract"></i></div>
            <div class="stat-value"><?= $nb_contrats; ?></div>
            <div class="stat-label">Mes contrats</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon"><i class="fa fa-folder-open"></i></div>
            <div class="stat-value"><?= $nb_dossiers; ?></div>
            <div class="stat-label">Mes dossiers</div>
        </div>
        <div class="stat-card teal">
            <div class="stat-icon"><i class="fa fa-clock"></i></div>
            <div class="stat-value"><?= $nb_en_cours; ?></div>
            <div class="stat-label">En cours</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><i class="fa fa-check-circle"></i></div>
            <div class="stat-value"><?= $nb_clotures; ?></div>
            <div class="stat-label">Clôturés</div>
        </div>
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fa fa-money-bill-wave"></i></div>
            <div class="stat-value" style="font-size:20px;"><?= number_format($total_recu,2,',',' '); ?></div>
            <div class="stat-label">Total reçu (DA)</div>
        </div>
        <?php if($nb_notifs > 0): ?>
        <div class="stat-card orange">
            <div class="stat-icon"><i class="fa fa-bell"></i></div>
            <div class="stat-value"><?= $nb_notifs; ?></div>
            <div class="stat-label">Notifications</div>
        </div>
        <?php endif; ?>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:22px;">

        <!-- Dossiers récents -->
        <div class="assure-card">
            <h3><i class="fa fa-folder-open"></i> Mes dossiers récents</h3>
            <?php if(mysqli_num_rows($dossiers)==0): ?>
            <p style="color:#90a4ae;text-align:center;padding:20px;">Aucun dossier pour le moment</p>
            <?php else: ?>
            <?php while($d = mysqli_fetch_assoc($dossiers)):
                $ec = $etat_class_map[$d['id_etat']] ?? 'gray';
                $en = $etat_map[$d['id_etat']] ?? 'Inconnu';
            ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f0f4f8;">
                <div>
                    <div style="font-weight:700;color:#0d47a1;font-size:13px;"><?= $d['numero_dossier']; ?></div>
                    <div style="font-size:11px;color:#90a4ae;"><?= $d['date_sinistre']; ?></div>
                </div>
                <span class="badge-etat <?= $ec; ?>"><?= $en; ?></span>
                <a href="mes_dossiers_assure.php?id=<?= $d['id_dossier']; ?>" class="assure-btn primary sm"><i class="fa fa-eye"></i></a>
            </div>
            <?php endwhile; ?>
            <?php endif; ?>
            <div style="margin-top:14px;">
                <a href="mes_dossiers_assure.php" class="assure-btn primary sm"><i class="fa fa-arrow-right"></i> Voir tous</a>
            </div>
        </div>

        <!-- Notifications récentes -->
        <div class="assure-card">
            <h3><i class="fa fa-bell"></i> Mes dernières notifications</h3>
            <?php if(mysqli_num_rows($notifs)==0): ?>
            <p style="color:#90a4ae;text-align:center;padding:20px;">Aucune notification</p>
            <?php else:
            $type_icons = ['validation'=>['fa-check-circle','#2e7d32','#e8f5e9'],'refus'=>['fa-times-circle','#c62828','#ffebee'],'complement'=>['fa-paper-plane','#e65100','#fff3e0'],'reglement'=>['fa-money-bill','#0d47a1','#e3f2fd'],'cloture'=>['fa-archive','#6a1b9a','#f3e5f5']];
            while($n = mysqli_fetch_assoc($notifs)):
                $ic = $type_icons[$n['type']] ?? ['fa-info','#546e7a','#eceff1'];
            ?>
            <div style="display:flex;gap:12px;padding:10px 0;border-bottom:1px solid #f0f4f8;<?= !$n['lu']?'background:#f5f9ff;border-radius:8px;padding:10px;margin-bottom:4px;':''; ?>">
                <div class="notif-icon" style="background:<?= $ic[2]; ?>;color:<?= $ic[1]; ?>;width:36px;height:36px;border-radius:8px;flex-shrink:0;">
                    <i class="fa <?= $ic[0]; ?>" style="font-size:16px;"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-size:12px;color:#2c3e50;font-weight:<?= !$n['lu']?'600':'400'; ?>;"><?= htmlspecialchars(mb_substr($n['message'],0,80)).'...'; ?></div>
                    <div style="font-size:11px;color:#90a4ae;margin-top:3px;"><?= date('d/m/Y H:i',strtotime($n['date_notification'])); ?> — <?= $n['numero_dossier']; ?></div>
                </div>
            </div>
            <?php endwhile; endif; ?>
            <div style="margin-top:14px;">
                <a href="notifications_assure.php" class="assure-btn primary sm"><i class="fa fa-arrow-right"></i> Toutes les notifications</a>
            </div>
        </div>

    </div>
</div>
</body>
</html>