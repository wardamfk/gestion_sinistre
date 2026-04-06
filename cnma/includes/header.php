<?php
// header.php — CRMA
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($conn)) { include __DIR__ . '/config.php'; }

$nom      = $_SESSION['nom']       ?? 'Agent';
$agence   = $_SESSION['nom_agence']?? '';
$wilaya   = $_SESSION['wilaya']    ?? '';
$initiale = strtoupper(substr($nom, 0, 1));

$nb_notifs = 0;
$r = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as n FROM notification WHERE id_destinataire={$_SESSION['id_user']} AND lu=0"));
$nb_notifs = $r ? (int)$r['n'] : 0;

$page_title = $page_title ?? 'Gestion des sinistres';
$base = '/PfeCnma/cnma';
?>
<div class="crma-header">
    <div class="page-title"><?= htmlspecialchars($page_title) ?></div>
    <div class="header-right">
        <?php if ($nb_notifs > 0): ?>
        <a href="<?= $base ?>/crma/notifications.php" class="notif-bell" title="Notifications">
            <i class="fa fa-bell"></i>
            <span class="nb-count"><?= $nb_notifs ?></span>
        </a>
        <?php endif; ?>
        <div class="user-pill">
            <div class="av"><?= $initiale ?></div>
            <span><?= htmlspecialchars($nom) ?><?= $agence ? ' · '.$agence : '' ?></span>
        </div>
    </div>
</div>