<?php
$nb_notifs_assure = 0;
if(isset($conn) && isset($_SESSION['id_user'])) {
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM notification WHERE id_destinataire={$_SESSION['id_user']} AND lu=0"));
    $nb_notifs_assure = $r ? $r['n'] : 0;
}
$current = basename($_SERVER['PHP_SELF']);
?>
<div class="assure-sidebar" id="assure-sidebar">
    <div class="assure-brand">
        <img src="/PfeCnma/cnma/images/logo.webp" alt="CNMA">
        <h2></h2>
        <span class="assure-role-badge">Assuré</span>
    </div>
   <nav class="assure-nav">

    <!-- DASHBOARD -->
    <div class="assure-section">
        <a href="/PfeCnma/cnma/assure/dashboard_assure.php" class="<?= $current=='dashboard_assure.php'?'active':'' ?>">
            <i class="fa fa-home"></i>
            <span class="link-text">Tableau de bord</span>
        </a>
    </div>

    <!-- MES DONNÉES -->
    <div class="assure-section">
        <div class="assure-section-label">Mes données</div>

        <a href="/PfeCnma/cnma/assure/mes_contrats.php" class="<?= $current=='mes_contrats.php'?'active':'' ?>">
            <i class="fa fa-file-contract"></i>
            <span class="link-text">Contrats</span>
        </a>

        <a href="/PfeCnma/cnma/assure/mes_dossiers_assure.php" class="<?= $current=='mes_dossiers_assure.php'?'active':'' ?>">
            <i class="fa fa-folder-open"></i>
            <span class="link-text">Dossiers</span>
        </a>

        <a href="/PfeCnma/cnma/assure/mes_paiements.php" class="<?= $current=='mes_paiements.php'?'active':'' ?>">
            <i class="fa fa-money-check-alt"></i>
            <span class="link-text">Paiements</span>
        </a>
    </div>

    <!-- SUIVI -->
    <div class="assure-section">
        <div class="assure-section-label">Suivi</div>

        <a href="/PfeCnma/cnma/assure/notifications_assure.php"
           class="<?= $current=='notifications_assure.php'?'active':'' ?>"
           style="position:relative;">

            <i class="fa fa-bell"></i>
            <span class="link-text">Notifications</span>

            <?php if($nb_notifs_assure > 0): ?>
                <span class="assure-notif-badge"><?= $nb_notifs_assure ?></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- COMPTE -->
    <div class="assure-section">
        <div class="assure-section-label">Compte</div>

        <a href="/PfeCnma/cnma/assure/mon_profil.php" class="<?= $current=='mon_profil.php'?'active':'' ?>">
            <i class="fa fa-user-circle"></i>
            <span class="link-text">Profil</span>
        </a>
    </div>

</nav>
    <div class="assure-footer">
        <a href="/PfeCnma/cnma/assure/logout.php">
            <i class="fa fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</div>
<div class="assure-sidebar-overlay" data-assure-sidebar-overlay></div>
