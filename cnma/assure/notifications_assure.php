<?php
session_start();
include('../includes/config.php');
if(!isset($_SESSION['id_user']) || $_SESSION['role'] != 'ASSURE') { header("Location: ../pages/login.php"); exit(); }
$id_user = $_SESSION['id_user'];
$page_title = "Notifications";

if(isset($_GET['read'])) {
    $id = intval($_GET['read']);
    mysqli_query($conn,"UPDATE notification SET lu=1 WHERE id_notification=$id AND id_destinataire=$id_user");
    header("Location: notifications_assure.php"); exit();
}
if(isset($_GET['read_all'])) {
    mysqli_query($conn,"UPDATE notification SET lu=1 WHERE id_destinataire=$id_user");
    header("Location: notifications_assure.php"); exit();
}

$notifications = mysqli_query($conn,"SELECT n.*,d.numero_dossier,u.nom AS exp_nom FROM notification n LEFT JOIN dossier d ON n.id_dossier=d.id_dossier LEFT JOIN utilisateur u ON n.id_expediteur=u.id_user WHERE n.id_destinataire=$id_user AND n.type <> 'complement' ORDER BY n.date_notification DESC");
$nb_non_lues = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as n FROM notification WHERE id_destinataire=$id_user AND lu=0 AND type <> 'complement'"))['n'];
$type_icons = ['validation'=>['fa-check-circle','#2e7d32','#e8f5e9'],'refus'=>['fa-times-circle','#c62828','#ffebee'],'complement'=>['fa-paper-plane','#e65100','#fff3e0'],'reglement'=>['fa-money-bill','#0d47a1','#e3f2fd'],'cloture'=>['fa-archive','#6a1b9a','#f3e5f5']];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Notifications</title>
<link rel="stylesheet" href="../css/style_assure.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php include('sidebar_assure.php'); ?>
<?php include('header_assure.php'); ?>
<div class="assure-main">
    <div class="page-heading">
        <h2>
            <i class="fa fa-bell"></i> Mes notifications
            <?php if($nb_non_lues > 0): ?><span style="background:#ef5350;color:white;border-radius:20px;padding:3px 12px;font-size:13px;"><?= $nb_non_lues; ?></span><?php endif; ?>
        </h2>
        <?php if($nb_non_lues > 0): ?>
        <a href="?read_all=1" class="assure-btn secondary sm"><i class="fa fa-check-double"></i> Tout marquer lu</a>
        <?php endif; ?>
    </div>

    <?php if(mysqli_num_rows($notifications)==0): ?>
    <div style="text-align:center;padding:60px;background:white;border-radius:14px;">
        <i class="fa fa-bell-slash" style="font-size:48px;color:#cfd8dc;display:block;margin-bottom:16px;"></i>
        <p style="color:#90a4ae;">Aucune notification pour le moment</p>
    </div>
    <?php else: while($n = mysqli_fetch_assoc($notifications)):
        $ic = $type_icons[$n['type']] ?? ['fa-info-circle','#546e7a','#eceff1'];
        $non_lu = !$n['lu'];
    ?>
    <div class="notif-item <?= $non_lu?'non-lu':''; ?>">
        <?php if($non_lu): ?><div style="width:10px;height:10px;border-radius:50%;background:#0d47a1;flex-shrink:0;margin-top:6px;"></div><?php endif; ?>
        <div class="notif-icon" style="background:<?= $ic[2]; ?>;color:<?= $ic[1]; ?>;">
            <i class="fa <?= $ic[0]; ?>" style="font-size:18px;"></i>
        </div>
        <div style="flex:1;">
            <div style="font-size:14px;color:#2c3e50;font-weight:<?= $non_lu?'600':'400'; ?>;margin-bottom:6px;"><?= htmlspecialchars($n['message']); ?></div>
            <div style="font-size:12px;color:#90a4ae;display:flex;gap:14px;flex-wrap:wrap;">
                <span><i class="fa fa-calendar"></i> <?= date('d/m/Y H:i',strtotime($n['date_notification'])); ?></span>
                <span><i class="fa fa-building"></i> <?= htmlspecialchars($n['exp_nom'] ?? ''); ?></span>
                <span><i class="fa fa-folder"></i> <?= htmlspecialchars($n['numero_dossier'] ?? ''); ?></span>
            </div>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="mes_dossiers_assure.php?id=<?= $n['id_dossier']; ?>" class="assure-btn primary sm"><i class="fa fa-eye"></i></a>
            <?php if($non_lu): ?><a href="?read=<?= $n['id_notification']; ?>" class="assure-btn secondary sm"><i class="fa fa-check"></i></a><?php endif; ?>
        </div>
    </div>
    <?php endwhile; endif; ?>
</div>
</body>
</html>
