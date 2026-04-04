<?php
// Compter notifications non lues (pour admin CNMA — en tant qu'expéditeur on n'en reçoit pas)
// Récupérer dossiers en attente
$nb_attente = 0;
if(isset($conn)) {
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM dossier WHERE id_etat = 3"));
    $nb_attente = $r['n'];
}

// Page courante pour active
$current = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <img src="/PfeCnma/cnma/images/logo.webp" alt="CNMA">
        <h2>CNMA</h2>
        <div class="role-badge">Administration</div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Navigation</div>

        <a href="/PfeCnma/cnma/pages/dashboard_cnma.php"
           class="<?php echo ($current=='dashboard_cnma.php') ? 'active' : ''; ?>">
            <i class="fa fa-chart-pie"></i> Tableau de bord
        </a>

        <a href="/PfeCnma/cnma/pages/dossiers_attente.php"
           class="<?php echo ($current=='dossiers_attente.php') ? 'active' : ''; ?>">
            <i class="fa fa-clock"></i> En attente
            <?php if($nb_attente > 0): ?>
            <span class="notif-badge"><?php echo $nb_attente; ?></span>
            <?php endif; ?>
        </a>

        <a href="/PfeCnma/cnma/pages/tous_dossiers_cnma.php"
           class="<?php echo ($current=='tous_dossiers_cnma.php') ? 'active' : ''; ?>">
            <i class="fa fa-folder-open"></i> Tous les dossiers
        </a>

        <a href="/PfeCnma/cnma/pages/statistiques_cnma.php"
           class="<?php echo ($current=='statistiques_cnma.php') ? 'active' : ''; ?>">
            <i class="fa fa-chart-bar"></i> Statistiques
        </a>

        <div class="nav-section" style="margin-top:10px;">Administration</div>

        <a href="/PfeCnma/cnma/pages/gestion_utilisateurs.php"
           class="<?php echo ($current=='gestion_utilisateurs.php') ? 'active' : ''; ?>">
            <i class="fa fa-users"></i> Utilisateurs
        </a>

        <a href="/PfeCnma/cnma/pages/historique_global.php"
           class="<?php echo ($current=='historique_global.php') ? 'active' : ''; ?>">
            <i class="fa fa-history"></i> Historique global
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="/PfeCnma/cnma/pages/logout.php">
            <i class="fa fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</div>