<?php
$nb_notifs_assure = 0;
if(isset($conn) && isset($_SESSION['id_user'])) {
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM notification WHERE id_destinataire={$_SESSION['id_user']} AND lu=0"));
    $nb_notifs_assure = $r ? $r['n'] : 0;
}
$current = basename($_SERVER['PHP_SELF']);
?>
<div class="assure-sidebar">
    <div class="assure-brand">
        <img src="/PfeCnma/cnma/images/logo.webp" alt="CNMA">
        <h2></h2>
        <span class="assure-role-badge">Assuré</span>
    </div>
    <nav class="assure-nav">
        <a href="/PfeCnma/cnma/assure/dashboard_assure.php" class="<?= $current=='dashboard_assure.php'?'active':'' ?>">
            <i class="fa fa-home"></i> Tableau de bord
        </a>
        <a href="/PfeCnma/cnma/assure/mes_contrats.php" class="<?= $current=='mes_contrats.php'?'active':'' ?>">
            <i class="fa fa-file-contract"></i> contrats
        </a>
        <a href="/PfeCnma/cnma/assure/mes_dossiers_assure.php" class="<?= $current=='mes_dossiers_assure.php'?'active':'' ?>">
            <i class="fa fa-folder-open"></i>  dossiers
        </a>
        <a href="/PfeCnma/cnma/assure/mes_paiements.php" class="<?= $current=='mes_paiements.php'?'active':'' ?>">
            <i class="fa fa-money-check-alt"></i>  paiements
        </a>
      
        <a href="/PfeCnma/cnma/assure/notifications_assure.php" class="<?= $current=='notifications_assure.php'?'active':'' ?>" style="position:relative;">
            <i class="fa fa-bell"></i> Notifications
            <?php if($nb_notifs_assure > 0): ?>
            <span class="assure-notif-badge"><?= $nb_notifs_assure ?></span>
            <?php endif; ?>
        </a>
        <a href="/PfeCnma/cnma/assure/mon_profil.php" class="<?= $current=='mon_profil.php'?'active':'' ?>">
            <i class="fa fa-user-circle"></i> profil
        </a>
    </nav>
    <div class="assure-footer">
        <a href="/PfeCnma/cnma/pages/logout.php">
            <i class="fa fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</div>