<?php
// Compter notifications non lues (pour admin CNMA — en tant qu'expéditeur on n'en reçoit pas)
// Récupérer dossiers en attente
$nb_attente = 0;
if(isset($conn)) {
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM dossier WHERE id_etat = 3"));
    $nb_attente = $r['n'];
}

// Page courante pour active
$current = basename($_SERVER['PHP_SELF']);?>

<div class="sidebar cnma-sidebar">
 
<div class="sidebar-brand centered">
    <img src="/PfeCnma/cnma/images/logo.webp">
  
    <h2>CNMA</h2>
    <div class="badge-role">Administration</div>

    <button class="sidebar-toggle">
        <i class="fa fa-bars"></i>
    </button>
</div>

<!-- Dashboard -->
<div class="sidebar-section">
    <nav class="sidebar-nav">
        <a href="/PfeCnma/cnma/pages/dashboard_cnma.php"
           class="sidebar-link <?= $current=='dashboard_cnma.php'?'active':'' ?>">
           
            <span class="sidebar-link-icon"><i class="fa fa-chart-pie"></i></span>
            <span class="sidebar-link-label">Tableau de bord</span>
        </a>
    </nav>
</div>

<!-- Gestion -->
<div class="sidebar-section">
    <div class="sidebar-section-label toggle-section">
        <span><i class="fa fa-folder"></i> Gestion</span>
        <i class="fa fa-chevron-down arrow"></i>
    </div>

    <nav class="sidebar-nav sub-menu">
        <a href="/PfeCnma/cnma/pages/dossiers_attente.php" class="sidebar-link">
            <span class="sidebar-link-icon"><i class="fa fa-clock"></i></span>
            <span class="sidebar-link-label">Dossiers attente</span>
        </a>

        <a href="/PfeCnma/cnma/pages/tous_dossiers_cnma.php" class="sidebar-link">
            <span class="sidebar-link-icon"><i class="fa fa-folder-open"></i></span>
            <span class="sidebar-link-label">Tous dossiers</span>
        </a>
    </nav>
</div>

<!-- Stat -->
<div class="sidebar-section">
    <nav class="sidebar-nav">
        <a href="/PfeCnma/cnma/pages/statistiques_cnma.php" class="sidebar-link">
            <span class="sidebar-link-icon"><i class="fa fa-chart-bar"></i></span>
            <span class="sidebar-link-label">Statistiques</span>
        </a>
    </nav>
</div>

<!-- Compte -->
<div class="sidebar-section">
    <div class="sidebar-section-label toggle-section">
        <span><i class="fa fa-user"></i> Compte</span>
        <i class="fa fa-chevron-down arrow"></i>
    </div>

    <nav class="sidebar-nav sub-menu">

        <a href="/PfeCnma/cnma/pages/gestion_utilisateurs.php" class="sidebar-link">
            <span class="sidebar-link-icon"><i class="fa fa-users"></i></span>
            <span class="sidebar-link-label">Utilisateurs</span>
        </a>

        <a href="/PfeCnma/cnma/pages/profil_cnma.php" class="sidebar-link">
            <span class="sidebar-link-icon"><i class="fa fa-user"></i></span>
            <span class="sidebar-link-label">Profil</span>
        </a>

    </nav>
</div>

<div class="sidebar-footer">
    <a href="/PfeCnma/cnma/pages/logout.php" class="sidebar-link">
        <span class="sidebar-link-icon"><i class="fa fa-right-from-bracket"></i></span>
        <span class="sidebar-link-label">Déconnexion</span>
    </a>
</div>

</div>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ===== COLLAPSE SIDEBAR =====
    const btn = document.querySelector('.sidebar-toggle');

    if (btn) {
        btn.addEventListener('click', function () {
            document.documentElement.classList.toggle('sidebar-collapsed');
        });
    }

    // ===== TOGGLE SECTIONS (Gestion / Compte) =====
    document.querySelectorAll('.toggle-section').forEach(section => {

        section.addEventListener('click', function () {

            const submenu = this.nextElementSibling;

            // toggle affichage propre
            submenu.classList.toggle('show');

            // rotation flèche
            this.classList.toggle('open');

        });

    });

});
</script>